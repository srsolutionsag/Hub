<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/class.hubObject.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Log/class.hubLog.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Origin/class.hubOriginConfiguration.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Log/class.hubCounter.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Configuration/class.hubConfig.php');

/**
 * Class hubOrigin
 *
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.1.04
 *
 * @revision $r$
 */
class hubOrigin extends ActiveRecord {

	const CONF_TYPE_FILE = 1;
	const CONF_TYPE_DB = 2;
	const CONF_TYPE_EXTERNAL = 3;
	const CLASS_NONE = 'none';
	/**
	 * @var int
	 */
	protected $checksum = 0;
	/**
	 * @var hubLog
	 */
	protected $log;
	/**
	 * @var hubOriginObjectProperties
	 */
	protected $object_properties;
	/**
	 * @var bool
	 */
	protected $ar_safe_read = false;


	public function __destruct() {
		$this->object_properties = null;
		$this->log = null;
		$this->conf = null;
	}


	/**
	 * @param int $id
	 */
	public function __construct($id = 0) {
		parent::__construct($id);
		$this->loadConf();
		$this->loadProps();
		$this->log = hubLog::getInstance();
	}


	public function addSummary() {
		hubOriginNotification::addMessage($this->getId(), 'Duration read Data: ' . $this->getDuration(), 'Durations');
		hubOriginNotification::addMessage($this->getId(), 'Duration build Objects: ' . $this->getDurationObjects(), 'Durations');

		$created = 'Total Created: ' . hubCounter::getCountCreated($this->getId());
		hubOriginNotification::addMessage($this->getId(), $created);
		$updated = 'Total Updated: ' . hubCounter::getCountUpdated($this->getId());
		hubOriginNotification::addMessage($this->getId(), $updated);
		$deleted = 'Total Deleted: ' . hubCounter::getCountDeleted($this->getId());
		hubOriginNotification::addMessage($this->getId(), $deleted);
		$ignored = 'Total Ignored: ' . hubCounter::getCountIgnored($this->getId());
		hubOriginNotification::addMessage($this->getId(), $ignored);
		$newly_delivered = 'Total Newly Delivered: ' . hubCounter::getCountNewlyDelivered($this->getId());
		hubOriginNotification::addMessage($this->getId(), $newly_delivered);
	}


	public static function sendSummaries() {
		foreach (self::get() as $hubOrigin) {
			$hubDurationLogger = hubDurationLogger2::getInstance('obj_origin_' . $hubOrigin->getId());
			$hubDurationLogger->stop();
			if ($duration = $hubDurationLogger->get()) {
				$hubOrigin->setDurationObjects($duration);
				$hubOrigin->update();
			}
			$hubOrigin->sendSummary();
		}
	}


	/**
	 * @return null
	 */
	public function returnActivePeriod() {
		return null;
	}


	public function sendSummary() {
		$this->addSummary();
		hubOriginNotification::send($this);
	}


	/**
	 * @return bool
	 */
	public function supportsIcons() {
		return hub::supportsIcons($this->getUsageType());
	}


	/**
	 * @return hubOrigin[]
	 */
	public static function get() {
		return parent::get();
	}


	/**
	 * @param $ext_id
	 *
	 * @return hubOrigin
	 */
	public static function find($ext_id) {
		return parent::find($ext_id);
	}


	/**
	 * @param $sr_hub_origin_id
	 *
	 * @return string
	 */
	public static function getUsageClass($sr_hub_origin_id) {
		/**
		 * @var $obj hubOrigin
		 */
		$obj = self::find($sr_hub_origin_id);

		return hub::getObjectClassname($obj->getUsageType());
	}


	/**
	 * @param $sr_hub_origin_id
	 *
	 * @return string
	 */
	public static function getClassnameForOriginId($sr_hub_origin_id) {
		/**
		 * @var $obj hubOrigin
		 */
		$obj = self::find($sr_hub_origin_id);
		if ($obj->getClassname() == self::CLASS_NONE OR $obj->getClassname() == null) {
			return 'hubOrigin';
		} else {
			return $obj->getClassname();
		}
	}


	/**
	 * @param $usage_type_id
	 *
	 * @return array
	 */
	public static function getOriginsForUsage($usage_type_id) {
		/**
		 * @var $origin hubOrigin
		 */
		$origins = self::where(array( 'usage_type' => $usage_type_id, 'active' => true ))->get();
		foreach ($origins as $key => $origin) {
			if ($origin->isAsleep()) {
				unset($origins[$key]);
			}
		}

		return $origins;
	}


