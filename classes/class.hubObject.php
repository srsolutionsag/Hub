<?php
require_once('./Services/Database/classes/class.ilDBMySQL.php');
require_once('./Customizing/global/plugins/Libraries/ActiveRecord/class.ActiveRecord.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/OriginProperties/class.hubOriginObjectProperties.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Log/class.hubLog.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/class.ilHubPlugin.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Notification/class.hubOriginNotification.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Log/class.hubCounter.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Connector/class.hubConnector.php');

/**
 * Class hubObject
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.1.04
 */
abstract class hubObject extends ActiveRecord {

	const MODULO = 3000;
	const IMPORT_PREFIX = 'srhub_';
	const ILIAS_ID_TYPE_USER = 1;
	const ILIAS_ID_TYPE_REF_ID = 2;
	const ILIAS_ID_TYPE_OBJ_ID = 3;
	const ILIAS_ID_TYPE_ROLE = 4;
	const DELETE_MODE_DELETE = 1;
	const DELETE_MODE_INACTIVE = 2;
	const DELETE_MODE_ARCHIVE = 3;
	const DELETE_MODE_TRASH = 4;
	/**
	 * @var hubOrigin
	 */
	protected $hubOrigin;
	/**
	 * @var ilObject2
	 */
	public $ilias_object;
	/**
	 * @var array
	 */
	protected static $counter = array();
	/**
	 * @var int
	 */
	public static $id_type = self::ILIAS_ID_TYPE_OBJ_ID;
	/**
	 * @var bool
	 */
	public $ar_safe_read = false;
	/**
	 * @var array
	 */
	protected static $loaded = array();
	/**
	 * @var array
	 */
	protected static $existing_ext_ids = array();


	/**
	 * @param $class
	 * @param $ext_id
	 *
	 * @deprecated
	 * @return bool
	 */
	public static function exists($class, $ext_id) {
		/**
		 * @var $class hubMembership
		 */
		if (! self::$loaded[$class]) {
			$class::get();
			self::$existing_ext_ids[$class] = array_values($class::getArray(NULL, 'ext_id'));
			self::$loaded[$class] = true;
		}

		return in_array($ext_id, self::$existing_ext_ids[$class]);
	}


	/**
	 * @param hubOrigin $origin
	 */
	public function update(hubOrigin $origin) {
		$this->updateInto($origin);
	}


	/**
	 * @param hubOrigin $origin
	 */
	public function create(hubOrigin $origin) {
		$this->updateInto($origin);
	}


	/**
	 * @param hubOrigin $origin
	 *
	 * @return bool
	 */
	public function updateInto(hubOrigin $origin) {
		$this->setSrHubOriginId($origin->getId());
		$this->updateDeliveryDate();
		$hist = $this->getHistoryObject();
		$hist->setDeleted(false);
		$hist->setAlreadyDeleted(false);
		$hist->update();
		if (self::find($this->getExtId())) {
			//			$this->setCreationDate(date(DATE_ATOM));
			parent::update();
		} else {
			$this->setCreationDate(date(DATE_ATOM));
			parent::create();
		}

		return true;
	}


	/**
	 * @param $primary_key
	 *
	 * @return hubObject
	 */
	public static function find($primary_key) {
		/**
		 * @var $obj hubObject
		 */
		$class_name = get_called_class();
		if (! arObjectCache::isCached($class_name, $primary_key)) {
			if (self::where(array( 'ext_id' => $primary_key ))->hasSets()) {
				arFactory::getInstance($class_name, $primary_key);
			} else {
				return NULL;
			}
		}

		return arObjectCache::get($class_name, $primary_key);
	}


	/**
	 * @param $primary_key
	 *
	 * @return hubObject
	 */
	public static function findOrGetInstance($primary_key) {
		/**
		 * @var $obj hubObject
		 */
		$obj = self::find($primary_key);
		if ($obj !== NULL) {
			return $obj;
		} else {
			$class_name = get_called_class();
			$obj = arFactory::getInstance($class_name, 0);
			$obj->setExtId($primary_key);

			//			$obj->storeObjectToCache();

			return $obj;
		}
	}


