<?php


require_once('./Customizing/global/plugins/Libraries/ActiveRecord/class.ActiveRecord.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/class.hub.php');
require_once('./Services/Object/classes/class.ilObject2.php');

/**
 * Class hubSyncHistory
 *
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 *
 * @revision $r$
 */
class hubSyncHistory extends ActiveRecord {

	const STATUS_NEW = 1;
	const STATUS_UPDATED = 2;
	const STATUS_DELETED = 3;
	const STATUS_DELETED_IN_ILIAS = self::STATUS_NEW; // 4
	const STATUS_NEWLY_DELIVERED = 5; // 5
	const STATUS_ALREADY_DELETED = 6;
	/**
	 * @var bool
	 */
	protected static $loaded = array();


	/**
	 * @param int $ext_id
	 */
	public function __construct($ext_id = 0) {
		parent::__construct($ext_id, new hubConnector());
	}


	//
	// Workflow
	//
	/**
	 * @param $sr_hub_origin_id
	 *
	 * @return bool
	 */
	public static function initStatus($sr_hub_origin_id) {
		/**
		 * @var $class     hubCategory
		 * @var $hubObject hubCategory
		 * @var $ilDB      ilDB
		 */
		if (! self::$loaded[$sr_hub_origin_id]) {
			global $ilDB;
			$class = hubOrigin::getUsageClass($sr_hub_origin_id);
			$sql = 'UPDATE sr_hub_sync_history hist
					JOIN ' . $class::returnDbTableName() . ' hub_obj ON hub_obj.ext_id = hist.ext_id
					SET hist.deleted = 1
					WHERE hist.sr_hub_origin_id = ' . $ilDB->quote($sr_hub_origin_id, 'integer') . '
						AND hist.pickup_date_micro > hub_obj.delivery_date_micro;';
			$ilDB->query($sql);

			self::$loaded[$sr_hub_origin_id] = true;
		}

		return true;
	}


	/**
	 * @return int
	 * @throws Exception
	 */
	public function getStatus() {
		if (! self::isLoaded($this->getSrHubOriginId())) {
			throw new Exception('Cannot get Status of hubSyncHistory object before hubSyncHistory::initDataForSync()<br>'
				. print_r(hubLog::getBackTrace(), 1));
		} else {
			return $this->getTemporaryStatus();
		}
	}


	/**
	 * @param $sr_hub_origin_id
	 *
	 * @return bool
	 */
	public static function isLoaded($sr_hub_origin_id) {
		return isset(self::$loaded[$sr_hub_origin_id]);
	}


	/**
	 * @return int
	 */
	public function getTemporaryStatus() {
		if ($this->getIliasId()) {
			if ($this->getDeleted()) {
				if ($this->getAlreadyDeleted()) {
					return self::STATUS_ALREADY_DELETED;
				} else {
					return self::STATUS_DELETED;
				}
			} else {
				if ($this->isDeletedInILIAS()) {
					return self::STATUS_DELETED_IN_ILIAS;
				}
				if ($this->getAlreadyDeleted()) {
					return self::STATUS_NEWLY_DELIVERED;
				}

				return self::STATUS_UPDATED;
			}
		} else {
			return self::STATUS_NEW;
		}
	}


	public function updatePickupDate() {
		$this->setPickupDateMicro(microtime(true));
		$this->update();
	}


	/**
	 * @return hubCourse
	 * @deprecated
	 */
	public function getHubObject() {
		$class = hubOrigin::getUsageClass($this->getSrHubOriginId());

		return $class::find($this->getExtId());
	}


	/**
	 * @return string
	 * @description Return the Name of your Database Table
	 */
	static function returnDbTableName() {
		return 'sr_hub_sync_history';
	}


