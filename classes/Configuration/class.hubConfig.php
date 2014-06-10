<?php
require_once('./Customizing/global/plugins/Libraries/ActiveRecord/class.ActiveRecord.php');
require_once('./include/inc.ilias_version.php');
require_once('./Services/Component/classes/class.ilComponent.php');

/**
 * Class hubConfig
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.1.03
 *
 */
class hubConfig extends ActiveRecord {

	const F_DB_HOST = 'db_host';
	const F_DB = 'db';
	const F_DB_NAME = 'db_name';
	const F_DB_USER = 'db_user';
	const F_DB_PASSWORD = 'db_password';
	const F_DB_PORT = 'db_port';
	const F_ORIGINS_PATH = 'origins_path';
	const F_ROOT_PATH = 'root_path';
	const F_LOCK = 'lock';
	const F_USE_ASYNC = 'use_async';
	const F_ASYNC_USER = 'async_user';
	const F_ASYNC_PASSWORD = 'async_password';
	const F_ASYNC_CLIENT = 'async_client';
	const F_ASYNC_CLI_PHP = 'async_cli_php';
	const F_ADMIN_ROLES = 'admin_roles';
	const F_IMPORT_EXPORT = 'import_export';
	const MIN_ILIAS_VERSION = self::ILIAS_43;
	const ILIAS_43 = 43;
	const ILIAS_44 = 44;
	const ILIAS_45 = 45;
	/**
	 * @var array
	 */
	protected static $cache = array();
	/**
	 * @var array
	 */
	protected static $cache_loaded = array();

	/**
	 * @var bool
	 */
	protected $ar_safe_read = false;


	/**
	 * @return string
	 * @description Return the Name of your Database Table
	 */
	static function returnDbTableName() {
		return 'sr_hub_conf';
	}


	/**
	 * @return int
	 */
	public static function getILIASVersion() {
		if (ilComponent::isVersionGreaterString(ILIAS_VERSION_NUMERIC, '4.5.000')) {
			return self::ILIAS_45;
		}
		if (ilComponent::isVersionGreaterString(ILIAS_VERSION_NUMERIC, '4.4.000')) {
			return self::ILIAS_44;
		}
		if (ilComponent::isVersionGreaterString(ILIAS_VERSION_NUMERIC, '4.3.000')) {
			return self::ILIAS_43;
		}

		return 0;
	}


	/**
	 * @return bool
	 */
	public static function isILIASSupported() {
		return self::getILIASVersion() >= self::MIN_ILIAS_VERSION;
	}


	/**
	 * @return bool
	 */
	public static function is44() {
		return self::getILIASVersion() >= self::ILIAS_44;
	}


	/**
	 * @return bool
	 */
	public static function is43() {
		return self::getILIASVersion() >= self::ILIAS_43;
	}


	/**
	 * @return bool
	 */
	public static function is45() {
		return self::getILIASVersion() >= self::ILIAS_45;
	}


	/**
	 * @param $name
	 *
	 * @return string
	 */
	public static function get($name) {
		if (! isset(self::$cache_loaded[$name])) {
			$obj = self::find($name);
			if ($obj === NULL) {
				self::$cache[$name] = NULL;
			} else {
				self::$cache[$name] = $obj->getValue();
			}
			self::$cache_loaded[$name] = true;
		}

		return self::$cache[$name];
	}


	/**
	 * @param $name
	 * @param $value
	 *
	 * @return null
	 */
	public static function set($name, $value) {
		/**
		 * @var $obj arConfig
		 */
		$obj = self::findOrGetInstance($name);
		if ($obj === NULL) {
			$obj = new self($name);
			$obj->setValue($value);
			$obj->create();
		} else {
			$obj->setValue($value);
			$obj->update();
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