	/**
	 * @return mixed
	 *
	 * @desciprion Build get Status of History an build your ILIAS-Objects
	 */
	abstract public static function buildILIASObjects();


	/**
	 * @param int $ext_id
	 */
	public function __construct($ext_id = 0) {
		parent::__construct($ext_id, new hubConnector());
	}


	/**
	 * @return hubOriginObjectProperties
	 */
	public function props() {
		return hubOriginObjectProperties::getInstance($this->getSrHubOriginId());
	}


	/**
	 * @return hubSyncHistory
	 */
	public function getHistoryObject() {
		return hubSyncHistory::getInstance($this);
	}


	/**
	 * @return string
	 */
	public function returnImportId() {
		return self::IMPORT_PREFIX . $this->getHistoryObject()->getSrHubOriginId() . '_' . $this->getExtId();
	}


	public function updateDeliveryDate() {
		$this->setDeliveryDateMicro(microtime(true));
	}


	/**
	 * @var
	 * @db_has_field        true
	 * @db_fieldtype        integer
	 * @db_length           8
	 */
	protected $id;
	/**
	 * @var int
	 *
	 * @db_has_field        true
	 * @db_is_notnull       true
	 * @db_is_notnull       true
	 * @db_is_primary       true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $ext_id = '';
	/**
	 * @var int
	 *
	 * @db_has_field           true
	 * @db_fieldtype           float
	 * @db_length              8
	 * @con_index              true
	 */
	protected $delivery_date_micro;
	/**
	 * @var int
	 *
	 * @db_has_field            true
	 * @db_fieldtype            integer
	 * @db_is_notnull           true
	 * @db_length               8
	 * @con_index               true
	 */
	protected $sr_hub_origin_id;
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           256
	 * @con_index           true
	 */
	protected $shortlink = '';
	/**
	 * @var int
	 *
	 * @db_has_field            true
	 * @db_fieldtype            integer
	 * @db_length               1
	 */
	protected $ext_status = NULL;
	/**
	 * @var int
	 *
	 * @db_has_field            true
	 * @db_fieldtype            timestamp
	 */
	protected $creation_date = NULL;


	/**
	 * @return mixed
	 */
	public function getId() {
		return $this->id;
	}


	/**
	 * @param int $ext_status
	 */
	public function setExtStatus($ext_status) {
		$this->ext_status = $ext_status;
	}


	/**
	 * @return int
	 */
	public function getExtStatus() {
		return $this->ext_status;
	}


	/**
	 * @param string $ext_id
	 */
	public function setExtId($ext_id) {
		$this->ext_id = $ext_id;
	}


	/**
	 * @return string
	 */
	public function getExtId() {
		return $this->ext_id;
	}


	/**
	 * @param int $delivery_date_micro
	 */
	public function setDeliveryDateMicro($delivery_date_micro) {
		$this->delivery_date_micro = $delivery_date_micro;
	}


	/**
	 * @return int
	 */
	public function getDeliveryDateMicro() {
		return $this->delivery_date_micro;
	}


	/**
	 * @param int $sr_hub_origin_id
	 */
	public function setSrHubOriginId($sr_hub_origin_id) {
		$this->sr_hub_origin_id = $sr_hub_origin_id;
	}


	/**
	 * @return int
	 */
	public function getSrHubOriginId() {
		return $this->sr_hub_origin_id;
	}


	/**
	 * @param string $shortlink
	 */
	public function setShortlink($shortlink) {
		$this->shortlink = $shortlink;
	}


	/**
	 * @return string
	 */
	public function getShortlink() {
		return $this->shortlink;
	}


	/**
	 * @param int $creation_date
	 */
	public function setCreationDate($creation_date) {
		$this->creation_date = $creation_date;
	}


	/**
	 * @return int
	 */
	public function getCreationDate() {
		return $this->creation_date;
	}
}
?>