	/**
	 * @return array
	 */
	public static function getAllStatusAsArray() {
		$ReflectionClass = new ReflectionClass('hubSyncHistory');
		$status = array();
		foreach ($ReflectionClass->getConstants() as $name => $value) {
			if (strpos($name, 'STATUS_') === 0) {
				$status[str_ireplace('STATUS_', '', $name)] = $value;
			}
		}

		return $status;
	}


	/**
	 * @return bool
	 */
	private function isDeletedInILIAS() {
		return ! ilObject2::_exists($this->getIliasId(), ($this->getIliasIdType() == srModelObjectHubClass::ILIAS_ID_TYPE_REF_ID ? true : false));
	}


	public function create() {
		if (self::where(array( 'ext_id' => $this->getExtId() ))->hasSets()) {
			parent::update();
		} else {
			parent::create();
		}
	}


	/**
	 * @var int
	 *
	 * @db_has_field           true
	 * @db_fieldtype           integer
	 * @db_length              8
	 */
	protected $id;
	/**
	 * @var string
	 *
	 * @db_has_field           true
	 * @db_fieldtype           text
	 * @db_is_primary          true
	 * @db_is_notnull          true
	 * @db_length              256
	 */
	protected $ext_id;
	/**
	 * @var int
	 *
	 * @db_has_field           true
	 * @db_fieldtype           integer
	 * @db_length              8
	 */
	protected $ilias_id;
	/**
	 * @var int
	 *
	 * @db_has_field           true
	 * @db_fieldtype           integer
	 * @db_length              1
	 */
	protected $ilias_id_type;
	/**
	 * @var int
	 *
	 * @db_has_field           true
	 * @db_fieldtype           integer
	 * @db_length              4
	 */
	protected $sr_hub_origin_id;
	/**
	 * @var int
	 *
	 * @db_has_field           true
	 * @db_fieldtype           float
	 * @db_length              8
	 */
	protected $pickup_date_micro = 0;
	/**
	 * @var bool
	 *
	 * @db_has_field           true
	 * @db_fieldtype           integer
	 * @db_length              1
	 */
	protected $deleted = 0;
	/**
	 * @var bool
	 *
	 * @db_has_field           true
	 * @db_fieldtype           integer
	 * @db_length              1
	 */
	protected $already_deleted = 0;
	/**
	 * @var int
	 *
	 * @db_has_field           false
	 * @db_fieldtype           integer
	 * @db_length              1
	 */
	protected $ext_id_type;


	/**
	 * @param boolean $deleted
	 */
	public function setDeleted($deleted) {
		$this->deleted = $deleted;
	}


	/**
	 * @return boolean
	 */
	public function getDeleted() {
		return $this->deleted;
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
	 * @param int $ext_id_type
	 */
	public function setExtIdType($ext_id_type) {
		$this->ext_id_type = $ext_id_type;
	}


	/**
	 * @return int
	 */
	public function getExtIdType() {
		return $this->ext_id_type;
	}


	/**
	 * @param int $ilias_id
	 */
	public function setIliasId($ilias_id) {
		$this->ilias_id = $ilias_id;
	}


	/**
	 * @return int
	 */
	public function getIliasId() {
		return $this->ilias_id;
	}


	/**
	 * @param int $ilias_id_type
	 */
	public function setIliasIdType($ilias_id_type) {
		$this->ilias_id_type = $ilias_id_type;
	}


	/**
	 * @return int
	 */
	public function getIliasIdType() {
		return $this->ilias_id_type;
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
	 * @param int $pickup_date_micro
	 */
	public function setPickupDateMicro($pickup_date_micro) {
		$this->pickup_date_micro = $pickup_date_micro;
	}


	/**
	 * @return int
	 */
	public function getPickupDateMicro() {
		return $this->pickup_date_micro;
	}


	/**
	 * @param boolean $already_deleted
	 */
	public function setAlreadyDeleted($already_deleted) {
		$this->already_deleted = $already_deleted;
	}


	/**
	 * @return boolean
	 */
	public function getAlreadyDeleted() {
		return $this->already_deleted;
	}
}