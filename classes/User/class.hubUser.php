<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/class.hubObject.php');
require_once('./Services/Mail/classes/class.ilMimeMail.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/User/class.hubUserFields.php');

/**
 * Class hubUser
 *
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.1.04
 *
 * @revision $r$
 */
class hubUser extends hubObject {

	const GENDER_MALE = 'm';
	const GENDER_FEMALE = 'f';
	const ACCOUNT_TYPE_ILIAS = 1;
	const ACCOUNT_TYPE_SHIB = 2;
	const ACCOUNT_TYPE_LDAP = 3;
	const ACCOUNT_TYPE_RADIUS = 4;
	/**
	 * @var ilObjUser
	 */
	public $ilias_object;
	/**
	 * @var int
	 */
	public static $id_type = self::ILIAS_ID_TYPE_USER;


	/**
	 * @return bool
	 */
	public static function buildILIASObjects() {
		/**
		 * @var $hubUser   hubUser
		 * @var $hubOrigin hubOrigin
		 */

		$count = self::count();
		$steps = 1000;
		$step = 0;
		$hasSets = true;
		hubLog::getInstance()->write("Start building $count ILIAS objects");
		while ($hasSets) {
			$start = $step * $steps;
			hubLog::getInstance()->write("Start looping $steps records, round=" . ($step + 1) . ", limit=$start,$steps");
			$hubUsers = self::limit($start, $steps)->get();
			hubLog::getInstance()->write("Count for round " . ($step+1) . ": " . count($hubUsers));
			if (!count($hubUsers)) {
            	hubLog::getInstance()->write("No more sets found, aborting: step=$step");    
				$hasSets = false;
			}
			foreach ($hubUsers as $hubUser) {
				if (!hubSyncHistory::isLoaded($hubUser->getSrHubOriginId())) {
					continue;
				}
				$duration_id = 'obj_origin_' . $hubUser->getSrHubOriginId();
				hubDurationLogger2::getInstance($duration_id)->resume();
				$hubOrigin = hubOrigin::getClassnameForOriginId($hubUser->getSrHubOriginId());
				$hubOriginObj = $hubOrigin::find($hubUser->getSrHubOriginId());
				self::lookupExisting($hubUser);
				switch ($hubUser->getHistoryObject()->getStatus()) {
					case hubSyncHistory::STATUS_NEW:
						if (!hubSyncCron::getDryRun()) {
							$hubUser->createUser();
						}
						hubCounter::incrementCreated($hubUser->getSrHubOriginId());
						hubOriginNotification::addMessage($hubUser->getSrHubOriginId(), $hubUser->getEmail(), 'User created:');
						break;
					case hubSyncHistory::STATUS_UPDATED:
						if (!hubSyncCron::getDryRun()) {
							$hubUser->updateUser();
						}
						hubCounter::incrementUpdated($hubUser->getSrHubOriginId());
						break;
					case hubSyncHistory::STATUS_DELETED:
						if (!hubSyncCron::getDryRun()) {
							$hubUser->deleteUser();
						}
						hubCounter::incrementDeleted($hubUser->getSrHubOriginId());
						//					hubOriginNotification::addMessage($hubUser->getSrHubOriginId(), $hubUser->getEmail(), 'User deleted:');
						break;
					case hubSyncHistory::STATUS_ALREADY_DELETED:
						hubCounter::incrementIgnored($hubUser->getSrHubOriginId());
						//					hubOriginNotification::addMessage($hubUser->getSrHubOriginId(), $hubUser->getEmail(), 'User ignored:');
						break;
					case hubSyncHistory::STATUS_NEWLY_DELIVERED:
						hubCounter::incrementNewlyDelivered($hubUser->getSrHubOriginId());
						//					hubOriginNotification::addMessage($hubUser->getSrHubOriginId(), $hubUser->getEmail(), 'User newly delivered:');
						if (!hubSyncCron::getDryRun()) {
							$hubUser->updateUser();
						}
						break;
				}
				$hubUser->getHistoryObject()->updatePickupDate();
				if (!hubSyncCron::getDryRun()) {
					$hubOriginObj->afterObjectInit($hubUser);
				}
				hubDurationLogger2::getInstance($duration_id)->pause();
				arObjectCache::purge($hubUser);
				$hubUser = NULL;
			}
			$step++;
		}

		return true;
	}


