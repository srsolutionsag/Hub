<?php

/**
 * Class hub
 *
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 *
 * @revision $r$
 */
class hub {

	/**
	 * @var array
	 */
	protected static $object_types = array(
		self::OBJECTTYPE_USER => 'hubUser',
		self::OBJECTTYPE_MEMBERSHIP => 'hubMembership',
		self::OBJECTTYPE_COURSE => 'hubCourse',
		self::OBJECTTYPE_CATEGORY => 'hubCategory',
	);
	const OBJECTTYPE_USER = 1;
	const OBJECTTYPE_MEMBERSHIP = 2;
	const OBJECTTYPE_COURSE = 3;
	const OBJECTTYPE_CATEGORY = 4;


	/**
	 * @return array
	 */
	public static function getObjectTypeClassNames() {
		return self::$object_types;
	}


	/**
	 * @param $object_type_id
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public static function getObjectClassname($object_type_id) {
		if (! in_array($object_type_id, array_keys(self::$object_types)) AND $object_type_id != 0) {
			throw new Exception('$object_type_id ' . $object_type_id . 'does not exists');
		}

		return self::$object_types[$object_type_id];
	}


	public static function includeOriginTypes() {
	}


	/**
	 * @return string
	 */
	public static function getPath() {
		$real_path = realpath(self::getRootPath()) . 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/';
		$real_path = rtrim($real_path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

		return $real_path;
	}


	/**
	 * @return string
	 */
	public static function getRootPath() {
		$path = dirname(__FILE__) . '/../../../../../../../..';
		$real_path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

		return $real_path;
	}


	/**
	 * @return bool
	 */
	public static function isCli() {
		return (php_sapi_name() === 'cli');
	}
}