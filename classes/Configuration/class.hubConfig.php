<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/class.hub.php');
require_once "Services/ActiveRecord/class.ActiveRecord.php";
require_once('./include/inc.ilias_version.php');
require_once('./Services/Component/classes/class.ilComponent.php');

/**
 * Class hubConfig
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.1.04
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
	const F_MSG_SHORTLINK_NOT_FOUND = 'msg_shortlink_not_found';
	const F_MSG_SHORTLINK_NOT_ACTIVE = 'msg_shortlink_not_active';
	const F_MSG_SHORTLINK_NO_ILIAS_ID = 'msg_shortlink_no_ilias_id';
	const F_MMAIL_ACTIVE = 'membership_mail_active';
	const F_MMAIL_SUBJECT = 'membership_mail_subject';
	const F_MMAIL_MSG = 'membership_mail_msg';
	const F_STANDARD_ROLE = 'standard_role';
	const TABLE_NAME = "sr_hub_conf";


	/**
	 * @return string
	 */
	public function getConnectorContainerName() {
		return self::TABLE_NAME;
	}


	/**
	 * @return string
	 * @deprecated
	 */
	public static function returnDbTableName() {
		return self::TABLE_NAME;
	}


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
	 * @param string $name
	 *
	 * @return string
	 */
	public static function get($name = NULL) {
		if (!isset(self::$cache_loaded[$name])) {
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
	 * @param string $name
	 * @param string $value
	 */
	public static function set($name, $value) {
		/**
		 * @var hubConfig $obj
		 */
		$obj = self::findOrGetInstance($name);
		$obj->setValue($value);
		if (self::where(array( 'name' => $name ))->hasSets()) {
			$obj->update();
		} else {
			$obj->create();
		}
	}


	/**
	 * @param string $name
	 */
	public static function remove($name) {
		/**
		 * @var hubConfig $obj
		 */
		$obj = self::find($name);
		if ($obj !== NULL) {
			$obj->delete();
		}
	}


	/**
	 * @return bool
	 */
	public static function isImportEnabled() {
		return hubConfig::get(self::F_IMPORT_EXPORT) AND is_writable(hubOrigin::getOriginsPathForUsageType(hub::OBJECTTYPE_CATEGORY))
			AND is_writable(hubOrigin::getOriginsPathForUsageType(hub::OBJECTTYPE_MEMBERSHIP))
			AND is_writable(hubOrigin::getOriginsPathForUsageType(hub::OBJECTTYPE_USER))
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
