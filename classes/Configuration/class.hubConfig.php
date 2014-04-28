<?php
require_once('./Customizing/global/plugins/Libraries/ActiveRecord/class.ActiveRecord.php');

/**
 * Class hubConfig
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class hubConfig extends ActiveRecord {

	const F_DB_HOST = 'db_host';
	const F_DB = 'db';
	const F_DB_NAME = 'db_name';
	const F_DB_USER = 'db_user';
	const F_DB_PASSWORD = 'db_password';
	const F_DB_PORT = 'db_port';
	const F_ORIGINS_PATH = 'origins_path';
	const F_LOCK = 'lock';
	const F_USE_ASYNC = 'use_async';
	const F_ASYNC_USER = 'async_user';
	const F_ASYNC_PASSWORD = 'async_password';
	const F_ASYNC_CLIENT = 'async_client';
	const F_ASYNC_CLI_PHP = 'async_cli_php';
	const F_ADMIN_ROLES = 'admin_roles';
	const F_IMPORT_EXPORT = 'import_export';
	/**
	 * @var array
	 */
	protected static $cache = array();


	/**
	 * @return string
	 * @description Return the Name of your Database Table
	 */
	static function returnDbTableName() {
		return 'sr_hub_conf';
	}


	/**
	 * @param $name
	 *
	 * @return string
	 */
	public static function get($name) {
		if (! isset(self::$cache[$name])) {
			$obj = new self($name);
			self::$cache[$name] = $obj->getValue();
		}

		return self::$cache[$name];
	}


	/**
	 * @param $name
	 * @param $value
	 */
	public static function set($name, $value) {
		$obj = new self($name);
		$obj->setValue($value);
		if (self::where(array( 'name' => $name ))->hasSets()) {
			$obj->update();
		} else {
			$obj->create();
		}
	}


	/**
	 * @return bool
	 */
	public static function isImportEnabled() {
		return hubConfig::get(self::F_IMPORT_EXPORT) AND is_writable(hubOrigin::getOriginsPathForUsageType(hub::OBJECTTYPE_CATEGORY)) AND
		is_writable(hubOrigin::getOriginsPathForUsageType(hub::OBJECTTYPE_MEMBERSHIP)) AND
		is_writable(hubOrigin::getOriginsPathForUsageType(hub::OBJECTTYPE_USER))
		AND is_writable(hubOrigin::getOriginsPathForUsageType(hub::OBJECTTYPE_COURSE));
	}


	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_is_unique        true
	 * @db_is_primary       true
	 * @db_is_notnull       true
	 * @db_fieldtype        text
	 * @db_length           250
	 */
	protected $name;
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           1000
	 */
	protected $value;


	/**
	 * @param string $value
	 */
	public function setValue($value) {
		$this->value = $value;
	}


	/**
	 * @return string
	 */
	public function getValue() {
		return $this->value;
	}


	/**
	 * @param string $name
	 */
	public function setName($name) {
		$this->name = $name;
	}


	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}
}

?>