	public function createUser() {
		$this->ilias_object = new ilObjUser();
		$this->updateLogin();
		$this->updateExternalAuth();
		$this->ilias_object->setTitle($this->getFirstname() . ' ' . $this->getLastname());
		$this->ilias_object->setDescription($this->getEmail());
		$this->ilias_object->setImportId($this->returnImportId());
		$this->ilias_object->create();
		$this->ilias_object->setFirstname($this->getFirstname());
		$this->ilias_object->setLastname($this->getLastname());
		$this->ilias_object->setEmail($this->getEmail());
		if ($this->props()->get(hubUserFields::F_ACTIVATE_ACCOUNT)) {
			$this->ilias_object->setActive(true);
			$this->ilias_object->setProfileIncomplete(false);
		} else {
			$this->ilias_object->setActive(false);
			$this->ilias_object->setProfileIncomplete(true);
		}
		if ($this->props()->get(hubUserFields::F_CREATE_PASSWORD)) {
			$this->generatePassword();
			$password = md5($this->getPasswd());
			$this->ilias_object->setPasswd($password, IL_PASSWD_MD5);
		} else {
			$this->ilias_object->setPasswd($this->getPasswd());
		}
		if ($this->props()->get(hubUserFields::F_SEND_PASSWORD)) {
			$this->sendPasswordMail();
		}
// 		$this->ilias_object->setInstitution($this->getInstitution());
//		$this->ilias_object->setStreet($this->getStreet());
//		$this->ilias_object->setCity($this->getCity());
//		$this->ilias_object->setZipcode($this->getZipcode());
		$this->ilias_object->setCountry($this->getCountry());
		$this->ilias_object->setSelectedCountry($this->getSelCountry());
//		$this->ilias_object->setPhoneOffice($this->getPhoneOffice());
//		$this->ilias_object->setPhoneHome($this->getPhoneHome());
//		$this->ilias_object->setPhoneMobile($this->getPhoneMobile());
//		$this->ilias_object->setDepartment($this->getDepartment());
//		$this->ilias_object->setFax($this->getFax());
//		$this->ilias_object->setTimeLimitOwner($this->getTimeLimitOwner());
//		$this->ilias_object->setTimeLimitUnlimited($this->getTimeLimitUnlimited());
//		$this->ilias_object->setTimeLimitFrom($this->getTimeLimitFrom());
//		$this->ilias_object->setTimeLimitUntil($this->getTimeLimitUntil());
//		$this->ilias_object->setMatriculation($this->getMatriculation());
//		$this->ilias_object->setGender($this->getGender());
		$this->ilias_object->saveAsNew();
		$this->ilias_object->writePrefs();
		$this->assignRoles();
		$history = $this->getHistoryObject();
		$history->setIliasId($this->ilias_object->getId());
		$history->setIliasIdType(self::ILIAS_ID_TYPE_USER);
		$history->update();
	}


	/**
	 * @return bool
	 */
	private function updateLogin() {
		if (!$this->ilias_object) {
			return false;
		}
		switch ($this->props()->get(hubUserFields::F_LOGIN_FIELD)) {
			case 'email':
				$login = $this->getEmail();
				break;
			case 'external_account':
				$login = $this->getExternalAccount();
				break;
			case 'ext_id':
				$login = $this->getExtId();
				break;
			case 'first_and_lastname':
				$login = self::cleanName($this->getFirstname()) . '.' . self::cleanName($this->getLastname());
				break;
			case 'own':
				$login = $this->getLogin();
				break;
			default:
				$login = substr(self::cleanName($this->getFirstname()), 0, 1) . '.' . self::cleanName($this->getLastname());
		}
		$appendix = 2;
		$login_tmp = $login;
		while (self::loginExists($login, $this->ilias_object->getId())) {
			$login = $login_tmp . $appendix;
			$appendix ++;
		}
		//$this->ilias_object->updateLogin($login); has restriction --> direct Method
		$this->ilias_object->setLogin($login);
		global $ilDB;
		/**
		 * @var $ilDB ilDB
		 */

		$ilDB->manipulateF('UPDATE usr_data SET login = %s WHERE usr_id = %s', array(
			'text',
			'integer'
		), array(
			$this->ilias_object->getLogin(),
			$this->ilias_object->getId()
		));

		return true;
	}


	public function generatePassword() {
		$pwds = ilUtil::generatePasswords(1);
		$this->setPasswd($pwds[0]);
	}


