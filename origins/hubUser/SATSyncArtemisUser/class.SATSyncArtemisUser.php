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
class SATSyncArtemisUser extends hubOrigin implements hubOriginInterface {

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
        'usr_login',    //login = ARTEMIS-name
        'usr_lastname',
        'usr_firstname',
        'usr_mail',
        'is4LC'
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
     * @return bool
     */
    public function buildEntries()
    {
        global $rbacreview, $rbacadmin;

        foreach ($this->data as $data) {
            $hubUser = new hubUser($data->usr_login);
            $hubUser->setLogin($data->usr_login);
            $hubUser->setExternalAccount($data->usr_login);
            $hubUser->setFirstname($data->usr_firstname);
            $hubUser->setLastname($data->usr_lastname);
            $hubUser->setEmail($data->usr_mail);
            $hubUser->setTimeLimitUnlimited(true);

            //Assign role SAT_G_MEMBER (sat-specific)
            $roles = $rbacreview->getAssignableRoles(false, false, hubConfig::get(hubConfig::F_STANDARD_ROLE));
            if (count($roles) > 0){
                $hubUser->setIliasRoles(array($roles[0]["rol_id"]));
            } else {
                $hubUser->setIliasRoles(array(4)); //Assign standard-role 'User'
            }

            $hubUser->generatePassword();
            $password = $hubUser->getPasswd();
            if(strtolower($data->is4LC) == 'n' && !hubSyncCron::getDryRun()) {
                $this->sendPasswordMail($hubUser, $password);
            }

            $hubUser->create($this);
        }
        return true;
    }

    /**
     * @param hubUser $user
     */
    protected function sendPasswordMail(hubUser $user, $password) {
        global $ilSetting,$ilias;

        // Choose language of user
        $usr_lang = new ilLanguage('en');
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
}