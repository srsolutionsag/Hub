<?php

require_once('./Customizing/global/plugins/Libraries/ActiveRecord/class.ActiveRecord.php');

/**
 * Class hubOriginObjectPropertyValue
 *
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.1.02
 *
 * @revision $r$
 */
class hubOriginObjectPropertyValue extends ActiveRecord {

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
	 * @db_length       256
	 */
	protected $property_key;
	/**
	 * @var string
	 * @db_has_field    true
	 * @db_fieldtype    text
	 * @db_length       1000
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
		$this->property_value = $prop_value;
	}


	/**
	 * @return string
	 */
	public function getPropertyValue() {
		return $this->property_value;
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