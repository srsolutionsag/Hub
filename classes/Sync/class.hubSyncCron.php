<?php


/**
 * hubSyncCron
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 *
 * @version 1.1.02
 * @revision $r$
 */
class hubSyncCron {

	/**
	 * @var int|bool
	 */
	protected $origin_id;
	/**
	 * @var array
	 */
	protected $messages = array();
	/**
	 * @var bool
	 */
	public static $dry_run = false;


	public function __construct() {
		global $ilUser, $ilCtrl;
		/**
		 * @var $ilDB   ilDB
		 * @var $ilUser ilObjUser
		 * @var $ilCtrl ilCtrl
		 */
		$this->user = $ilUser;
		$this->ctrl = $ilCtrl;
		$this->origin_id = $_SERVER['argv'][4] ? $_SERVER['argv'][4] : false;
		$this->log = hubLog::getInstance();
	}


	public static function initAndRun() {
		self::initILIAS();
		$cronJob = new self();
		if ($cronJob->origin_id) {
			$cronJob->runSingleOrigin();
		} else {
			$cronJob->run();
		}
	}


	public static function initILIAS() {
		require_once(dirname(__FILE__) . '/../class.hub.php');
		chdir(Hub::getRootPath());
		require_once('./Services/Authentication/classes/class.ilAuthFactory.php');
		ilAuthFactory::setContext(ilAuthFactory::CONTEXT_CRON);
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


	public function runSingleOrigin() {
		self::includes();
		$this->log->write('New Sync initiated', hubLog::L_PROD);
		$this->log->write('PHP: ' . (hub::isCli() ? 'CLI' : 'WEB'), hubLog::L_PROD);
		$this->log->write('User: ' . $this->user->getPublicName(), hubLog::L_PROD);
		// User
		$this->log->write('Sync single Origin: ' . $this->origin_id, hubLog::L_PROD);
		try {
			$origin = hubOrigin::find($this->origin_id);
			if ($this->syncOrigin($origin)) {
				$class = hubOrigin::getUsageClass($this->origin_id);
				if ($class::buildILIASObjects() !== true) {
					throw new hubOriginException(hubOriginException::BUILD_ILIAS_OBJECTS_FAILED, $origin, ! self::getDryRun());
				};
				$class::logCounts();
			}
		} catch (Exception $e) {
			$this->messages[] = $e->getMessage();
		}
		$this->handleMessages();
	}


	/**
	 * @throws hubOriginException
	 */
	public function run() {
		self::includes();
		$this->log->write('New Sync initiated', hubLog::L_PROD);
		$this->log->write('PHP: ' . (hub::isCli() ? 'CLI' : 'WEB'), hubLog::L_PROD);
		$this->log->write('User: ' . $this->user->getPublicName(), hubLog::L_PROD);
		// User
		$this->log->write('Sync Users', hubLog::L_PROD);
		try {
			if ($this->syncUsageType(hub::OBJECTTYPE_USER)) {
				hubDurationLogger::start('build_users', false);
				$class = hub::getObjectClassname(hub::OBJECTTYPE_USER);
				if (hubUser::buildILIASObjects() !== true) {
					throw new hubOriginException(hubOriginException::BUILD_ILIAS_OBJECTS_FAILED, new hubOrigin(), ! self::getDryRun());
				};
				hubUser::logCounts();
				hubDurationLogger::log('build_users');
			}
		} catch (Exception $e) {
			$this->messages[] = $e->getMessage();
		}
		// Category
		$this->log->write('Sync Categories', hubLog::L_PROD);
		try {
			if ($this->syncUsageType(hub::OBJECTTYPE_CATEGORY)) {
				hubDurationLogger::start('build_categories', false);
				if (hubCategory::buildILIASObjects() !== true) {
					throw new hubOriginException(hubOriginException::BUILD_ILIAS_OBJECTS_FAILED, new hubOrigin(), ! self::getDryRun());
				}
				hubCategory::logCounts();
				hubDurationLogger::log('build_categories');
			}
		} catch (Exception $e) {
			$this->messages[] = $e->getMessage();
		}
		// Courses
		$this->log->write('Sync Courses', hubLog::L_PROD);
		try {
			if ($this->syncUsageType(hub::OBJECTTYPE_COURSE)) {
				hubDurationLogger::start('build_courses', false);
				if (hubCourse::buildILIASObjects() !== true) {
					throw new hubOriginException(hubOriginException::BUILD_ILIAS_OBJECTS_FAILED, new hubOrigin(), ! self::getDryRun());
				}
				hubCourse::logCounts();
				hubDurationLogger::log('build_courses');
			}
		} catch (Exception $e) {
			$this->messages[] = $e->getMessage();
		}
		// Memberships
		$this->log->write('Sync Memberships', hubLog::L_PROD);
		try {
			if ($this->syncUsageType(hub::OBJECTTYPE_MEMBERSHIP)) {
				hubDurationLogger::start('build_memberships', false);
				if (hubMembership::buildILIASObjects() !== true) {
					throw new hubOriginException(hubOriginException::BUILD_ILIAS_OBJECTS_FAILED, new hubOrigin(), ! self::getDryRun());
				}
				hubMembership::logCounts();
				hubDurationLogger::log('build_memberships');
			}
		} catch (Exception $e) {
			$this->messages[] = $e->getMessage();
		}
		$this->handleMessages();
	}


	/**
	 * @param $usage
	 *
	 * @return bool
	 * @throws hubOriginException
	 */
	private function syncUsageType($usage) {
		$failed = 0;
		$originsForUsage = hubOrigin::getOriginsForUsage($usage);
		if (count($originsForUsage) == 0) {
			return false;
		}
		foreach ($originsForUsage as $origin) {
			/**
			 * @var $origin       hubOrigin
			 * @var $originObject hubOrigin
			 */
			if (! $this->syncOrigin($origin)) {
				$failed ++;
			}
		}
		if ($failed > 0) {
			return false;
		} else {
			return true;
		}
	}


	/**
	 * @param $origin
	 *
	 * @return bool
	 * @throws hubOriginException
	 */
	private function syncOrigin(hubOrigin $origin) {
		/**
		 * @var $originObject hubOrigin
		 */
		try {
			hubDurationLogger::start('overall_origin_' . $origin->getId(), false);
			$originObject = $origin->getObject();
			if ($origin->getConfType() == hubOrigin::CONF_TYPE_EXTERNAL) {
				$this->writeLastUpdate($origin);
				if (! hubSyncHistory::initStatus($origin->getId())) {
					throw new hubOriginException(hubOriginException::BUILD_ENTRIES_FAILED, $origin, true);
				}

				return true;
			}
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
								$this->writeLastUpdate($origin);
								$originObject->afterSync();
								hubDurationLogger::start('init_status_' . $origin->getId(), false);
								if (! hubSyncHistory::initStatus($origin->getId())) {
									throw new hubOriginException(hubOriginException::BUILD_ENTRIES_FAILED, $origin, ! self::getDryRun());
								}
								hubDurationLogger::log('init_status_' . $origin->getId());

								return true;
							} else {
								throw new hubOriginException(hubOriginException::BUILD_ENTRIES_FAILED, $origin, ! self::getDryRun());
							}
						} else {
							throw new hubOriginException(hubOriginException::CHECKSUM_MISMATCH, $origin, ! self::getDryRun());
						}
					} else {
						$percentage = $originObject->props()->get(hubOriginObjectPropertiesFields::CHECK_AMOUNT_PERCENTAGE) . '%';
						throw new hubOriginException(hubOriginException::TOO_MANY_LOST_DATASETS, $origin, ! self::getDryRun(), $percentage);
					}
				} else {
					throw new hubOriginException(hubOriginException::PARSE_DATA_FAILED, $origin, ! self::getDryRun());
				}
			} else {
				throw new hubOriginException(hubOriginException::CONNECTION_FAILED, $origin, ! self::getDryRun());
			}
		} catch (Exception $e) {
			$this->messages[] = $e->getMessage();
		}
	}


	private function handleMessages() {
		if (count($this->messages) > 0) {
			ilUtil::sendFailure(implode('<br>', $this->messages), true);
		}
		hubOrigin::sendSummaries();
		if (self::getDryRun() OR ! hub::isCli()) {
			ilUtil::sendInfo(hubOriginNotification::getSummaryString(), false);
		}
	}


	/**
	 * @param hubOrigin $origin
	 */
	private function writeLastUpdate(hubOrigin $origin) {
		$time = new DateTime();
		$origin->setLastUpdate($time->format(DateTime::ISO8601));
		$origin->setDuration(hubDurationLogger::stop('overall_origin_' . $origin->getId()));
		hubDurationLogger::log('overall_origin_' . $origin->getId());
		if (! self::getDryRun()) {
			$origin->update();
		}
	}


	/**
	 * @param boolean $dryrun
	 */
	public static function setDryRun($dryrun) {
		self::$dry_run = $dryrun;
	}


	/**
	 * @return boolean
	 */
	public static function getDryRun() {
		return self::$dry_run;
	}
}

?>
