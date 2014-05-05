<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Log/class.hubLog.php');

/**
 * Class hubDurationLogger
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.1.02
 */
class hubDurationLogger {

	/**
	 * @var array
	 */
	protected static $durations = array();
	/**
	 * @var array
	 */
	protected static $ids = array();
	/**
	 * @var array
	 */
	protected static $micro = array();


	/**
	 * @param      $id
	 * @param bool $micro
	 */
	public static function start($id, $micro = true) {
		self::$ids[$id] = true;
		self::$micro[$id] = $micro;
		if (self::$micro[$id]) {
			self::$durations[$id] = microtime();
		} else {
			self::$durations[$id] = time();
		}
	}


	/**
	 * @param $id
	 *
	 * @return bool
	 */
	public static function stop($id) {
		if (self::$ids[$id]) {
			if (self::$micro[$id]) {
				self::$durations[$id] = microtime() - self::$durations[$id];
			} else {
				self::$durations[$id] = time() - self::$durations[$id];
			}

			return self::$durations[$id];
		} else {
			return false;
		}
	}


	/**
	 * @param $id
	 *
	 * @return string
	 */
	public static function asString($id) {
		$time = self::stop($id);

		return 'Duration ' . $id . ': ' . (self::$micro[$id] ? date('H:i:s', $time) . ' ms' : $time . ' s');
	}


	/**
	 * @param $id
	 */
	public static function write($id) {
		echo self::asString($id) . '<br>';
	}


	/**
	 * @param $id
	 */
	public static function log($id) {
		hubLog::getInstance()->write(self::asString($id), hubLog::L_PROD);
	}
}

?>