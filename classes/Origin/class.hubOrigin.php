<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/class.srModelObjectHubClass.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Log/class.hubLog.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Origin/class.hubOriginConfiguration.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Log/class.hubCounter.php');

/**
 * Class hubOrigin
 *
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
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
	 * @param int $id
	 */
	public function __construct($id = 0) {
		parent::__construct($id);
		$this->conf = hubOriginConfiguration::conf($this->getId());
		$this->log = hubLog::getInstance();
		$this->loadObjectProperties();
	}


	public function loadConf() {
		$this->conf = hubOriginConfiguration::conf($this->getId());
	}


	public function addSummary() {
		$created = 'Total Created: ' . hubCounter::getCountCreated($this->getId());
		hubOriginNotification::addMessage($this->getId(), $created);
		$updated = 'Total Updated: ' . hubCounter::getCountUpdated($this->getId());
		hubOriginNotification::addMessage($this->getId(), $updated);
		$deleted = 'Total Deleted: ' . hubCounter::getCountDeleted($this->getId());
		hubOriginNotification::addMessage($this->getId(), $deleted);
		$ignored = 'Total Ignored: ' . hubCounter::getCountIgnored($this->getId());
		hubOriginNotification::addMessage($this->getId(), $ignored);
	}


	public static function sendSummaries() {
		foreach (self::get() as $hubOrigin) {
			$hubOrigin->addSummary();
			hubOriginNotification::send($hubOrigin);
		}
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
		if ($obj->getClassname() == self::CLASS_NONE OR $obj->getClassname() == NULL) {

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
		return self::where(array( 'usage_type' => $usage_type_id, 'active' => true ))->get();
	}


	/**
	 * @param $amount_of_datasets
	 *
	 * @return bool
	 */
	public function compareDataWithExisting($amount_of_datasets) {
		if ($this->props()->getByKey('check_amount')) {
			/**
			 * @var $usage_class hubCourse
			 */
			$usage_class = self::getUsageClass($this->getId());
			$existing = $usage_class::where(array( 'sr_hub_origin_id' => $this->getId() ))->count();

			if ($existing == 0) {
				return true;
			}

			$percent = 100 / $existing * $amount_of_datasets;
			$percentage = $this->props()->getByKey('check_amount_percentage');
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
			require_once($this->getClassFilePath());
			$class = $this->getClassName();
			$originObject = new $class($this->getId());

			return $originObject;
		}
		if (! is_file($this->getClassFilePath()) AND $this->getClassName() != self::CLASS_NONE) {
			ilUtil::sendFailure('ClassFile ' . $this->getClassFilePath() . 'does not exist');
		}

		return $this;
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
		if (! file_exists($dir_name) AND is_writable($dir_name)) {
			mkdir($dir_name);
			chmod($dir_name, 0755);
		} else {
			return false;
		}
		if (! file_exists($this->getClassFilePath())) {
			$template = file_get_contents(self::getOriginsPath() . 'class.hubOriginTemplate.tpl');
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
		return (bool)hubConfig::get('lock') AND (bool)$this->getActive();
	}


	/**
	 * @return string
	 */
	private static function getOriginsPath() {
		return hub::getPath() . 'origins/';
	}


	/**
	 * @param $usage_type
	 *
	 * @return string
	 */
	public static function  getOriginsPathForUsageType($usage_type) {
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
		return $this->conf;
	}


	/**
	 * @return hubOriginObjectProperties
	 */
	public function props() {
		$this->loadObjectProperties();

		return $this->object_properties;
	}


	public function loadObjectProperties() {
		$this->object_properties = hubOriginObjectProperties::getInstance($this->getId());
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
	 * @param srModelObjectHubClass $hubObject
	 *
	 * @return srModelObjectHubClass
	 */
	public static function afterObjectModification(srModelObjectHubClass $hubObject) {
		return $hubObject;
	}


	//
	// Database Fields
	//
	/**
	 * @var int
	 *
	 * @db_has_field        true
	 * @db_is_unique        true
	 * @db_is_primary       true
	 * @db_is_notnull       true
	 * @db_fieldtype        integer
	 * @db_length           4
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
}

?>