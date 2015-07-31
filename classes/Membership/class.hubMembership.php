<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/class.hubRepositoryObject.php');
require_once('./Modules/Course/classes/class.ilObjCourse.php');
require_once('./Services/Membership/classes/class.ilParticipants.php');
require_once('./Modules/Course/classes/class.ilCourseParticipants.php');
require_once('./Modules/Group/classes/class.ilGroupParticipants.php');
require_once('./Modules/Course/classes/class.ilCourseMembershipMailNotification.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Membership/class.hubMembershipFields.php');

/**
 * Class hubMembership
 *
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.1.04
 *
 * @revision $r$
 */
class hubMembership extends hubObject {

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
	 * @var int
	 */
	public static $id_type = self::ILIAS_ID_TYPE_ROLE;


	public function __destruct() {
		$this->members_object = NULL;
		$this->object_type = NULL;
		$this->participants = NULL;
		parent::__destruct();
	}


	/**
	 * @param $ext_id_usr
	 * @param $ext_id_container
	 *
	 * @return hubMembership
	 */
	public static function getInstance($ext_id_usr, $ext_id_container) {
		$ext_id = $ext_id_usr . self::DELIMITER . $ext_id_container;

		/**
		 * @var $hubMembership hubMembership
		 */
		$hubMembership = hubMembership::findOrGetInstance($ext_id);
		$hubMembership->setExtIdCourse($ext_id_container);
		$hubMembership->setExtIdUsr($ext_id_usr);

		return $hubMembership;
	}


