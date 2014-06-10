<?php
require_once('./Services/Logging/classes/class.ilLog.php');

/**
 * Class hubLog
 *
 * @author      Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.1.03
 * @description Async Log for HubPlugin
 */
class hubLog {

	const PATH = ILIAS_DATA_DIR;
	const FILENAME = 'hub.log';
	const L_DEBUG = 1;
	const L_WARN = 2;
	const L_BLOCK = 3;
	const L_PROD = 5;
	const DIRECT = true;
	const DISABLE = false;
	/**
	 * @var ilLog
	 */
	protected $hub_log;
	/**
	 * @var ilLog
	 */
	protected $il_log;
	/**
	 * @var hubLog
	 */
	protected static $cache;
	/**
	 * @var hubLogMessage[]
	 */
	protected static $messages = array();
	/**
	 * @var bool
	 */
	protected static $header_written = false;


	protected function __construct() {
		global $ilLog;
		$this->il_log = $ilLog;
		if (is_writable(self::getFilePath())) {
			$this->hub_log = new ilLog(self::PATH, self::FILENAME, 'HUB');
		} else {
			if (is_writable(self::PATH)) {
				touch(self::getFilePath());
			}
			//ilUtil::sendFailure('hub.log not writable', true);
		}
	}


	/**
	 * @return string
	 */
	protected static function getFilePath() {
		return self::PATH . DIRECTORY_SEPARATOR . self::FILENAME;
	}


	/**
	 * @return string
	 */
	public static function getBackTrace() {
		$return = '';
		foreach (debug_backtrace() as $bt) {
			if (! in_array($bt['function'], array( 'getBackTrace', 'executeCommand', 'performCommand' )) AND ! in_array($bt['class'], array(
					'hub',
					'ilCtrl',
					'ilObjectPluginGUI',
					'ilObject2GUI',
					'ilObjectFactory',
					'ilObject2'
				))
			) {
				$return .= $bt['class'] . '::' . $bt['function'] . '(' . $bt['line'] . ')<br>';
			}
		}

		return $return;
	}


	public function __destruct() {
		/*if (is_writable(self::getFilePath())) {
			if (! self::DIRECT AND ! self::DISABLE) {
				$this->hub_log->write(self::getHeader('New Request'), self::L_WARN);
				foreach (self::$messages as $m) {
					if ($m->getLevel() === self::L_PROD) {
						// $this->il_log->write($m->getMessage());
					}
					// $this->hub_log->write($m->getMessage(), self::getLevel($m->getLevel()));
				}
				$this->hub_log->write(self::getHeader('Request ended'), self::L_WARN);
			} elseif (! self::DISABLE) {
				$this->hub_log->write(self::getHeader('Request ended'), self::L_WARN);
			}
		}*/
	}


	/**
	 * @return hubLog
	 */
	public static function getInstance() {
		if (! isset(self::$cache)) {
			self::$cache = new self();
		}
		$obj =& self::$cache;

		return $obj;
	}


	/**
	 * @param $text
	 *
	 * @return string
	 */
	protected function getHeader($text) {
		return $text . ' ' . str_repeat('+', 50 - strlen($text)) . ' ' . date('d.m.Y - H:i:s');
	}


	/**
	 * @param $message
	 * @param $level
	 */
	public function write($message, $level = self::L_DEBUG) {
		if (! self::$header_written) {
			$this->hub_log->write(self::getHeader('New Request'), self::L_WARN);
			self::$header_written = true;
		}
		array_push(self::$messages, hubLogMessage::get($message, $level));
		if (self::DIRECT AND ! self::DISABLE) {
			$this->hub_log->write($message);
		}
	}


	/**
	 * @param $level
	 *
	 * @return string
	 */
	private static function getLevel($level) {
		switch ($level) {
			case self::L_DEBUG:
				return 'DEBUG';
			case self::L_WARN:
				return 'WARN';
			case self::L_BLOCK:
				return 'BLOCK';
			case self::L_PROD:
				return '';
		}
	}
}

class hubLogMessage {

	/**
	 * @var int
	 */
	protected $level = 0;
	/**
	 * @var string
	 */
	protected $message = '';


	/**
	 * @param $message
	 * @param $level
	 */
	protected function __construct($message, $level) {
		$this->setLevel($level);
		$this->setMessage(($message));
	}


	/**
	 * @param $message
	 * @param $level
	 *
	 * @return hubLogMessage
	 */
	public static function get($message, $level) {
		$obj = new self($message, $level);

		return $obj;
	}


	/**
	 * @param string $message
	 */
	public function setMessage($message) {
		$this->message = $message;
	}


	/**
	 * @return string
	 */
	public function getMessage() {
		return $this->message;
	}


	/**
	 * @param int $level
	 */
	public function setLevel($level) {
		$this->level = $level;
	}


	/**
	 * @return int
	 */
	public function getLevel() {
		return $this->level;
	}
}

?>