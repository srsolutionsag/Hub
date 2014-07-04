<?php
require_once('./Customizing/global/plugins/Libraries/ActiveRecord/class.ActiveRecord.php');

/**
 * Class hubIcon
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
class hubIcon extends ActiveRecord {

	/**
	 * @return string
	 * @description Return the Name of your Database Table
	 * @deprecated
	 */
	static function returnDbTableName() {
		return 'hub_icon';
	}


	/**
	 * @return string
	 */
	public function getConnectorContainerName() {
		return 'hub_icon';
	}


	const SIZE_SMALL = 1;
	const SIZE_MEDIUM = 2;
	const SIZE_LARGE = 3;
	const USAGE_OBJECT = 1;
	const USAGE_FIRST_DEPENDENCE = 2;
	const USAGE_SECOND_DEPENDENCE = 3;
	const USAGE_THIRD_DEPENDENCE = 4;
	/**
	 * @var int
	 *
	 * @con_has_field          true
	 * @con_is_unique          true
	 * @con_is_primary         true
	 * @con_is_notnull         true
	 * @con_fieldtype          integer
	 * @con_length             4
	 * @con_sequence           true
	 */
	protected $id = 0;
	/**
	 * @var int
	 *
	 * @con_has_field          true
	 * @con_fieldtype          integer
	 * @con_length             8
	 * @con_index              true
	 */
	protected $sr_hub_origin_id = 0;
	/**
	 * @var string
	 */
	protected $path = '';
	/**
	 * @var string
	 *
	 * @con_has_field          true
	 * @con_fieldtype          text
	 * @con_length             64
	 */
	protected $name = '';
	/**
	 * @var string
	 *
	 * @con_has_field          true
	 * @con_fieldtype          text
	 * @con_length             128
	 */
	protected $suffix = '';
	/**
	 * @var int
	 *
	 * @con_has_field          true
	 * @con_fieldtype          integer
	 * @con_length             1
	 */
	protected $size_type = self::SIZE_LARGE;
	/**
	 * @var int
	 *
	 * @con_has_field          true
	 * @con_fieldtype          integer
	 * @con_length             1
	 */
	protected $usage_type = self::USAGE_OBJECT;
	/**
	 * @var int
	 *
	 * @con_has_field          true
	 * @con_fieldtype          integer
	 * @con_length             4
	 */
	protected $version = 1;
}

?>
