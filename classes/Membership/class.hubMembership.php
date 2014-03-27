<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/class.srModelObjectRepositoryObject.php');
require_once('./Modules/Course/classes/class.ilObjCourse.php');
require_once('./Services/Membership/classes/class.ilParticipants.php');
require_once('./Modules/Course/classes/class.ilCourseParticipants.php');
require_once('./Modules/Group/classes/class.ilGroupParticipants.php');
require_once('./Modules/Course/classes/class.ilCourseMembershipMailNotification.php');

/**
 * Class hubMembership
 *
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 *
 * @revision $r$
 */
class hubMembership extends srModelObjectHubClass {

	const DELIMITER = '###';
	const CONT_ROLE_CRS_ADMIN = 1;
	const CONT_ROLE_CRS_MEMBER = 2;
	const CONT_ROLE_CRS_TUTOR = 3;
	const CONT_ROLE_GRP_ADMIN = IL_GRP_ADMIN; // 4
	const CONT_ROLE_GRP_MEMBER = IL_GRP_MEMBER; // 5
	/**
	 * @var ilCourseParticipant
	 */
	public $members_object;
	/**
	 * @var ilObjCourse
	 */
	public $crs;
	/**
	 * @var ilCourseParticipants
	 */
	public $participants;
	/**
	 * @var int
	 */
	protected $ilias_role_id = 1;
	/**
	 * @var string
	 */
	protected $object_type = '';


	/**
	 * @param $ext_id_usr
	 * @param $ext_id_container
	 *
	 * @return hubMembership
	 */
	public static function getInstance($ext_id_usr, $ext_id_container) {
		$ext_id = implode(self::DELIMITER, array( $ext_id_usr, $ext_id_container ));
		$hub = new hubMembership($ext_id);
		$hub->setExtIdCourse($ext_id_container);
		$hub->setExtIdUsr($ext_id_usr);

		return $hub;
	}


	public static function buildILIASObjects() {
		/**
		 * @var $hubMembership hubMembership
		 */
		foreach (self::get() as $hubMembership) {
			if (! hubSyncHistory::isLoaded($hubMembership->getSrHubOriginId())) {
				continue;
			}
			hubCounter::logBuilding();
			$hubMembership->loadObjectProperties();
			switch ($hubMembership->getHistoryObject()->getStatus()) {
				case hubSyncHistory::STATUS_NEW:
					$hubMembership->createMembership();
					hubCounter::incrementCreated($hubMembership->getSrHubOriginId());
					break;
				case hubSyncHistory::STATUS_UPDATED:
					$hubMembership->updateMembership();
					hubCounter::incrementUpdated($hubMembership->getSrHubOriginId());
					break;
				case hubSyncHistory::STATUS_DELETED:
					$hubMembership->deleteMembership();
					hubCounter::incrementDeleted($hubMembership->getSrHubOriginId());
					break;
				case hubSyncHistory::STATUS_ALREADY_DELETED:
					hubCounter::incrementIgnored($hubMembership->getSrHubOriginId());
					break;
			}
			$hubMembership->getHistoryObject()->updatePickupDate();
			$hubOrigin = hubOrigin::getClassnameForOriginId($hubMembership->getSrHubOriginId());
			$hubOrigin::afterObjectModification($hubMembership);
		}

		return true;
	}


	/**
	 * @return bool
	 */
	protected function initObject() {
		switch ($this->getContainerRole()) {
			case self::CONT_ROLE_CRS_ADMIN:
			case self::CONT_ROLE_GRP_ADMIN:
				$appendix = 'admin';
				break;
			case self::CONT_ROLE_CRS_TUTOR:
				$appendix = 'tutor';
				break;
			case self::CONT_ROLE_CRS_MEMBER:
			case self::CONT_ROLE_GRP_MEMBER:
			default:
				$appendix = 'member';
				break;
		}
		$this->object_type = ilObject2::_lookupType($this->getContainerId(), true);
		switch ($this->object_type) {
			case 'crs':
				$this->participants = new ilCourseParticipants(ilObject2::_lookupObjId($this->getContainerId()));
				break;
			case 'grp':
				$this->participants = new ilGroupParticipants(ilObject2::_lookupObjId($this->getContainerId()));
				break;
		}

		foreach ($this->participants->getRoles() as $role) {
			if (strpos(ilObject::_lookupTitle($role), 'il_' . $this->object_type . '_' . $appendix) === 0) {
				$this->ilias_role_id = $role;
				break;
			}
		}

		if (! $this->getUsrId()) {
			if ($this->props()->getByKey('get_usr_id_from_origin')) {
				$where = array(
					'sr_hub_origin_id' => $this->props()->getByKey('get_usr_id_from_origin'),
					'ext_id' => $this->getExtIdUsr()
				);
				$hubUser = hubUser::where($where)->first();
			} else {
				$hubUser = hubUser::find($this->getExtIdUsr());
			}

			/**
			 * @var $hubUser hubUser
			 */
			if ($hubUser) {
				$usr_id = $hubUser->getHistoryObject()->getIliasId();
				if ($usr_id) {
					$this->setUsrId($usr_id);
				}
			}
		}
	}


	public function destroyObject() {
		unset($this->participants);
	}


	protected function updateIliasId() {
		$history = $this->getHistoryObject();
		$history->setIliasId($this->ilias_role_id);
		$history->setIliasIdType(self::ILIAS_ID_TYPE_ROLE);
		$history->update();
	}