	protected function sendPasswordMail() {
		global $ilSetting;
		$mail_field = $this->props()->get(hubUserFields::F_SEND_PASSWORD_FIELD);
		if ($mail_field) {
			$mail = new ilMimeMail();
			$mail->autoCheck(false);
			$mail->From($ilSetting->get('admin_email'));
			$mail->To($this->{$mail_field});
			$body = $this->props()->get(hubUserFields::F_PASSWORD_MAIL_BODY);

			$format = $this->props()->get(hubUserFields::F_PASSWORD_MAIL_DATE_FORMAT);
			$format = $format ? $format : DATE_ISO8601;

			$body = strtr($body, array(
				'[PASSWORD]' => $this->getPasswd(),
				'[LOGIN]' => $this->getLogin(),
				'[VALID_UNTIL]' => date($format, $this->getTimeLimitUntil()),
			));
			$mail->Subject($this->props()->get(hubUserFields::F_PASSWORD_MAIL_SUBJECT));
			$mail->Body($body);
			$mail->Send();
		}
	}


	public function updateUser() {
		if ($this->isUpdateRequired()) {
			$this->ilias_object = new ilObjUser($this->getHistoryObject()->getIliasId());
			$this->ilias_object->setImportId($this->returnImportId());
			$this->ilias_object->setTitle($this->getFirstname() . ' ' . $this->getLastname());
			$this->ilias_object->setDescription($this->getEmail());
			if ($this->props()->get(hubUserFields::F_UPDATE_LOGIN)) {
				$this->updateLogin();
			}
			if ($this->props()->get(hubUserFields::F_UPDATE_FIRSTNAME)) {
				$this->ilias_object->setFirstname($this->getFirstname());
			}
			if ($this->props()->get(hubUserFields::F_UPDATE_LASTNAME)) {
				$this->ilias_object->setLastname($this->getLastname());
			}
			if ($this->props()->get(hubUserFields::F_UPDATE_EMAIL)) {
				$this->ilias_object->setEmail($this->getEmail());
			}

// 			$this->ilias_object->setInstitution($this->getInstitution());
//			$this->ilias_object->setStreet($this->getStreet());
//			$this->ilias_object->setCity($this->getCity());
//			$this->ilias_object->setZipcode($this->getZipcode());
			$this->ilias_object->setCountry($this->getCountry());
			$this->ilias_object->setSelectedCountry($this->getSelCountry());
//			$this->ilias_object->setPhoneOffice($this->getPhoneOffice());
//			$this->ilias_object->setPhoneHome($this->getPhoneHome());
//			$this->ilias_object->setPhoneMobile($this->getPhoneMobile());
//			$this->ilias_object->setDepartment($this->getDepartment());
//			$this->ilias_object->setFax($this->getFax());
//			$this->ilias_object->setTimeLimitOwner($this->getTimeLimitOwner());
//			$this->ilias_object->setTimeLimitUnlimited($this->getTimeLimitUnlimited());
//			$this->ilias_object->setTimeLimitFrom($this->getTimeLimitFrom());
//			$this->ilias_object->setTimeLimitUntil($this->getTimeLimitUntil());
//			$this->ilias_object->setMatriculation($this->getMatriculation());
//			$this->ilias_object->setGender($this->getGender());
			if ($this->props()->get(hubUserFields::F_REACTIVATE_ACCOUNT)) {
				$this->ilias_object->setActive(true);
			}
			$this->updateExternalAuth();
			$this->ilias_object->update();
			$this->assignRoles();
		}
	}


	public function deleteUser() {
		if ($this->props()->get(hubUserFields::F_DELETE)) {
			$this->ilias_object = new ilObjUser($this->getHistoryObject()->getIliasId());
			switch ($this->props()->get(hubUserFields::F_DELETE)) {
				case self::DELETE_MODE_INACTIVE:
					$this->ilias_object->setActive(false);
					$this->ilias_object->update();

					break;
				case self::DELETE_MODE_DELETE:
					$this->ilias_object->delete();
					break;
			}
			$hist = $this->getHistoryObject();
			$hist->setAlreadyDeleted(true);
			$hist->setDeleted(true);
			$hist->update();
		}
	}


	/**
	 * @return bool
	 * @description Assign roles stored in field ilias_roles to ilias user object
	 */
	private function assignRoles() {
		if (!$this->ilias_object) {
			return false;
		}
		/**
		 * @var  $rbacadmin ilRbacAdmin
		 */
		global $rbacadmin;
		$user_id = $this->ilias_object->getId();
		if ($user_id AND count($this->ilias_roles)) {
			foreach ($this->ilias_roles as $role_id) {
				$rbacadmin->assignUser($role_id, $user_id);
			}
		}

		return true;
	}


