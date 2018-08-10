<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/class.hub.php');
require_once "Services/ActiveRecord/class.ActiveRecord.php";

/**
 * Class hubIcon
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
class hubIcon extends ActiveRecord {

	const SIZE_SMALL = 1;
	const SIZE_MEDIUM = 2;
	const SIZE_LARGE = 3;
	const USAGE_OBJECT = 1;
	const USAGE_FIRST_DEPENDENCE = 2;
	const USAGE_SECOND_DEPENDENCE = 3;
	const USAGE_THIRD_DEPENDENCE = 4;
	const PREF_SMALL = 'small';
	const PREF_MEDIUM = 'medium';
	const PREF_LARGE = 'large';
	const PREF_DEP = 'dep';
	const TABLE_NAME = "hub_icon";
	/**
	 * @var array
	 */
	protected static $size_prefixes = array(
		self::SIZE_SMALL => self::PREF_SMALL,
		self::SIZE_MEDIUM => self::PREF_MEDIUM,
		self::SIZE_LARGE => self::PREF_LARGE,
		self::SIZE_LARGE => self::PREF_LARGE,
	);
	/**
	 * @var array
	 */
	protected static $foldername = array(
		self::USAGE_OBJECT => 'obj',
		self::USAGE_FIRST_DEPENDENCE => 'dep_1',
		self::USAGE_SECOND_DEPENDENCE => 'dep_2',
		self::USAGE_THIRD_DEPENDENCE => 'dep_3',
	);


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
	 * @param string $type
	 *
	 * @return string
	 */
	public static function getFolderName($type) {
		return self::$foldername[$type];
	}


	/**
	 * @param hubOrigin $hubOrigin
	 */
	public static function getInstanceForOrigin(hubOrigin $hubOrigin) {
	}


	public static function initDir() {
		if (!is_dir(self::getIconDirectory())) {
			ilUtil::makeDirParents(self::getIconDirectory());
		}
	}


	/**
	 * @param bool $absolute
	 *
	 * @return string
	 */
	public static function getIconDirectory($absolute = false) {
		if ($absolute) {
			$path = ILIAS_ABSOLUTE_PATH . DIRECTORY_SEPARATOR;
		}

		return $path . ILIAS_WEB_DIR . '/' . CLIENT_ID . '/xhub/icons/';
	}


	/**
	 * @param string $path
	 * @param string $mode
	 *
	 * @throws Exception
	 */
	public function importFromPath($path, $mode = 'copy') {
		if (!$this->getId()) {
			throw new Exception('Cannot upload, please create hubIcon object first');
		}
		$this->setVersion($this->getVersion() + 1);
		$this->setDeleted(false);
		ilUtil::makeDirParents($this->getVersionDirectory(true));
		ilUtil::moveUploadedFile($path, $this->getfileName(), $this->getVersionDirectory(true) . $this->getfileName(), true, $mode);
		$this->update();
	}


	/**
	 * @param string $tmp_name
	 */
	public function importFromUpload($tmp_name) {
		$this->importFromPath($tmp_name, 'move_uploaded');
	}


	/**
	 * @return string
	 */
	public function getPath() {
		if ($this->getDeleted()) {
			return false;
		} else {
			$path = $this->getVersionDirectory() . '/image.' . $this->getSuffix();

			return $path;
		}
	}


	/**
	 * @return bool
	 */
	public function exists() {
		return is_file($this->getPath());
	}


	/**
	 * @var int
	 *
	 * @con_has_field          true
	 * @con_is_unique          true
	 * @con_is_primary         true
	 * @con_is_notnull         true
	 * @con_fieldtype          integer
	 * @con_length             4
	 * @con_sequence           true
	 */
	protected $id = 0;
	/**
	 * @var int
	 *
	 * @con_has_field          true
	 * @con_fieldtype          integer
	 * @con_length             8
	 * @con_index              true
	 */
	protected $sr_hub_origin_id = 0;
	/**
	 * @var string
	 *
	 * @con_has_field          true
	 * @con_fieldtype          text
	 * @con_length             64
	 */
	protected $name = '';
	/**
	 * @var string
	 *
	 * @con_has_field          true
	 * @con_fieldtype          text
	 * @con_length             128
	 */
	protected $suffix = 'png';
	/**
	 * @var int
	 *
	 * @con_has_field          true
	 * @con_fieldtype          integer
	 * @con_length             1
	 */
	protected $size_type = self::SIZE_LARGE;
	/**
	 * @var int
	 *
	 * @con_has_field          true
	 * @con_fieldtype          integer
	 * @con_length             1
	 */
	protected $usage_type = self::USAGE_OBJECT;
	/**
	 * @var int
	 *
	 * @con_has_field          true
	 * @con_fieldtype          integer
	 * @con_length             4
	 */
	protected $version = 1;
	/**
	 * @var bool
	 *
	 * @con_has_field          true
	 * @con_fieldtype          integer
	 * @con_length             1
	 */
	protected $deleted = false;


	/**
	 * @param int $id
	 */
	public function setId($id) {
		$this->id = $id;
	}


	/**
	 * @return int
	 */
	public function getId() {
		return $this->id;
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


	/**
	 * @param int $size_type
	 */
	public function setSizeType($size_type) {
		$this->size_type = $size_type;
	}


	/**
	 * @return int
	 */
	public function getSizeType() {
		return $this->size_type;
	}


	/**
	 * @param int $sr_hub_origin_id
	 */
	public function setSrHubOriginId($sr_hub_origin_id) {
		$this->sr_hub_origin_id = $sr_hub_origin_id;
	}


	/**
	 * @return int
	 */
	public function getSrHubOriginId() {
		return $this->sr_hub_origin_id;
	}


	/**
	 * @param string $suffix
	 */
	public function setSuffix($suffix) {
		$this->suffix = $suffix;
	}


	/**
	 * @return string
	 */
	public function getSuffix() {
		return $this->suffix;
	}


	/**
	 * @param int $usage_type
	 */
	public function setUsageType($usage_type) {
		$this->usage_type = $usage_type;
	}


	/**
	 * @return int
	 */
	public function getUsageType() {
		return $this->usage_type;
	}


	/**
	 * @param int $version
	 */
	public function setVersion($version) {
		$this->version = $version;
	}


	/**
	 * @return int
	 */
	public function getVersion() {
		return $this->version;
	}


	/**
	 * @param bool $absolute
	 *
	 * @return string
	 */
	public function getVersionDirectory($absolute = false) {
		return self::getIconDirectory($absolute) . $this->getId() . '/' . $this->getVersion();
	}


	/**
	 * @return string
	 */
	public function getfileName() {
		return '/image.' . $this->getSuffix();
	}


	/**
	 * @param boolean $deleted
	 */
	public function setDeleted($deleted) {
		$this->deleted = $deleted;
	}


	/**
	 * @return boolean
	 */
	public function getDeleted() {
		return $this->deleted;
	}
}
