<?php
require_once('./Services/Database/classes/class.ilDBMySQL.php');
require_once('./Customizing/global/plugins/Libraries/ActiveRecord/class.ActiveRecord.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/OriginProperties/class.hubOriginObjectProperties.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Log/class.hubLog.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/class.ilHubPlugin.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Notification/class.hubOriginNotification.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Log/class.hubCounter.php');

/**
 * Class srModelObjectHubClass
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
abstract class srModelObjectHubClass extends ActiveRecord {

	const MODULO = 3000;
	const IMPORT_PREFIX = 'srhub_';
	const ILIAS_ID_TYPE_USER = 1;
	const ILIAS_ID_TYPE_REF_ID = 2;
	const ILIAS_ID_TYPE_OBJ_ID = 3;
	const ILIAS_ID_TYPE_ROLE = 4;
	const DELETE_MODE_DELETE = 1;
	const DELETE_MODE_INACTIVE = 2;
	const DELETE_MODE_ARCHIVE = 3;
	/**
	 * @var hubOrigin
	 */
	protected $hubOrigin;
	/**
	 * @var ilObject2
	 */
	public $ilias_object;
	/**
	 * @var hubOriginObjectProperties
	 */
	protected $object_properties;
	/**
	 * @var ilHubPlugin
	 */
	protected $pl;
	/**
	 * @var array
	 */
	protected static $counter = array();


	/**
	 * @return mixed
	 *
	 * @desciprion Build get Status of Hisory an build your ILIAS-Objects
	 */
	abstract public static function buildILIASObjects();


	/**
	 * @param int $ext_id
	 */
	public function __construct($ext_id = NULL) {
		$this->pl = new ilHubPlugin();
		$this->log = hubLog::getInstance();
		$this->setExtId($ext_id);
		parent::__construct($ext_id);
		$this->object_properties = hubOriginObjectProperties::getInstance($this->getSrHubOriginId());
		/*
		$database = ilDBWrapperFactory::getWrapper('mysql');
		$database->setDBHost('127.0.0.1');
		$database->setDBName('middleware');
		$database->setDBUser('middleware');
		$database->setDBPassword('middleware');
		$database->setDBPort(3306);
		$database->connect();
		$this->db = $database;
		// $this-db = konfigurierbar machen
		*/
	}


	/**
	 * @return hubOriginObjectProperties
	 */
	public function props() {
		return $this->object_properties;
	}


	public function loadObjectProperties() {
		$this->object_properties = hubOriginObjectProperties::getInstance($this->getSrHubOriginId());
	}


	/**
	 * @var hubProperties
	 */
	public $properties;


	/**
	 * @return hubSyncHistory
	 */
	public function getHistoryObject() {
		/**
		 * @var $history hubSyncHistory
		 */
		$where = array(
			'ext_id' => $this->getExtId(),
			'sr_hub_origin_id' => $this->getSrHubOriginId(),
		);
		$history = hubSyncHistory::where($where)->first();
		if (! $history) {
			$history = new hubSyncHistory();
			$history->setExtId($this->getExtId());
			$history->setSrHubOriginId($this->getSrHubOriginId());
			$history->create();
		}

		return $history;
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
	 */
	public function updateInto(hubOrigin $origin) {
		$this->setSrHubOriginId($origin->getId());
		$this->updateDeliveryDate();
		$hist = $this->getHistoryObject();
		$hist->setDeleted(false);
		$hist->update();
		if (self::where(array( 'ext_id' => $this->getExtId() ))->hasSets()) {

			parent::update();
		} else {
			parent::create();
		}
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
	 */
	protected $delivery_date_micro;
	/**
	 * @var int
	 *
	 * @db_has_field            true
	 * @db_fieldtype            integer
	 * @db_is_notnull           true
	 * @db_length               8
	 */
	protected $sr_hub_origin_id;
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $shortlink = '';
	/**
	 * @var int
	 *
	 * @db_has_field            false
	 * @db_fieldtype            integer
	 * @db_length               1
	 */
	protected $ext_status = NULL;


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

	//
	// Helper
	//

	public static function logCounts() {
		//		$created = get_called_class() . ': Created: ' . hubCounter::getCount(hubCounter::CREATED);
		//		hubLog::getInstance()->write($created, hubLog::L_PROD);
		//		$updated = get_called_class() . ': Updated: ' . hubCounter::getCount(hubCounter::UPDATED);
		//		hubLog::getInstance()->write($updated, hubLog::L_PROD);
		//		$deleted = get_called_class() . ': Deleted: ' . hubCounter::getCount(hubCounter::DELETED);
		//		hubLog::getInstance()->write($deleted, hubLog::L_PROD);
		//		$ignored = get_called_class() . ': Ignored: ' . hubCounter::getCount(hubCounter::IGNORED);
		//		hubLog::getInstance()->write($ignored, hubLog::L_PROD);
	}
}

?>