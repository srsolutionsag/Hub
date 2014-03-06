<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/class.hub.php');

/**
 * hubSyncCron
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 *
 * @version 1.0.0
 * @revision $r$
 */
class hubSyncCron {

	public function __construct() {
		global $ilDB, $ilUser, $ilCtrl;
		/**
		 * @var $ilDB   ilDB
		 * @var $ilUser ilObjUser
		 * @var $ilCtrl ilCtrl
		 */
		$this->db = $ilDB;
		$this->user = $ilUser;
		$this->ctrl = $ilCtrl;
		$this->log = hubLog::getInstance();
	}


	public static function initAndRun() {
		self::initILIAS();
		$cronJob = new self();
		$cronJob->run();
	}


	private function sendNotification() {
		require_once('./Modules/Course/classes/class.ilCourseMembershipMailNotification.php');
		$mail = new ilCourseMembershipMailNotification();
		$mail->setRefId(68448);
		$mail->setRecipients(array( 6 ));
		$mail->setType(ilCourseMembershipMailNotification::TYPE_NOTIFICATION_REGISTRATION);
		$mail->send();
	}


	public static function initILIAS() {
		require_once(dirname(__FILE__) . '/../class.hub.php');
		chdir(Hub::getRootPath());
		require_once('./include/inc.ilias_version.php');
		require_once('./Services/Component/classes/class.ilComponent.php');
		require_once('./Services/Authentication/classes/class.ilAuthFactory.php');

		if (ilComponent::isVersionGreaterString(ILIAS_VERSION_NUMERIC, '4.2.999')) {
			include_once "Services/Context/classes/class.ilContext.php";
			ilContext::init(ilContext::CONTEXT_CRON);
			ilAuthFactory::setContext(ilAuthFactory::CONTEXT_CRON);
		} else {
			ilAuthFactory::setContext(ilAuthFactory::CONTEXT_CRON);
		}
		$_COOKIE['ilClientId'] = $_SERVER['argv'][3];
		$_POST['username'] = $_SERVER['argv'][1];
		$_POST['password'] = $_SERVER['argv'][2];
		require_once('./include/inc.header.php');

		self::includes();
	}


	private static function includes() {
		require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/class.hub.php');
		require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Origin/class.hubOrigin.php');
		require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Sync/class.hubSyncHistory.php');
		require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Category/class.hubCategory.php');
		require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Course/class.hubCourse.php');
		require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Log/class.hubDurationLogger.php');
		require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Log/class.hubLog.php');
		require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Notification/class.hubOriginException.php');
	}


	/**
	 * @throws hubOriginException
	 */
	public function run() {
		self::includes();
		try {
			$this->log->write('New Sync initiated', hubLog::L_PROD);
			$this->log->write('PHP: ' . (hub::isCli() ? 'CLI' : 'WEB'), hubLog::L_PROD);
			$this->log->write('User: ' . $this->user->getPublicName(), hubLog::L_PROD);
			// User
			$this->log->write('Sync Users', hubLog::L_PROD);
			if ($this->syncData(hub::OBJECTTYPE_USER)) {
				hubDurationLogger::start('build_users', false);
				if (hubUser::buildILIASObjects() !== true) {
					throw new hubOriginException(hubOriginException::BUILD_ILIAS_OBJECTS_FAILED, new hubOrigin(), true);
				};
				hubUser::logCounts();
				hubDurationLogger::log('build_users');
			}
			// Category
			$this->log->write('Sync Categories', hubLog::L_PROD);
			if ($this->syncData(hub::OBJECTTYPE_CATEGORY)) {
				hubDurationLogger::start('build_categories', false);
				if (hubCategory::buildILIASObjects() !== true) {
					throw new hubOriginException(hubOriginException::BUILD_ILIAS_OBJECTS_FAILED, new hubOrigin(), true);
				}
				hubCategory::logCounts();
				hubDurationLogger::log('build_categories');
			}
			// Courses
			$this->log->write('Sync Courses', hubLog::L_PROD);
			if ($this->syncData(hub::OBJECTTYPE_COURSE)) {
				hubDurationLogger::start('build_courses', false);
				if (hubCourse::buildILIASObjects() !== true) {
					throw new hubOriginException(hubOriginException::BUILD_ILIAS_OBJECTS_FAILED, new hubOrigin(), true);
				}
				hubCourse::logCounts();
				hubDurationLogger::log('build_courses');
			}
			// Memberships
			$this->log->write('Sync Memberships', hubLog::L_PROD);
			if ($this->syncData(hub::OBJECTTYPE_MEMBERSHIP)) {
				hubDurationLogger::start('build_memberships', false);
				if (hubMembership::buildILIASObjects() !== true) {
					throw new hubOriginException(hubOriginException::BUILD_ILIAS_OBJECTS_FAILED, new hubOrigin(), true);
				}
				hubMembership::logCounts();
				hubDurationLogger::log('build_memberships');
			}
		} catch (Exception $e) {
			ilUtil::sendFailure($e->getMessage(), true);
		}

		hubOrigin::sendSummaries();
	}


	/**
	 * @param $usage
	 *
	 * @return bool
	 * @throws hubOriginException
	 */
	private function syncData($usage) {
		foreach (hubOrigin::getOriginsForUsage($usage) as $origin) {
			/**
			 * @var $origin       hubOrigin
			 * @var $originObject unibasOe
			 */
			hubDurationLogger::start('overall_origin_' . $origin->getId(), false);
			$originObject = $origin->getObject();
			$this->log->write('Sync-Class: ' . get_class($originObject), hubLog::L_PROD);
			if ($originObject->connect()) {
				hubDurationLogger::start('parse_data_origin_' . $origin->getId(), false);
				if ($originObject->parseData()) {
					$data = $originObject->getData();
					if ($originObject->compareDataWithExisting(count($data))) {
						hubDurationLogger::log('parse_data_origin_' . $origin->getId());
						if ($originObject->getChecksum() === count($data) OR $originObject->getChecksum() == 0) {
							hubDurationLogger::start('build_ext_objects_origin_' . $origin->getId(), false);
							if ($originObject->buildEntries()) {
								hubDurationLogger::log('build_ext_objects_origin_' . $origin->getId());
								$time = new DateTime();
								$origin->setLastUpdate($time->format(DateTime::ISO8601));
								$origin->setDuration(hubDurationLogger::stop('overall_origin_' . $origin->getId()));
								$origin->update();
								hubDurationLogger::log('overall_origin_' . $origin->getId());
								hubDurationLogger::start('init_status_' . $origin->getId(), false);
								if (! hubSyncHistory::initStatus($origin->getId())) {
									throw new hubOriginException(hubOriginException::BUILD_ENTRIES_FAILED, $origin, true);
								}
								hubDurationLogger::log('init_status_' . $origin->getId());

								return true;
							} else {
								throw new hubOriginException(hubOriginException::BUILD_ENTRIES_FAILED, $origin, true);
							}
						} else {
							throw new hubOriginException(hubOriginException::CHECKSUM_MISMATCH, $origin, true);
						}
					} else {
						throw new hubOriginException(hubOriginException::TOO_MANY_LOST_DATASETS, $origin, true);
					}
				} else {
					throw new hubOriginException(hubOriginException::PARSE_DATA_FAILED, $origin, true);
				}
			} else {
				throw new hubOriginException(hubOriginException::CONNECTION_FAILED, $origin, true);
			}
		}

		return false;
	}
}

?>
