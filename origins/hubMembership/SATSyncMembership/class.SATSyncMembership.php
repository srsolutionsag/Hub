<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Origin/class.hubOrigin.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Origin/int.hubOriginInterface.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Category/class.hubCategory.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/User/class.hubUser.php');
/**
 * Class SATSyncMembership
 *
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 * @version 1.0.0** @revision $r$
 */
class SATSyncMembership extends hubOrigin implements hubOriginInterface {

    const DELIMITER = ';';

    /**
     * @var array
     */
    protected $data = array();

    /**
     * Stores the objects coming from the external system that are newly delivered
     *
     * @var array
     */
    protected $new_delivered_objects = array();

    /**
     * Field columns inside CSV (correct order!!!)
     *
     * @var array
     */
    protected static $fields_external = array(
        'artemis_id',           //course ARTEMIS-id
        'usr_login',    //login = ARTEMIS-name
        'course_title',
    );

    /**
     * cache with ids of newly created users
     *
     * @var array
     */
    protected $new_users = array();

    /**
     * @return bool
     * @description Connect to your Service, return bool status
     */
    public function connect()
    {
        return is_readable($this->conf()->getFilePath());
    }

    /**
     * @return bool
     * @description read your Data an save in Class
     */
    public function parseData() {
        $file = $this->conf()->getFilePath();
        $n_fields = count(self::$fields_external);
        $line = 0;
        $checksum = 0;
        if (($handle = fopen($file, 'r')) !== false) {
            while (($data = fgetcsv($handle, 0, self::DELIMITER)) !== false) {
                $count = count($data);
                if ($count != $n_fields) {
                    $msg = "File $file, Line $line: number of columns does not match. Got $count, expected $n_fields";
                    throw new hubOriginException(hubOriginException::PARSE_DATA_FAILED, $this, true, $msg);
                } else {
                    //                    $this->validateRecord($data, $line, $file);
                    $tmp = new stdClass();
                    foreach (self::$fields_external as $i => $name) {
                        $tmp->$name = $data[$i];
                    }
                    $this->data[] = $tmp;
                    $checksum++;
                    $line++;
                }
            }
            fclose($handle);
        }
        return true;
    }

    /**
     * @return array
     * @description return array of Data
     */
    public function getData() {
        return $this->data;
    }

    /**
     * @return bool|void
     */
    public function afterSync()
    {
        foreach ($this->data as $data) {
           $hub_membership = hubMembership::getInstance($data->usr_login, $data->artemis_id);

            if (!$hub_membership->is_new && $hub_membership->getHistoryObject()->getStatus() == hubSyncHistory::STATUS_NEW
                && hubConfig::get(hubConfig::F_MMAIL_ACTIVE)) {
                $this->sendMembershipMail($hub_membership);
            }
        }
    }

    /**
     * @return bool
     */
    public function buildEntries()
    {
        foreach ($this->data as $data) {
            if(!ilObjUser::_loginExists($data->usr_login)){
                $this->log->write("User {$data->usr_login} doesn't exist in ilias! Please check, if it is in the user-import file");
                continue;
            }

            $hub = hubMembership::getInstance($data->usr_login, $data->artemis_id);
            if ($hub === null) {
                $this->log->write("Skipped building entry, reason: No hubMembership object found with ext_id={$data->login}, course_id={$data->artemis_id}");
                continue;
            }

            // Find ref-id of course
            /** @var hubCourse $hub_course */
            $hub_course = hubCourse::find($data->artemis_id);
            if ($hub_course === null) {
                $this->log->write("Skipped building entry, reason: No hubCourse object found course_id={$data->course_id}");
                continue;
            }
            $ref_id = $hub_course->getHistoryObject()->getIliasId();
            if (!ilObject2::_lookupObjectId($ref_id)) {
                $this->log->write("Skipped building entry, reason: No ILIAS object found for course-ref-id {$ref_id}");
                continue;
            }
            $this->log->write("course-ref-id {$ref_id}, course-obj-id " . ilObject2::_lookupObjectId($ref_id));

            $hub->setContainerId($ref_id);
            $hub->setUsrId(ilObjUser::_lookupId($data->usr_login));
            $hub->setContainerRole(hubMembership::CONT_ROLE_CRS_MEMBER);
            $hub->create($this);

        }
        return true;
    }

    /**
     * @param hubMembership $hub
     */
    protected function sendMembershipMail(hubMembership $hub) {
        global $ilSetting;
        $user = new ilObjUser($hub->getUsrId());
        $lng = new ilLanguage($user->getLanguage());
        $lng->loadLanguageModule('ui_uihk_hub');

        $mail = new ilMimeMail();
        $mail->autoCheck(false);
        $mail->From($ilSetting->get('admin_email'));
        $mail->To($user->getEmail());

        $body = $this->fillPlaceholders(hubConfig::get(hubConfig::F_MMAIL_MSG), $hub, $user);
        $subject = $this->fillPlaceholders(hubConfig::get(hubConfig::F_MMAIL_SUBJECT), $hub, $user);

        $mail->Subject($subject);
        $mail->Body($body);
        $mail->Send();
    }

    protected function fillPlaceholders($string, $hub, $user) {
        $activation = $this->getActivations($hub);
        $string = strtr($string, array(
            '[FIRSTNAME]' => $user->getFirstname(),
            '[LASTNAME]' => $user->getLastname(),
            '[LOGIN]' => $user->getLogin(),
            '[COURSE_LINK]' => ilUtil::_getHttpPath().'/goto.php?target=crs_'.$hub->getContainerId(),
            '[COURSE_TITLE]' => ilObjCourse::_lookupTitle(ilObject2::_lookupObjId($hub->getContainerId())),
            '[VALIDITY_START]' => $activation['valid_from'],
            '[VALIDITY_END]' => $activation['valid_to'],
        ));
        return $string;
    }

    protected function getActivations(hubMembership $hub) {
        global $ilDB;
        $query = $ilDB->query("SELECT DATE_FORMAT(FROM_UNIXTIME(timing_start), '%Y-%m-%d') as valid_from, DATE_FORMAT(FROM_UNIXTIME(timing_end), '%Y-%m-%d') as valid_to FROM crs_items WHERE obj_id = " . $ilDB->quote($hub->getContainerId(), 'integer'));
        return ($ilDB->fetchAssoc($query));
    }

}