	/**
	 * @param $amount_of_datasets
	 *
	 * @return bool
	 */
	public function compareDataWithExisting($amount_of_datasets) {
		if ($this->props()->get('check_amount')) {
			/**
			 * @var $usage_class hubCourse
			 */
			$usage_class = self::getUsageClass($this->getId());
			$existing = $usage_class::where(array( 'sr_hub_origin_id' => $this->getId() ))->count();
			if ($existing == 0) {
				return true;
			}
			$percent = 100 / $existing * $amount_of_datasets;
			$percentage = $this->props()->get('check_amount_percentage');
			$percentage = $percentage ? $percentage : 80;

			return ($percent >= $percentage ? true : false);
		} else {
			return true;
		}
	}


	/**
	 * @return unibasSLCM
	 */
	public function getObject() {
		if ($this->getClassFilePath() AND is_file($this->getClassFilePath())) {
			if (!$this->originObject) {
				require_once($this->getClassFilePath());
				$class = $this->getClassName();
				$this->originObject = new $class($this->getId());
			}

			return $this->originObject;
		}
		if (!is_file($this->getClassFilePath()) AND $this->getClassName() != self::CLASS_NONE) {
			$this->buildFoldersAndFiles();
			hub::sendFailure('ClassFile ' . $this->getClassFilePath() . ' does not exist');
		}

		return $this;
	}


	/**
	 * @return bool
	 */
	public function afterSync() {
		return true;
	}


	/**
	 * @return int
	 */
	public function getCountOfHubObjects() {
		/**
		 * @var $hubObject hubCourse
		 */
		$hubObject = self::getUsageClass($this->getId());

		return $hubObject::where(array( 'sr_hub_origin_id' => $this->getId() ))->count();
	}


	//
	// Creation
	//
	public function update() {
		parent::update();
		$this->conf->update();
	}


	public function create() {
		$this->buildFoldersAndFiles();
		parent::create();
		$this->conf->setSrHubOriginId($this->getId());
		$this->conf->create();
	}


	public function delete() {
		$this->deleteFoldersAndFiles();
		$this->conf->delete();
		$this->object_properties->delete();
		parent::delete();
	}


	//
	// Folders & Files
	//
	/**
	 * @return bool
	 */
	private function buildFoldersAndFiles() {
		$dir_name = self::getOriginsPathForUsageType($this->getUsageType()) . $this->getClassName();

		if (!file_exists($dir_name)) {
			$ret = ilUtil::makeDirParents($dir_name);
			//			var_dump($ret); // FSX
			//			mkdir($dir_name);
			chmod($dir_name, 0755);
		} else {
			return false;
		}
		if (!file_exists($this->getClassFilePath())) {
			$template = file_get_contents('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/origins/class.hubOriginTemplate.tpl');
			$template = sprintf($template, hub::getObjectClassname($this->getUsageType()), $this->getClassName());
			file_put_contents($this->getClassFilePath(), $template);
			chmod($this->getClassFilePath(), 0755);
		}
	}


	/**
	 * @return bool
	 */
	private function deleteFoldersAndFiles() {
		$dir_name = self::getOriginsPathForUsageType($this->getUsageType()) . $this->getClassName();
		system('rm -rf ' . escapeshellarg($dir_name), $retval);

		return $retval == 0;
	}


	/**
	 * @return string
	 */
	private function getClassFilePath() {
		if ($this->getClassName()) {
			return $this->getClassPath() . '/class.' . $this->getClassName() . '.php';
		} else {
			return false;
		}
	}


	/**
	 * @return string
	 */
	public function getClassPath() {
		if ($this->getClassName()) {
			return self::getOriginsPathForUsageType($this->getUsageType()) . $this->getClassName();
		} else {
			return false;
		}
	}


	/**
	 * @return bool
	 */
	public function isLocked() {
		return (bool)hubConfig::get(hubConfig::F_LOCK) AND (bool)$this->getActive();
	}


