<?php
require_once('./Services/Logging/classes/class.ilLog.php');

/**
 * Class hubLog
 *
 * @author      Fabian Schmid <fs@studer-raimann.ch>
 * @version     1.1.04
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
			if (!in_array($bt['function'], array( 'getBackTrace', 'executeCommand', 'performCommand' )) AND !in_array($bt['class'], array(
					'hub',
					'ilCtrl',
					'ilObjectPluginGUI',
					'ilObject2GUI',
					'ilObjectFactory',
					'ilObject2',
				))
			) {
				$return .= $bt['class'] . '::' . $bt['function'] . '(' . $bt['line'] . ')<br>';
			}
		}

		return $return;
	}


	/**
	 * @return hubLog
	 */
	public static function getInstance() {
		if (!isset(self::$cache)) {
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
		$date = '[' . date('d.m.Y - H:i:s') . ']';
		$length = hubLogMessage::LENGTH - strlen($text) - strlen($date);
		return $text . ' ' . str_repeat('+', ($length > 0 ? $length : 0)) . ' ' . $date;
	}


	/**
	 * @param $message
	 * @param $level
	 */
	public function write($message, $level = self::L_DEBUG) {
		if (!self::$header_written) {
			$this->hub_log->write(self::getHeader('New Request'), self::L_WARN);
			self::$header_written = true;
		}
		$hubLogMessage = hubLogMessage::get($message, $level);
		array_push(self::$messages, $hubLogMessage);
		if (self::DIRECT AND !self::DISABLE) {
			$this->hub_log->write($hubLogMessage->renderMessage());
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

	const LENGTH = 100;
	/**
	 * @var int
	 */
	protected $level = 0;
	/**
	 * @var string
	 */
	protected $message = '';
	/**
	 * @var int
	 */
	protected $memory_peak = 0;
	/**
	 * @var int
	 */
	protected $memory = 0;


	/**
	 * @param $message
	 * @param $level
	 */
	protected function __construct($message, $level) {
		$this->setLevel($level);
		$this->setMessage(($message));
		$this->setMemoryPeak(memory_get_peak_usage());
		$this->setMemory(memory_get_usage());
	}


	/**
	 * @param        $bytes
	 * @param string $unit
	 * @param int $decimals
	 *
	 * @return string
	 */
	protected static function formatBytes($bytes, $unit = "", $decimals = 2) {
		$units = array(
			'B'  => 0,
			'KB' => 1,
			'MB' => 2,
			'GB' => 3,
			'TB' => 4,
			'PB' => 5,
			'EB' => 6,
			'ZB' => 7,
			'YB' => 8,
		);

		$value = 0;
		if ($bytes > 0) {
			// Generate automatic prefix by bytes
			// If wrong prefix given
			if (!array_key_exists($unit, $units)) {
				$pow = floor(log($bytes) / log(1024));
				$unit = array_search($pow, $units);
			}

			// Calculate byte value by prefix
			$value = ($bytes / pow(1024, floor($units[$unit])));
		}

		// If decimals is not numeric or decimals is less than 0
		// then set default value
		if (!is_numeric($decimals) || $decimals < 0) {
			$decimals = 2;
		}

		// Format output
		return sprintf('%.' . $decimals . 'f ' . $unit, $value);
	}


	/**
	 * @return string
	 */
	public function renderMessage() {
		$max = self::formatBytes($this->getMemoryPeak(), 'MB', 0);
		$cur = self::formatBytes($this->getMemory(), 'MB', 0);

		$memory_info = '[Cur: ' . $cur . ', Max:' . $max . ']';
		$message = $this->getMessage();

		if (strlen($memory_info) - strlen($message) + 2 < hubLogMessage::LENGTH) {
			// if a message is smaller than hubLogMessage::LENGTH, then fill it up with spaces in order to reach the maximal length (plus two spaces more)
			$fill = str_repeat(' ', hubLogMessage::LENGTH - strlen($memory_info) - strlen($message) + 2);
		} else {
			// if a message is smaller than hubLogMessage::LENGTH, then fill it up with spaces in order to reach the maximal length (plus two spaces more)
			$fill = '  ';
			$message_array = explode(' :: ', $message);
			$message = substr($message_array[0], 0, hubLogMessage::LENGTH - strlen($memory_info) - strlen($message_array[1]) - strlen('... :: '))
			           . '... :: ' . $message_array[1];
		}

		return $message . $fill . $memory_info;
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


	/**
	 * @return int
	 */
	public function getMemoryPeak() {
		return $this->memory_peak;
	}


	/**
	 * @param int $memory_peak
	 */
	public function setMemoryPeak($memory_peak) {
		$this->memory_peak = $memory_peak;
	}


	/**
	 * @return int
	 */
	public function getMemory() {
		return $this->memory;
	}


	/**
	 * @param int $memory
	 */
	public function setMemory($memory) {
		$this->memory = $memory;
	}
}

?>