	public static function buildILIASObjects() {
		/**
		 * @var $hubMembership    hubMembership
		 * @var $hubOrigin        hubOrigin
		 * @var $hubOriginObj     unibasSLCMMemberships
		 */
		$count = self::count();
		$steps = 1000;
		$step = 0;
		$hasSets = true;
		hubLog::getInstance()->write("Start building $count ILIAS objects");
		//		hubSyncCron::setDryRun(true);
		$active_origins = hubOrigin::getOriginsForUsage(hub::OBJECTTYPE_MEMBERSHIP);
		$active_origin_ids = array();
		foreach ($active_origins as $origin) {
			$active_origin_ids[] = $origin->getId();
		}
		$activeMemberships = self::where(array("sr_hub_origin_id" => $active_origin_ids));
		while ($hasSets) {
			$start = $step * $steps;
			hubLog::getInstance()->write("Start looping $steps records, round=" . ($step + 1) . ", limit=$start,$steps");
			$hubMemberships = $activeMemberships->limit($start, $steps);
			if ($hubMemberships->count() == 0) {
				$hasSets = false;
			}
			foreach ($hubMemberships->get() as $hubMembership) {
				if (!hubSyncHistory::isLoaded($hubMembership->getSrHubOriginId())) {
					continue;
				}
				$duration_id = 'obj_origin_' . $hubMembership->getSrHubOriginId();
				hubDurationLogger2::getInstance($duration_id)->resume();
				$hubOrigin = hubOrigin::getClassnameForOriginId($hubMembership->getSrHubOriginId());
				$hubOriginObj = $hubOrigin::find($hubMembership->getSrHubOriginId())->getObject();

				//			$overridden_status = $hubOriginObj->overrideStatus($hubMembership);
				//			if ($overridden_status) {
				//				$status = $overridden_status;
				//			} else {
				//			}

				$status = $hubMembership->getHistoryObject()->getStatus();
				if ($hubOriginObj->returnActivePeriod()) {
					$active_period = (string)$hubOriginObj->returnActivePeriod();
					if ($hubMembership->getPeriod() != $active_period) {
						//						 hubLog::getInstance()->write('ignored period');
						$status = hubSyncHistory::STATUS_IGNORE;
					}
				}
				//				hubLog::getInstance()->write('Status: ' . $status);
				switch ($status) {
					case hubSyncHistory::STATUS_NEW:
						hubLog::getInstance()->write('Create Membership: ' . $hubMembership->getExtId());
						if (!hubSyncCron::getDryRun()) {
							$hubMembership->createMembership();
						}
						hubCounter::incrementCreated($hubMembership->getSrHubOriginId());
						break;
					case hubSyncHistory::STATUS_UPDATED:
						//						hubLog::getInstance()->write('Update Membership: ' . $hubMembership->getExtId());
						if (!hubSyncCron::getDryRun()) {
							$hubMembership->updateMembership();
						}
						hubCounter::incrementUpdated($hubMembership->getSrHubOriginId());
						break;
					case hubSyncHistory::STATUS_DELETED:
						hubLog::getInstance()->write('Delete Membership: ' . $hubMembership->getExtId());
						hubLog::getInstance()->write('Periods: ' . $hubMembership->getPeriod() . ' | ' . $active_period);
						if (!hubSyncCron::getDryRun()) {
							$hubMembership->deleteMembership();
						}
						hubCounter::incrementDeleted($hubMembership->getSrHubOriginId());
						break;
					case hubSyncHistory::STATUS_ALREADY_DELETED:
						hubCounter::incrementIgnored($hubMembership->getSrHubOriginId());
						break;
					case hubSyncHistory::STATUS_NEWLY_DELIVERED:
						hubCounter::incrementNewlyDelivered($hubMembership->getSrHubOriginId());
						hubLog::getInstance()->write('Create newly delivered Membership: ' . $hubMembership->getExtId());
						if (!hubSyncCron::getDryRun()) {
							$hubMembership->getHistoryObject()->setAlreadyDeleted(false);
							$hubMembership->createMembership();
						}
						break;
					case hubSyncHistory::STATUS_IGNORE:
						hubCounter::incrementIgnored($hubMembership->getSrHubOriginId());
						break;
				}

				$hubMembership->getHistoryObject()->updatePickupDate();
				$hubOrigin::afterObjectModification($hubMembership);
				if (!hubSyncCron::getDryRun()) {
					$hubOriginObj->afterObjectInit($hubMembership);
					arObjectCache::purge($hubMembership->getHistoryObject());
				}
				hubDurationLogger2::getInstance($duration_id)->resume();
				arObjectCache::purge($hubMembership);
				$hubMembership = NULL;
				$hubOriginObj = NULL;
			}
			$step ++;
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
		if ($this->participants instanceof ilParticipants) {
			foreach ($this->participants->getRoles() as $role) {
				if (strpos(ilObject::_lookupTitle($role), 'il_' . $this->object_type . '_' . $appendix) === 0) {
					$this->ilias_role_id = $role;
					break;
				}
			}
		}

		if (!$this->getUsrId()) {
			//	if ($this->props()->get(hubMembershipFields::GET_USR_ID_FROM_ORIGIN)) {
			//		$where = array(
			//			'sr_hub_origin_id' => $this->props()->get(hubMembershipFields::GET_USR_ID_FROM_ORIGIN),
			//			'ext_id' => $this->getExtIdUsr()
			//		);
			//		$hubUser = hubUser::where($where)->first();
			//	} else {
			$hubUser = hubUser::find($this->getExtIdUsr());
			//	}

			/**
			 * @var $hubUser hubUser
			 */
			if ($hubUser instanceof hubUser) {
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
		if ($this->ilias_role_id > 1) {
			if ($this->getContainerRole() != NULL AND $this->getUsrId() != NULL) {
				$this->participants->add($this->getUsrId(), $this->getContainerRole());
			}
			if ($this->getHasNotification() AND $this->props()->get(hubMembershipFields::ADD_NOTIFICATION)) {
				$this->participants->updateNotification($this->getUsrId(), true);
			}
			if ($this->props()->get(hubMembershipFields::DESKTOP_NEW)) {
				ilObjUser::_addDesktopItem($this->getUsrId(), $this->getContainerId(), $this->object_type);
			}
			$this->setMembershipInactive(false);
			//			 $this->sendMails('new', ilCourseMembershipMailNotification::TYPE);
			$this->updateIliasId();
		}
	}


	public function updateMembership() {
		$this->initObject();
		if ($this->ilias_role_id > 1) {
			if ($this->props()->get(hubMembershipFields::UPDATE_ROLE)) {
				$this->initObject();
				if ($this->getContainerRole() != NULL AND $this->getUsrId() != NULL) {
					if ($this->participants->isAssigned($this->getUsrId())) {
						//						$this->participants->updateRoleAssignments($this->getUsrId(), array( $this->ilias_role_id ));
					} else {
						$this->participants->add($this->getUsrId(), $this->getContainerRole());
					}
				}
				if ($this->props()->get(hubMembershipFields::UPDATE_NOTIFICATION)) {
					$this->participants->updateNotification($this->getUsrId(), (bool)$this->getHasNotification());
				}
				if ($this->props()->get(hubMembershipFields::DESKTOP_UPDATED)) {
					ilObjUser::_addDesktopItem($this->getUsrId(), $this->getContainerId(), $this->object_type);
				}
				// $this->sendMails('updated', ilCourseMembershipMailNotification::TYPE_STATUS_CHANGED);
				$this->updateIliasId();
			}
		}
	}


	protected function deleteMembership() {
		$this->initObject();
		switch ($this->props()->get(hubMembershipFields::DELETE)) {
			case self::DELETE_MODE_INACTIVE:
				break;
			case self::DELETE_MODE_DELETE:
                if($this->participants){
                    $this->participants->delete($this->getUsrId());
                    $this->participants->updateNotification($this->getUsrId(), false);
                }
				// $this->sendMails('deleted', ilCourseMembershipMailNotification::TYPE_NOTIFICATION_REGISTRATION);
				break;
			case self::DELETE_MODE_DELETE_OR_INACTIVE:
				if ($this->hasActivities()) {
					$this->setMembershipInactive();
				} else {
					if($this->participants){
						$this->participants->delete($this->getUsrId());
						$this->participants->updateNotification($this->getUsrId(), false);
					}
				}
		}
		$history = $this->getHistoryObject();
		$history->setDeleted(true);
		$history->setAlreadyDeleted(true);
		$history->update();
	}

	protected function hasActivities() {
		global $ilDB;
		$query = $ilDB->query('SELECT * FROM catch_write_events WHERE usr_id = ' . $ilDB->quote($this->getUsrId(), 'integer'));
		if ($ilDB->numRows($query)) {
			return true;
		}
		return false;
	}

	protected function setMembershipInactive($inactive = true) {
		$participants = new ilCourseParticipants(ilObject2::_lookupObjId($this->getContainerId()));
		$participants->updateBlocked($this->getUsrId(), $inactive);
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
				$send = $this->props()->get($prefix . '_send_mail_admin');
				if (!$this->getHasNotification()) {
					$send = false;
				}
				break;
			case self::CONT_ROLE_CRS_TUTOR:
				$send = $this->props()->get($prefix . '_send_mail_tutor');
				break;
			case self::CONT_ROLE_CRS_MEMBER:
			case self::CONT_ROLE_GRP_MEMBER:
				$send = $this->props()->get($prefix . '_send_mail_member');
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
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           64
	 */
	protected $period = NULL;
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
		if (!$this->ext_id_usr && $this->ext_id) {
			$this->ext_id_usr = substr($this->ext_id, 0, strpos($this->ext_id, '###'));
		}
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


	/**
	 * @param string $period
	 */
	public function setPeriod($period) {
		$this->period = $period;
	}


	/**
	 * @return string
	 */
	public function getPeriod() {
		return $this->period;
	}
}

?>