	/**
	 * @return bool
	 */
	private function updateExternalAuth() {
		if (!$this->ilias_object) {
			return false;
		}
		$auth_mode = '';
		switch ($this->getAccountType()) {
			case self::ACCOUNT_TYPE_ILIAS:
				$auth_mode = 'local';
				break;
			case self::ACCOUNT_TYPE_SHIB:
				$auth_mode = 'shibboleth';
				break;
			case self::ACCOUNT_TYPE_LDAP:
				$auth_mode = 'ldap';
				break;
			case self::ACCOUNT_TYPE_RADIUS:
				$auth_mode = 'radius';
				break;
		}
		$this->ilias_object->setAuthMode($auth_mode);
		$this->ilias_object->setExternalAccount($this->getExternalAccount());

		return true;
	}


	//
	// Helper
	//
	/**
	 * @param $login
	 * @param $usr_id
	 *
	 * @return bool
	 */
	private static function loginExists($login, $usr_id) {
		global $ilDB;
		/**
		 * @var $ilDB ilDB
		 */
		$query = 'SELECT usr_id FROM usr_data WHERE login = ' . $ilDB->quote($login, 'text');
		$query .= ' AND usr_id != ' . $ilDB->quote($usr_id, 'integer');

		return (bool)$ilDB->numRows($ilDB->query($query));
	}


	/**
	 * @param $fieldname
	 * @param $value
	 *
	 * @return bool
	 */
	public static function lookupUsrIdByField($fieldname, $value) {
		global $ilDB;
		$query = 'SELECT usr_id FROM usr_data WHERE ' . $fieldname . ' LIKE ' . $ilDB->quote($value, 'text');
		$res = $ilDB->query($query);
		$existing = $ilDB->fetchObject($res);

		return $existing->usr_id ? $existing->usr_id : false;
	}


	/**
	 * @param $email
	 *
	 * @return bool
	 */
	public static function lookupUsrIdByEmail($email) {
		return self::lookupUsrIdByField('email', $email);
	}


	/**
	 * @param $external_account
	 *
	 * @return bool
	 */
	public static function lookupUsrIdByExtAccount($external_account) {
		return self::lookupUsrIdByField('ext_account', $external_account);
	}


	//
	// Fields
	//

	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        integer
	 * @db_length           4
	 */
	protected $sr_hub_origin_id;
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $passwd;
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $firstname;
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $lastname;
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $login;
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $title;
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        integer
	 * @db_length           1
	 */
	protected $gender;
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $email;
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $email_password;
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $institution;
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $street;
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $city;
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $zipcode;
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $country;
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           8
	 */
	protected $sel_country;
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $phone_office;
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $department;
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $phone_home;
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $phone_mobile;
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $fax;
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $time_limit_owner;
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $time_limit_unlimited = true;
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $time_limit_from;
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $time_limit_until;
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $matriculation;
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        clob
	 */
	protected $image;
	/**
	 * @var int
	 *
	 * @db_has_field        true
	 * @db_fieldtype        integer
	 * @db_length           1
	 */
	protected $account_type = self::ACCOUNT_TYPE_ILIAS;
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $external_account;
	/**
	 * @var array
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $ilias_roles = array();


	/**
	 * @param $hubUser
	 */
	protected static function lookupExisting(hubUser $hubUser) {
		$existing_usr_id = 0;
		switch ($hubUser->props()->get(hubUserFields::F_SYNCFIELD)) {
			case 'email':
				$existing_usr_id = self::lookupUsrIdByEmail($hubUser->getEmail());
				break;
			case 'external_account':
				$existing_usr_id = self::lookupUsrIdByExtAccount($hubUser->getExternalAccount());
				break;
		}
		if ($existing_usr_id > 6) {//} AND ($hubUser->getHistoryObject()->getStatus() == hubSyncHistory::STATUS_NEW)) {
			$history = $hubUser->getHistoryObject();
			$history->setIliasId($existing_usr_id);
			$history->setIliasIdType(self::ILIAS_ID_TYPE_USER);
			$history->update();
		}
	}


	/**
	 * @param $field_name
	 *
	 * @return string
	 */
	public function sleep($field_name) {
		switch ($field_name) {
			case 'ilias_roles':
				return implode(',', $this->ilias_roles);
		}
	}