	public function createMembership() {
		$this->initObject();
		if ($this->ilias_role_id) {

			if ($this->getContainerRole() != NULL AND $this->getUsrId() != NULL) {
				$this->participants->add($this->getUsrId(), $this->getContainerRole());
			}
			if ($this->getHasNotification() AND $this->object_properties->getAddNotification()) {
				$this->participants->updateNotification($this->getUsrId(), true);
			}
			if ($this->object_properties->getDesktopNew()) {
				ilObjUser::_addDesktopItem($this->getUsrId(), $this->getContainerId(), $this->object_type);
			}
			$this->sendMails('new', ilCourseMembershipMailNotification::TYPE_NOTIFICATION_REGISTRATION);
			$this->updateIliasId();
		}
	}


	public function updateMembership() {
		if ($this->ilias_role_id) {
			if ($this->object_properties->getUpdateRole()) {
				$this->initObject();
				if ($this->getContainerRole() != NULL AND $this->getUsrId() != NULL
				) {
					$this->participants->add($this->getUsrId(), $this->getContainerRole());
				}
				if ($this->getHasNotification() AND $this->object_properties->getUpdateNotification()) {
					$this->participants->updateNotification($this->getUsrId(), true);
				}
				if ($this->object_properties->getDesktopUpdated()) {
					ilObjUser::_addDesktopItem($this->getUsrId(), $this->getContainerId(), $this->object_type);
				}
				$this->sendMails('updated', ilCourseMembershipMailNotification::TYPE_STATUS_CHANGED);
				$this->updateIliasId();
			}
		}
	}


	protected function deleteMembership() {
		$this->initObject();
		switch ($this->object_properties->getByKey('delete')) {
			case self::DELETE_MODE_INACTIVE:
				break;
			case self::DELETE_MODE_DELETE:
				$this->participants->delete($this->getUsrId());
				$this->sendMails('deleted', ilCourseMembershipMailNotification::TYPE_NOTIFICATION_REGISTRATION);
				break;
		}
		$history = $this->getHistoryObject();
		$history->setAlreadyDeleted(true);
		$history->update();
	}


	/**
	 * @param $prefix
	 * @param $type
	 */
	protected function sendMails($prefix, $type) {
		$send = false;
		switch ($this->getContainerRole()) {
			case self::CONT_ROLE_CRS_ADMIN:
			case self::CONT_ROLE_GRP_ADMIN:
				$send = $this->object_properties->getByKey($prefix . '_send_mail_admin');
				if (! $this->getHasNotification()) {
					$send = false;
				}
				break;
			case self::CONT_ROLE_CRS_TUTOR:
				$send = $this->object_properties->getByKey($prefix . '_send_mail_tutor');
				break;
			case self::CONT_ROLE_CRS_MEMBER:
			case self::CONT_ROLE_GRP_MEMBER:
				$send = $this->object_properties->getByKey($prefix . '_send_mail_member');
				break;
		}
		if ($send) {
			$mail = new ilCourseMembershipMailNotification();
			$mail->setRefId($this->getContainerId());
			$mail->setRecipients(array( $this->getUsrId() ));
			$mail->setType($type);
			$mail->send();
		}
	}

	//
	// Fields
	//
	/**
	 * @var string
	 */
	protected $ext_id_usr;
	/**
	 * @var string
	 */
	protected $ext_id_course;
	/**
	 * @var int
	 *
	 * @db_has_field        true
	 * @db_fieldtype        integer
	 * @db_length           2
	 */
	protected $container_role = self::CONT_ROLE_CRS_MEMBER;
	/**
	 * @var int
	 *
	 * @db_has_field        true
	 * @db_fieldtype        integer
	 * @db_length           8
	 */
	protected $usr_id;
	/**
	 * @var int
	 *
	 * @db_has_field        true
	 * @db_fieldtype        integer
	 * @db_length           8
	 */
	protected $container_id;
	/**
	 * @var bool
	 *
	 * @db_has_field        true
	 * @db_fieldtype        integer
	 * @db_length           1
	 */
	protected $has_notification = false;


	//
	// Setter & Getter
	//
	/**
	 * @param int $container_id
	 */
	public function setContainerId($container_id) {
		$this->container_id = $container_id;
	}


	/**
	 * @return int
	 */
	public function getContainerId() {
		return $this->container_id;
	}


	/**
	 * @param int $container_role
	 */
	public function setContainerRole($container_role) {
		$this->container_role = $container_role;
	}


	/**
	 * @return int
	 */
	public function getContainerRole() {
		return $this->container_role;
	}


	/**
	 * @param int $usr_id
	 */
	public function setUsrId($usr_id) {
		$this->usr_id = $usr_id;
	}


	/**
	 * @return int
	 */
	public function getUsrId() {
		return $this->usr_id;
	}


	/**
	 * @param string $ext_id_usr
	 */
	public function setExtIdUsr($ext_id_usr) {
		$this->ext_id_usr = $ext_id_usr;
	}


	/**
	 * @return string
	 */
	public function getExtIdUsr() {
		return $this->ext_id_usr;
	}


	/**
	 * @param string $ext_id_course
	 */
	public function setExtIdCourse($ext_id_course) {
		$this->ext_id_course = $ext_id_course;
	}


	/**
	 * @return string
	 */
	public function getExtIdCourse() {
		return $this->ext_id_course;
	}


	/**
	 * @return string
	 */
	static function returnDbTableName() {
		return 'sr_hub_membership';
	}


	/**
	 * @param boolean $has_notification
	 */
	public function setHasNotification($has_notification) {
		$this->has_notification = $has_notification;
	}


	/**
	 * @return boolean
	 */
	public function getHasNotification() {
		return $this->has_notification;
	}
}

?>