	/**
	 * @return string
	 */
	private static function getOriginsPath() {
		if (hubConfig::get(hubConfig::F_ORIGINS_PATH)) {
			return rtrim(hubConfig::get(hubConfig::F_ORIGINS_PATH), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		} else {
			return hub::getPath() . 'origins/';
		}
	}


	/**
	 * @param $usage_type
	 *
	 * @return string
	 */
	public static function getOriginsPathForUsageType($usage_type) {
		return self::getOriginsPath() . hub::getObjectClassname($usage_type) . '/';
	}


	/**
	 * @var array
	 */
	protected $data = array();
	/**
	 * @var hubOriginConfiguration
	 */
	protected $conf;


	/**
	 * @param array $data
	 */
	public function setData(array $data) {
		$this->data = $data;
	}


	/**
	 * @return hubOriginConfiguration
	 */
	public function conf() {
		$this->loadConf();

		return $this->conf;
	}


	public function loadConf() {
		if (!isset($this->conf) OR $this->conf->getSrHubOriginId() != $this->getId()) {
			$this->conf = hubOriginConfiguration::conf($this->getId());
		}
	}


	/**
	 * @return hubOriginObjectProperties
	 */
	public function props() {
		$this->loadProps();

		return $this->object_properties;
	}


	public function loadProps() {
		if (!isset($this->object_properties) OR $this->object_properties->getSrHubOriginId() != $this->getId()) {
			$this->object_properties = hubOriginObjectProperties::getInstance($this->getId());
		}
	}


	/**
	 * This method is executed after the ILIAS object is initialized
	 *
	 * @param hubObject $hub_object
	 *
	 * @return \hubObject
	 */
	public function afterObjectInit(hubObject $hub_object) {
		return $hub_object;
	}


	/**
	 * This method is executed after the ILIAS object is created
	 *
	 * @param hubObject $hub_object
	 *
	 * @return \hubObject
	 */
	public function afterObjectCreation(hubObject $hub_object) {
		return $hub_object;
	}


	/**
	 * This method is executed after the ILIAS object is updated
	 *
	 * @param hubObject $hub_object
	 *
	 * @return \hubObject
	 */
	public function afterObjectUpdate(hubObject $hub_object) {
		return $hub_object;
	}


	/**
	 * This method is executed after the ILIAS object is deleted
	 *
	 * @param hubObject $hub_object
	 *
	 * @return \hubObject
	 */
	public function afterObjectDeletion(hubObject $hub_object) {
		return $hub_object;
	}


	/**
	 * @param hubObject $hub_object
	 *
	 * @is executet when getting status of object
	 *
	 * @return bool
	 */
	public function overrideStatus(hubObject $hub_object) {
		return false;
	}


	/**
	 * @param ilPropertyFormGUI $form_gui
	 *
	 * @return ilPropertyFormGUI
	 */
	public static function appendFieldsToPropForm(ilPropertyFormGUI $form_gui) {
		return $form_gui;
	}


	/**
	 * @param hubObject $hub_object
	 *
	 * @deprecated Use non-static afterObjectInit method
	 * @return hubObject
	 */
	public static function afterObjectModification(hubObject $hub_object) {
		return $hub_object;
	}


	//
	// Database Fields
	//
	/**
	 * @var int
	 *
	 * @db_has_field          true
	 * @db_is_unique          true
	 * @db_is_primary         true
	 * @db_is_notnull         true
	 * @db_fieldtype          integer
	 * @db_length             4
	 * @db_sequence           true
	 */
	protected $id = 0;
	/**
	 * @var string
	 *
	 * @db_has_field           true
	 * @db_is_notnull          true
	 * @db_fieldtype           integer
	 * @db_length              4
	 */
	protected $conf_type = self::CONF_TYPE_FILE;
	/**
	 * @var string
	 *
	 * @db_has_field           false
	 * @db_is_notnull          true
	 * @db_fieldtype           text
	 * @db_length              1024
	 */
	protected $matching_key_origin;
	/**
	 * @var string
	 *
	 * @db_has_field           false
	 * @db_is_notnull          true
	 * @db_fieldtype           text
	 * @db_length              1024
	 */
	protected $matching_key_ilias;
	/**
	 * @var string
	 *
	 * @db_has_field           true
	 * @db_is_notnull          true
	 * @db_fieldtype           integer
	 * @db_length              1
	 */
	protected $usage_type = hub::OBJECTTYPE_CATEGORY;
	/**
	 * @var bool
	 *
	 * @db_has_field           true
	 * @db_fieldtype           integer
	 * @db_length              1
	 */
	protected $active = 0;
	/**
	 * @var string
	 *
	 * @db_has_field           true
	 * @db_is_notnull          true
	 * @db_fieldtype           text
	 * @db_length              2048
	 */
	protected $title;
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           2048
	 */
	protected $description;
	/**
	 * @var string
	 *
	 * @db_has_field           true
	 * @db_fieldtype           text
	 * @db_length              256
	 * @db_is_notnull          true
	 */
	protected $class_name = self::CLASS_NONE;
	/**
	 * @var DateTime
	 *
	 * @db_has_field           true
	 * @db_fieldtype           timestamp
	 */
	protected $last_update;
	/**
	 * @var int
	 *
	 * @db_has_field        true
	 * @db_fieldtype        integer
	 * @db_length           8
	 */
	protected $duration = 0;
	/**
	 * @var int
	 *
	 * @db_has_field        true
	 * @db_fieldtype        integer
	 * @db_length           8
	 */
	protected $duration_objects = 0;


	/**
	 * @return string
	 */
	static function returnDbTableName() {
		return 'sr_hub_origin';
	}


	//
	// Setter & Getter
	//
	/**
	 * @param int $id
	 */
	public function setId($id) {
		$this->id = $id;
	}


	/**
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}


	/**
	 * @param boolean $active
	 */
	public function setActive($active) {
		$this->active = $active;
	}


	/**
	 * @return boolean
	 */
	public function getActive() {
		return $this->active;
	}


	/**
	 * @param string $class_name
	 */
	public function setClassName($class_name) {
		$this->class_name = $class_name;
	}


	/**
	 * @return string
	 */
	public function getClassName() {
		return $this->class_name;
	}


	/**
	 * @param string $conf_type
	 */
	public function setConfType($conf_type) {
		$this->conf_type = $conf_type;
	}


	/**
	 * @return string
	 */
	public function getConfType() {
		return $this->conf_type;
	}


	/**
	 * @param string $description
	 */
	public function setDescription($description) {
		$this->description = $description;
	}


	/**
	 * @return string
	 */
	public function getDescription() {
		return $this->description;
	}


	/**
	 * @return string
	 */
	public function getShortDescription() {
		if (count($this->description) > 70) {
			$pos = strpos($this->description, ' ', 50);

			return substr($this->description, 0, $pos) . ' ...';
		} else {
			return $this->description;
		}
	}


	/**
	 * @param \DateTime $last_update
	 */
	public function setLastUpdate($last_update) {
		$this->last_update = $last_update;
	}


	/**
	 * @return \DateTime
	 */
	public function getLastUpdate() {
		return $this->last_update;
	}


	/**
	 * @param string $matching_key_ilias
	 */
	public function setMatchingKeyIlias($matching_key_ilias) {
		$this->matching_key_ilias = $matching_key_ilias;
	}


	/**
	 * @return string
	 */
	public function getMatchingKeyIlias() {
		return $this->matching_key_ilias;
	}


	/**
	 * @param string $matching_key_origin
	 */
	public function setMatchingKeyOrigin($matching_key_origin) {
		$this->matching_key_origin = $matching_key_origin;
	}


	/**
	 * @return string
	 */
	public function getMatchingKeyOrigin() {
		return $this->matching_key_origin;
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
	 * @param string $usage_type
	 */
	public function setUsageType($usage_type) {
		$this->usage_type = $usage_type;
	}


	/**
	 * @return string
	 */
	public function getUsageType() {
		return $this->usage_type;
	}


	/**
	 * @param int $checksum
	 */
	public function setChecksum($checksum) {
		$this->checksum = $checksum;
	}


	/**
	 * @param int $duration
	 */
	public function setDuration($duration) {
		$this->duration = $duration;
	}


	/**
	 * @return int
	 */
	public function getDuration() {
		return $this->duration;
	}


	/**
	 * @return int
	 * @description read Checksum of your Data and return int Count
	 */
	public function getChecksum() {
		return $this->checksum;
	}


	/**
	 * @return array
	 * @description return array of Data
	 */
	public function getData() {
		return $this->data;
	}


	/**
	 * @param int $duration_objects
	 */
	public function setDurationObjects($duration_objects) {
		$this->duration_objects = $duration_objects;
	}


	/**
	 * @return int
	 */
	public function getDurationObjects() {
		return $this->duration_objects;
	}


	/**
	 * @return bool
	 * @description checks if the current time is in the origin's execution time (if cofigured)
	 */
	public function isAsleep() {
		if ($this->conf()->getExecTime() && $this->conf()->getExecTime() != date("H:i")) {
			return true;
		}

		return false;
	}
}

?>