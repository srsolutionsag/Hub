<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Log/class.hubLog.php');

class hubDurationLogger2 {

	/**
	 * @var string
	 */
	protected $key = '';
	/**
	 * @var bool
	 */
	protected $micro = false;
	/**
	 * @var int
	 */
	protected $start = 0;
	/**
	 * @var int
	 */
	protected $stop = 0;
	/**
	 * @var array
	 */
	protected $pauses = array();
	/**
	 * @var array
	 */
	protected $resumes = array();
	/**
	 * @var hubDurationLogger2[]
	 */
	protected static $instances = array();


	/**
	 * @param      $key
	 * @param bool $micro
	 *
	 * @return hubDurationLogger2
	 */
	public static function getInstance($key, $micro = false) {
		if (!isset(self::$instances[$key])) {
			$hubDurationLogger = new self();
			$hubDurationLogger->setKey($key);
			//			$hubDurationLogger->setMicro($micro);
			self::$instances[$key] = $hubDurationLogger;
		}

		return self::$instances[$key];
	}


	/**
	 * @return int|mixed
	 */
	protected function getTime() {
		if ($this->getMicro()) {
			return microtime(true);
		} else {
			return time();
		}
	}


	public function start() {
		$this->setStart($this->getTime());
	}


	public function stop() {
		$this->setStop($this->getTime());
	}


	public function pause() {
		$this->pauses[] = $this->getTime();
	}


	public function resume() {
		if ($this->getStart() == 0) {
			$this->start();
		} else {
			$last_pause = count($this->getPauses()) - 1;
			$this->resumes[$last_pause] = $this->getTime();
		}
	}


	/**
	 * @param boolean $micro
	 */
	public function setMicro($micro) {
		$this->micro = $micro;
	}


	/**
	 * @return boolean
	 */
	public function getMicro() {
		return $this->micro;
	}


	/**
	 * @param array $pauses
	 */
	public function setPauses($pauses) {
		$this->pauses = $pauses;
	}


	/**
	 * @return array
	 */
	public function getPauses() {
		return $this->pauses;
	}


	/**
	 * @param array $resumes
	 */
	public function setResumes($resumes) {
		$this->resumes = $resumes;
	}


	/**
	 * @return array
	 */
	public function getResumes() {
		return $this->resumes;
	}


	/**
	 * @param int $start
	 */
	public function setStart($start) {
		$this->start = $start;
	}


	/**
	 * @return int
	 */
	public function getStart() {
		return $this->start;
	}


	/**
	 * @param int $stop
	 */
	public function setStop($stop) {
		$this->stop = $stop;
	}


	/**
	 * @return int
	 */
	public function getStop() {
		return $this->stop;
	}


	/**
	 * @param string $key
	 */
	public function setKey($key) {
		$this->key = $key;
	}


	/**
	 * @return string
	 */
	public function getKey() {
		return $this->key;
	}


	/**
	 * @return int
	 */
	public function get() {
		if ($this->getStart() == 0) {
			return null;
		}
		$this->stop();
		$full_time = $this->getStop() - $this->getStart();
		sort($this->pauses);
		sort($this->resumes);

		$pause_time = 0;
		foreach ($this->getPauses() as $i => $pause) {
			if (isset($this->resumes[$i])) {
				$pause_time = $pause_time + ($this->resumes[$i] - $pause);
			}
		}

		return $full_time - $pause_time;
	}


	public function log() {
		hubLog::getInstance()->write($this->asString(), hubLog::L_PROD);
	}


	/**
	 * @return string
	 */
	public function asString() {
		return 'Duration ' . $this->getKey() . ': ' . ($this->getMicro() ? date('H:i:s', $this->get()) . ' ms' : $this->get() . ' s');
	}

	//
	// Old Methods
	//

}

/**
 * Class hubDurationLogger
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.1.04
 */
class hubDurationLogger {

	/**
	 * @var array
	 */
	protected static $ids = array();
	/**
	 * @var array
	 */
	protected static $durations = array();
	/**
	 * @var array
	 */
	protected static $pauses = array();
	/**
	 * @var array
	 */
	protected static $starts = array();
	/**
	 * @var array
	 */
	protected static $stops = array();
	/**
	 * @var array
	 */
	protected static $micro = array();


	/**
	 * @param      $id
	 * @param bool $micro
	 *
	 * @deprecated
	 */
	public static function start($id, $micro = true) {
		self::$ids[$id] = true;
		self::$micro[$id] = $micro;
		if (self::$micro[$id]) {
			self::$durations[$id] = 0;
			self::$starts[$id] = microtime(true); // new

		} else {
			self::$durations[$id] = 0;
			self::$starts[$id] = time(); // new
		}

		self::$stops[$id] = self::$starts[$id]; // new
		self::$pauses[$id] = self::$starts[$id]; // new
	}


	/**
	 * @param $id
	 *
	 * @return bool
	 * @deprecated
	 */
	public static function stop($id) {
		if (self::$ids[$id]) {
			if (self::$micro[$id]) {
				self::$durations[$id] = microtime(true) - self::$durations[$id];
				self::$durations[$id] = microtime(true) - self::$starts[$id]; // new
			} else {
				self::$durations[$id] = time() - self::$durations[$id];
				self::$durations[$id] = time() - self::$starts[$id]; //new
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
	 * @deprecated
	 */
	public static function asString($id) {
		$time = self::stop($id);

		return 'Duration ' . $id . ': ' . (self::$micro[$id] ? date('H:i:s', $time) . ' ms' : $time . ' s');
	}


	/**
	 * @param $id
	 *
	 * @deprecated
	 */
	public static function write($id) {
		echo self::asString($id) . '<br>';
	}


	/**
	 * @param $id
	 *
	 * @deprecated
	 */
	public static function log($id) {
		hubLog::getInstance()->write(self::asString($id), hubLog::L_PROD);
	}
}

?>