	/**
	 * @param $field_name
	 * @param $field_value
	 *
	 * @return array
	 */
	public function wakeUp($field_name, $field_value) {
		switch ($field_name) {
			case 'ilias_roles':
				return explode(',', $field_value);
		}
	}


	/**
	 * @param $role_id
	 */
	public function addRole($role_id) {
		$this->ilias_roles[] = $role_id;
		$this->ilias_roles = array_unique($this->ilias_roles);
	}


	public function clearRoles() {
		$this->ilias_roles = array();
	}


	/**
	 * @return string
	 */
	static function returnDbTableName() {
		return 'sr_hub_user';
	}


	/**
	 * @param string $city
	 */
	public function setCity($city) {
		$this->city = $city;
	}


	/**
	 * @return string
	 */
	public function getCity() {
		return $this->city;
	}


	/**
	 * @param string $country
	 */
	public function setCountry($country) {
		$this->country = $country;
	}


	/**
	 * @return string
	 */
	public function getCountry() {
		return $this->country;
	}


	/**
	 * @param string $department
	 */
	public function setDepartment($department) {
		$this->department = $department;
	}


	/**
	 * @return string
	 */
	public function getDepartment() {
		return $this->department;
	}


	/**
	 * @param string $email
	 */
	public function setEmail($email) {
		$this->email = $email;
	}


	/**
	 * @return string
	 */
	public function getEmail() {
		return $this->email;
	}


	/**
	 * @param string $fax
	 */
	public function setFax($fax) {
		$this->fax = $fax;
	}


	/**
	 * @return string
	 */
	public function getFax() {
		return $this->fax;
	}


	/**
	 * @param string $firstname
	 */
	public function setFirstname($firstname) {
		$this->firstname = $firstname;
	}


	/**
	 * @return string
	 */
	public function getFirstname() {
		return $this->firstname;
	}


	/**
	 * @param mixed $gender
	 */
	public function setGender($gender) {
		$this->gender = $gender;
	}


	/**
	 * @return mixed
	 */
	public function getGender() {
		return $this->gender;
	}


	/**
	 * @param string $institution
	 */
	public function setInstitution($institution) {
		$this->institution = $institution;
	}


	/**
	 * @return string
	 */
	public function getInstitution() {
		return $this->institution;
	}


	/**
	 * @param string $lastname
	 */
	public function setLastname($lastname) {
		$this->lastname = $lastname;
	}


	/**
	 * @return string
	 */
	public function getLastname() {
		return $this->lastname;
	}


	/**
	 * @param string $matriculation
	 */
	public function setMatriculation($matriculation) {
		$this->matriculation = $matriculation;
	}


	/**
	 * @return string
	 */
	public function getMatriculation() {
		return $this->matriculation;
	}


	/**
	 * @param string $passwd
	 */
	public function setPasswd($passwd) {
		$this->passwd = $passwd;
	}


	/**
	 * @return string
	 */
	public function getPasswd() {
		return $this->passwd;
	}


	/**
	 * @param string $phone_home
	 */
	public function setPhoneHome($phone_home) {
		$this->phone_home = $phone_home;
	}


	/**
	 * @return string
	 */
	public function getPhoneHome() {
		return $this->phone_home;
	}


	/**
	 * @param string $phone_mobile
	 */
	public function setPhoneMobile($phone_mobile) {
		$this->phone_mobile = $phone_mobile;
	}


	/**
	 * @return string
	 */
	public function getPhoneMobile() {
		return $this->phone_mobile;
	}


	/**
	 * @param string $phone_office
	 */
	public function setPhoneOffice($phone_office) {
		$this->phone_office = $phone_office;
	}


	/**
	 * @return string
	 */
	public function getPhoneOffice() {
		return $this->phone_office;
	}


	/**
	 * @param array $primary_fields
	 */
	public static function setPrimaryFields($primary_fields) {
		self::$primary_fields = $primary_fields;
	}


	/**
	 * @return array
	 */
	public static function getPrimaryFields() {
		return self::$primary_fields;
	}


	/**
	 * @param mixed $sr_hub_origin_id
	 */
	public function setSrHubOriginId($sr_hub_origin_id) {
		$this->sr_hub_origin_id = $sr_hub_origin_id;
	}


	/**
	 * @return mixed
	 */
	public function getSrHubOriginId() {
		return $this->sr_hub_origin_id;
	}


	/**
	 * @param string $street
	 */
	public function setStreet($street) {
		$this->street = $street;
	}


