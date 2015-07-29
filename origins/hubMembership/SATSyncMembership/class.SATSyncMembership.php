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
        'client_id',
        'template_id',
        'artemis_id',           //course ARTEMIS-id
        'usr_login',    //login = ARTEMIS-name
        'course_title',
        'usr_lastname',
        'usr_firstname',
        'usr_mail'
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
            if ($hub_membership->getHistoryObject()->getStatus() == hubSyncHistory::STATUS_NEW
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
                $this->log->write("User {$data->usr_login} doesn't exist in ilias -> create user");
                $this->createUser($data);
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

    protected function createUser($data)
    {
        global $rbacreview, $rbacadmin;
        $user = new ilObjUser();
        $user->setLogin($data->usr_login);
        $user->setFirstname($data->usr_firstname);
        $user->setLastname($data->usr_lastname);
        $user->setEmail($data->usr_mail);
        $user->setActive(true);
        $password = $this->generatePassword();
        $user->setPasswd(md5($password), IL_PASSWD_MD5);
        $user->setTimeLimitUnlimited(true);
        $user->create();
        $user->saveAsNew();

        $this->sendPasswordMail($user, $password);

        //Assign role SAT_G_MEMBER (sat-specific)
        $roles = $rbacreview->getAssignableRoles(false, false, hubConfig::get(hubConfig::F_STANDARD_ROLE));
        if (sizeof($roles)){
            $rbacadmin->assignUser($roles[0]["rol_id"], $user->getId());
        } else {
            $rbacadmin->assignUser(4, $user->getId());  //Assign standard-role 'User'
        }

    }

    protected function generatePassword() {
        $pwds = ilUtil::generatePasswords(1);
        return $pwds[0];
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

    /**
     * @param ilObjUser $user
     */
    protected function sendPasswordMail(ilObjUser $user, $password) {
        global $ilSetting,$ilias;

        // Choose language of user
        $usr_lang = new ilLanguage($user->getLanguage());
        $usr_lang->loadLanguageModule('crs');
        $usr_lang->loadLanguageModule('registration');

        include_once "Services/Mail/classes/class.ilMimeMail.php";

        $mmail = new ilMimeMail();
        $mmail->autoCheck(false);
        $mmail->From($ilSetting->get('admin_email'));
        $mmail->To($user->getEmail());

        // mail subject
        $subject = $usr_lang->txt("reg_mail_subject");


        // mail body
        $body = ($usr_lang->txt("reg_mail_body_salutation")." ".$user->getFullname().",\n\n");

        $body .= ($usr_lang->txt('reg_mail_body_text1')."\n\n");


        // Append login info only if password has been chacnged
        if($_POST['passwd'] != '********')
        {
            $body .= $usr_lang->txt("reg_mail_body_text2")."\n".
                ilUtil::_getHttpPath()."/login.php?client_id=".$ilias->client_id."\n".
                $usr_lang->txt("login").": ".$user->getLogin()."\n".
                $usr_lang->txt("passwd").": ".$password."\n\n";
        }
        $body .= ($usr_lang->txt("reg_mail_body_text3")."\n");
        $body .= $user->getProfileAsString($usr_lang);

        $mmail->Subject($subject);
        $mmail->Body($body);
        $mmail->Send();
    }

    protected function getMailBody($type = 'membership', $lng){
        $body = '';
        switch($type)
        {
            case 'membership':
                $body .= $lng->txt('login') . ": [LOGIN] \n\n";
                $body .= $lng->txt('crs_title') . ": [COURSE_TITLE] \n\n";
                $body .= $lng->txt('ui_uihk_hub_validity') . ": [VALIDITY] \n\n";
                break;
            case 'password':
                $body .= $lng->txt('login') . ": [LOGIN] \n\n";
                $body .= $lng->txt('password') . ": [PASSWORD] \n\n";
                break;
        }
        return $body;
    }
}