<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Log/class.hubLog.php');

/**
 * Class hubCounter
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.1.04
 */
class hubCounter {

	const MODULO = 500;
	const DELETED = 'deleted';
	const CREATED = 'created';
	const UPDATED = 'updated';
	const IGNORED = 'ignored';
	const NEWLY_DELIVERED = 'newly_delivered';
	const BUILT = 'built';
	/**
	 * @var array
	 */
	public static $counter = array();


	/**
	 * @param           $sr_hub_origin_id
	 * @param           $type
	 */
	protected static function increment($sr_hub_origin_id, $type) {
		self::$counter[$sr_hub_origin_id][$type] ++;
	}


	/**
	 * @param           $type
	 * @param           $sr_hub_origin_id
	 *
	 * @return int
	 */
	public static function getCountForOriginId($type, $sr_hub_origin_id) {
		return self::$counter[$sr_hub_origin_id][$type];
	}


	/**
	 * @param $type
	 *
	 * @return int
	 */
	public static function getCount($type) {
		$return = 0;
		foreach (self::$counter as $type_index => $count) {
			if ($type === $type_index) {
				$return += $count;
			}
		}

		return $return;
	}

	//
	// INCREMENT
	//

	/**
	 * @param $sr_hub_origin_id
	 */
	public static function incrementNewlyDelivered($sr_hub_origin_id) {
		self::increment($sr_hub_origin_id, self::NEWLY_DELIVERED);
	}


	/**
	 * @param $sr_hub_origin_id
	 */
	public static function incrementDeleted($sr_hub_origin_id) {
		self::increment($sr_hub_origin_id, self::DELETED);
	}


	/**
	 * @param $sr_hub_origin_id
	 *
	 * @internal param $sr_hub_origin_id
	 */
	public static function incrementCreated($sr_hub_origin_id) {
		self::increment($sr_hub_origin_id, self::CREATED);
	}


	/**
	 * @param $sr_hub_origin_id
	 *
	 * @internal param $sr_hub_origin_id
	 */
	public static function incrementUpdated($sr_hub_origin_id) {
		self::increment($sr_hub_origin_id, self::UPDATED);
	}


	/**
	 * @param $sr_hub_origin_id
	 *
	 * @internal param $sr_hub_origin_id
	 */
	public static function incrementIgnored($sr_hub_origin_id) {
		self::increment($sr_hub_origin_id, self::IGNORED);
	}


	public static function incrementBuilt() {
		self::increment(self::BUILT, self::BUILT);
	}

	//
	// GET VALUES
	//

	/**
	 * @param $sr_hub_origin_id
	 *
	 * @internal param null $sr_hub_origin_id
	 *
	 * @return array
	 */
	public static function getCountDeleted($sr_hub_origin_id) {
		return self::getCountForOriginId(self::DELETED, $sr_hub_origin_id);
	}


	/**
	 * @param $sr_hub_origin_id
	 *
	 * @internal param null $sr_hub_origin_id
	 *
	 * @return array
	 */
	public static function getCountNewlyDelivered($sr_hub_origin_id) {
		return self::getCountForOriginId(self::NEWLY_DELIVERED, $sr_hub_origin_id);
	}


	/**
	 * @param $sr_hub_origin_id
	 *
	 * @internal param null $sr_hub_origin_id
	 *
	 * @return array
	 */
	public static function getCountCreated($sr_hub_origin_id) {
		return self::getCountForOriginId(self::CREATED, $sr_hub_origin_id);
	}


	/**
	 * @param $sr_hub_origin_id
	 *
	 * @internal param null $sr_hub_origin_id
	 *
	 * @return array
	 */
	public static function getCountUpdated($sr_hub_origin_id) {
		return self::getCountForOriginId(self::UPDATED, $sr_hub_origin_id);
	}


	/**
	 * @param $sr_hub_origin_id
	 *
	 * @internal param null $sr_hub_origin_id
	 *
	 * @return array
	 */
	public static function getCountIgnored($sr_hub_origin_id) {
		return self::getCountForOriginId(self::IGNORED, $sr_hub_origin_id);
	}


	/**
	 * @return int
	 */
	public static function getCountBuilt() {
		return self::getCountForOriginId(self::BUILT, self::BUILT);
	}

	//
	// COMMON
	//

	public static function logRunning() {
		self::incrementBuilt();
		if ((self::getCountBuilt() % self::MODULO) == 0) {
			hubLog::getInstance()->write(get_called_class() . ': working...', hubLog::L_DEBUG);
		}
	}


	public static function logBuilding() {
		if ((self::getCountOverall() % self::MODULO) == 0) {
			hubLog::getInstance()->write(get_called_class() . ': building...', hubLog::L_DEBUG);
		}
	}


	/**
	 * @return int
	 */
	public static function getCountOverall() {
		return (self::getCount(self::CREATED) + self::getCount(self::UPDATED) + self::getCount(self::DELETED) + self::getCount(self::IGNORED));
	}
}

?>