	/**
	 * @return string
	 */
	public function getStreet() {
		return $this->street;
	}


	/**
	 * @param string $time_limit_from
	 */
	public function setTimeLimitFrom($time_limit_from) {
		$this->time_limit_from = $time_limit_from;
	}


	/**
	 * @return string
	 */
	public function getTimeLimitFrom() {
		return $this->time_limit_from;
	}


	/**
	 * @param string $time_limit_owner
	 */
	public function setTimeLimitOwner($time_limit_owner) {
		$this->time_limit_owner = $time_limit_owner;
	}


	/**
	 * @return string
	 */
	public function getTimeLimitOwner() {
		return $this->time_limit_owner;
	}


	/**
	 * @param string $time_limit_unlimited
	 */
	public function setTimeLimitUnlimited($time_limit_unlimited) {
		$this->time_limit_unlimited = $time_limit_unlimited;
	}


	/**
	 * @return string
	 */
	public function getTimeLimitUnlimited() {
		return $this->time_limit_unlimited;
	}


	/**
	 * @param string $time_limit_until
	 */
	public function setTimeLimitUntil($time_limit_until) {
		$this->time_limit_until = $time_limit_until;
	}


	/**
	 * @return string
	 */
	public function getTimeLimitUntil() {
		return $this->time_limit_until;
	}


	/**
	 * @param string $title
	 */
	public function setTitle($title) {
		$this->title = $title;
	}


	/**
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}


	/**
	 * @param string $zipcode
	 */
	public function setZipcode($zipcode) {
		$this->zipcode = $zipcode;
	}


	/**
	 * @return string
	 */
	public function getZipcode() {
		return $this->zipcode;
	}


	/**
	 * @param string $image
	 */
	public function setImage($image) {
		$this->image = $image;
	}


	/**
	 * @return string
	 */
	public function getImage() {
		return $this->image;
	}


	/**
	 * @param int $account_type
	 */
	public function setAccountType($account_type) {
		$this->account_type = $account_type;
	}


	/**
	 * @return int
	 */
	public function getAccountType() {
		return $this->account_type;
	}


	/**
	 * @param string $external_account
	 */
	public function setExternalAccount($external_account) {
		$this->external_account = $external_account;
	}


	/**
	 * @return string
	 */
	public function getExternalAccount() {
		return $this->external_account;
	}


	/**
	 * @param array $ilias_roles
	 */
	public function setIliasRoles($ilias_roles) {
		$this->ilias_roles = $ilias_roles;
	}


	/**
	 * @return array
	 */
	public function getIliasRoles() {
		return $this->ilias_roles;
	}


	/**
	 * @param string $sel_country
	 */
	public function setSelCountry($sel_country) {
		$this->sel_country = $sel_country;
	}


	/**
	 * @return string
	 */
	public function getSelCountry() {
		return $this->sel_country;
	}


	/**
	 * @param string $email_password
	 */
	public function setEmailPassword($email_password) {
		$this->email_password = $email_password;
	}


	/**
	 * @return string
	 */
	public function getEmailPassword() {
		return $this->email_password;
	}


	/**
	 * @param string $login
	 */
	public function setLogin($login) {
		$this->login = $login;
	}


	/**
	 * @return string
	 */
	public function getLogin() {
		return $this->login;
	}


	//
	// Helper
	//
	/**
	 * @param $name
	 *
	 * @return mixed
	 */
	protected static function cleanName($name) {
		$upas = array(
			'ä' => 'ae',
			'å' => 'ae',
			'ü' => 'ue',
			'ö' => 'oe',
			'Ä' => 'Ae',
			'Ü' => 'Ue',
			'Ö' => 'Oe',
			'é' => 'e',
			'è' => 'e',
			'ê' => 'e',
			'Á' => 'A',
			'\'' => '',
			' ' => '',
			'-' => '',
			'.' => '',
		);

		return strtolower(strtr($name, $upas));
	}


	/**
	 * @return bool
	 */
	protected function isUpdateRequired() {
		return $this->props()->get(hubUserFields::F_UPDATE_LOGIN) OR $this->props()->get(hubUserFields::F_UPDATE_FIRSTNAME) OR $this->props()
			->get(hubUserFields::F_UPDATE_LASTNAME) OR $this->props()->get(hubUserFields::F_UPDATE_EMAIL) OR $this->props()
			->get(hubUserFields::F_REACTIVATE_ACCOUNT);
	}
}

?>