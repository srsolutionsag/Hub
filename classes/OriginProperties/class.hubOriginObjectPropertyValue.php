<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/class.hub.php');
hub::loadActiveRecord();
/**
 * Class hubOriginObjectPropertyValue
 *
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.1.04
 *
 * @revision $r$
 */
class hubOriginObjectPropertyValue extends ActiveRecord {

	/**
	 * @var bool
	 */
	protected $ar_safe_read = false;


	/**
	 * @return string
	 */
	static function returnDbTableName() {
		return 'sr_hub_origin_prop';
	}


	public function update() {
		$this->updateInto();
	}


	public function create() {
		$this->updateInto();
	}


	public function updateInto() {
		if (self::where(array( 'property_key' => $this->getPropertyKey() ))->hasSets()) {
			parent::update();
		} else {

			parent::create();
		}
	}


	/**
	 * @var int
	 * @db_has_field    true
	 * @db_fieldtype    integer
	 * @db_length       8
	 */
	protected $sr_hub_origin_id;
	/**
	 * @var string
	 * @db_has_field    true
	 * @db_is_primary   true
	 * @db_fieldtype    text
	 * @db_length       255
	 */
	protected $property_key;
	/**
	 * @var string
	 * @db_has_field    true
	 * @db_fieldtype    text
	 * @db_length       4000
	 */
	protected $property_value;


	/**
	 * @param string $prop_key
	 */
	public function setPropertyKey($prop_key) {
		$this->property_key = $prop_key;
	}


	/**
	 * @return string
	 */
	public function getPropertyKey() {
		return $this->property_key;
	}


	/**
	 * @param string $prop_value
	 */
	public function setPropertyValue($prop_value) {
		$this->property_value = json_encode($prop_value);
	}


	/**
	 * @return string
	 */
	public function getPropertyValue() {
		return json_decode($this->property_value, true);
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
}

?>