<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/OriginProperties/class.hubOriginObjectPropertyValue.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Origin/class.hubOrigin.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/class.hub.php');

/**
 * Class hubProperties
 *
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 *
 * @revision $r$
 */
class hubOriginObjectProperties {

	/**
	 * @var int
	 */
	protected $sr_hub_origin_id;
	/**
	 * @var string
	 */
	protected $prefix = '';
	/**
	 * @var array
	 */
	private static $cache = array();


	/**
	 * @param $sr_hub_origin_id
	 */
	protected function __construct($sr_hub_origin_id) {
		/**
		 * @var $hubOrigin hubOrigin
		 */
		if ($sr_hub_origin_id != 0) {
			$this->setSrHubOriginId($sr_hub_origin_id);
			$hubOrigin = hubOrigin::find($sr_hub_origin_id);
			switch ($hubOrigin->getUsageType()) {
				case hub::OBJECTTYPE_CATEGORY:
					$this->setPrefix('cat');
					break;
				case hub::OBJECTTYPE_COURSE:
					$this->setPrefix('crs');
					break;
				case hub::OBJECTTYPE_USER:
					$this->setPrefix('usr');
					break;
				case hub::OBJECTTYPE_MEMBERSHIP:
					$this->setPrefix('mem');
					break;
			}
			/**
			 * @var $prop hubOriginObjectPropertyValue
			 */
			$where = array( 'sr_hub_origin_id' => $sr_hub_origin_id );
			foreach (hubOriginObjectPropertyValue::where($where)->get() as $prop) {
				if ($prop->getPropertyKey()) {
					$this->{$prop->getPropertyKey()} = $prop->getPropertyValue();
				}
			}
			self::$cache[$sr_hub_origin_id] = $this;
		}
	}


	/**
	 * @param $sr_hub_origin_id
	 *
	 * @return hubOriginObjectProperties
	 */
	public static function getInstance($sr_hub_origin_id) {
		if ($sr_hub_origin_id == 0) {
			return new self(0);
		}
		if (! isset(self::$cache[$sr_hub_origin_id])) {
			self::$cache[$sr_hub_origin_id] = new self($sr_hub_origin_id);
		}

		return self::$cache[$sr_hub_origin_id];
	}


	/**
	 * @param string $appendix
	 *
	 * @return bool|string
	 */
	public function getIconPath($appendix = '') {
		$path = hub::getPath() . 'icons/' . hubOrigin::getUsageClass($this->getSrHubOriginId()) . '/'
			. hubOrigin::getClassnameForOriginId($this->getSrHubOriginId()) . $appendix . '.png';
		if (is_file($path)) {
			return $path;
		} else {
			return false;
		}
	}


	/**
	 * @param $key
	 *
	 * @return mixed
	 */
	public function getByShortPrefix($key) {
		$prefix = $this->getPrefix() . '_' . $this->getSrHubOriginId() . '_';
		$key = $prefix . $key;

		return $this->{$key};
	}


	/**
	 * @param      $key
	 *
	 * @internal param bool $short_prefix
	 *
	 * @return mixed
	 */
	public function get($key) {
		$prefix = $this->getPrefix() . '_' . $this->getSrHubOriginId() . '_' . $this->getPrefix() . '_';
		$key = $prefix . $key;

		return $this->{$key};
	}


	/**
	 * @param $key
	 *
	 * @deprecated
	 * @return mixed
	 */
	public function getByKey($key) {
		return $this->get($key);
	}


	public function delete() {
		/**
		 * @var $prop hubOriginObjectPropertyValue
		 */
		$where = array( 'sr_hub_origin_id' => $this->getSrHubOriginId() );
		foreach (hubOriginObjectPropertyValue::where($where)->get() as $prop) {
			$prop->delete();
		}
	}


	/**
	 * @param      $key
	 * @param      $a_value
	 * @param bool $short_prefix
	 */
	public function setByKey($key, $a_value, $short_prefix = true) {
		if ($key) {
			if ($short_prefix) {
				$prefix = $this->getPrefix() . '_' . $this->getSrHubOriginId() . '_';
			} else {
				$prefix = $this->getPrefix() . '_' . $this->getSrHubOriginId() . '_' . $this->getPrefix() . '_';
			}
			$key = $prefix . self::_fromCamelCase($key);
			$value = new hubOriginObjectPropertyValue($key);
			$value->setSrHubOriginId($this->getSrHubOriginId());
			$value->setPropertyKey($key);
			$value->setPropertyValue($a_value);
			$value->updateInto();
			$this->{$key} = $a_value;
		}
	}


	/**
	 * @param $array
	 */
	public function importRaw(array $array) {
		$import = array();
		foreach ($array as $key => $value) {
			if (preg_match("/(usr|cat|crs|mem)_(.*)/us", $key, $matches)) {
				$import[$matches[2]] = $value;
			}
		}
		$this->import($import);
	}


	/**
	 * @param array $array
	 */
	public function import(array $array) {
		foreach ($array as $key => $value) {
			$this->setByKey($key, $value, false);
		}
	}


	/**
	 * @param $name
	 * @param $arguments
	 *
	 * @return array
	 */
	public function __call($name, $arguments) {
		// Getter
		$prefix = $this->getPrefix() . '_' . $this->getSrHubOriginId() . '_' . $this->getPrefix() . '_';
		if (preg_match("/get([a-zA-Z]*)/u", $name, $matches) AND count($arguments) == 0) {
			hubLog::getInstance()->write('Deprecated __call! ' . $name, hubLog::L_DEBUG);

			return $this->get(self::_fromCamelCase($matches[1]));
		}
		// Setter
		if (preg_match("/set([a-zA-Z]*)/u", $name, $matches) AND count($arguments) == 1) {
			$key = $prefix . self::_fromCamelCase($matches[1]);
			if ($key) {
				$value = new hubOriginObjectPropertyValue($key);
				$value->setSrHubOriginId($this->getSrHubOriginId());
				$value->setPropertyKey($key);
				$value->setPropertyValue($arguments[0]);
				$value->updateInto();
				$this->{$key} = $arguments[0];
			}
		}
	}


	/**
	 * @param string $str
	 *
	 * @return string
	 */
	private static function _fromCamelCase($str) {
		$str[0] = strtolower($str[0]);
		$func = create_function('$c', 'return "_" . strtolower($c[1]);');

		return preg_replace_callback('/([A-Z])/', $func, $str);
	}


	/**
	 * @param int $sr_hub_orign_id
	 */
	public function setSrHubOriginId($sr_hub_orign_id) {
		$this->sr_hub_origin_id = $sr_hub_orign_id;
	}


	/**
	 * @return int
	 */
	public function getSrHubOriginId() {
		return $this->sr_hub_origin_id;
	}


	/**
	 * @param string $prefix
	 */
	public function setPrefix($prefix) {
		$this->prefix = $prefix;
	}


	/**
	 * @return string
	 */
	public function getPrefix() {
		return $this->prefix;
	}
}

?>