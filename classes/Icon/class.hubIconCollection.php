<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Icon/class.hubIcon.php');

/**
 * Class hubIconCollection
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
class hubIconCollection {

	/**
	 * @var int
	 */
	protected $usage_type = 0;


	/**
	 * @param hubOrigin $hubOrigin
	 * @param int $usage
	 *
	 * @return hubIconCollection
	 */
	public static function getInstance(hubOrigin $hubOrigin, $usage = hubIcon::USAGE_OBJECT) {
		$return = new self();
		$return->setUsageType($usage);

		$where = array(
			'sr_hub_origin_id' => $hubOrigin->getId(),
			'usage_type'       => $usage,
		);
		$small_list = hubIcon::where($where)->where(array( 'size_type' => hubIcon::SIZE_SMALL ));
		if (!$small_list->hasSets()) {
			$small = new hubIcon();
			$small->setSizeType(hubIcon::SIZE_SMALL);
			$small->setSrHubOriginId($hubOrigin->getId());
			$small->setUsageType($usage);
			$small->create();
			$return->setSmall($small);
		} else {
			$return->setSmall($small_list->first());
		}

		$medium_list = hubIcon::where($where)->where(array( 'size_type' => hubIcon::SIZE_MEDIUM ));
		if (!$medium_list->hasSets()) {
			$small = new hubIcon();
			$small->setSizeType(hubIcon::SIZE_MEDIUM);
			$small->setSrHubOriginId($hubOrigin->getId());
			$small->setUsageType($usage);
			$small->create();
			$return->setMedium($small);
		} else {
			$return->setMedium($medium_list->first());
		}

		$large_list = hubIcon::where($where)->where(array( 'size_type' => hubIcon::SIZE_LARGE ));
		if (!$large_list->hasSets()) {
			$small = new hubIcon();
			$small->setSizeType(hubIcon::SIZE_LARGE);
			$small->setSrHubOriginId($hubOrigin->getId());
			$small->setUsageType($usage);
			$small->create();
			$return->setLarge($small);
		} else {
			$return->setLarge($large_list->first());
		}

		return $return;
	}


	/**
	 * @var hubIcon
	 */
	protected $small;
	/**
	 * @var hubIcon
	 */
	protected $medium;
	/**
	 * @var hubIcon
	 */
	protected $large;


	/**
	 * @param hubIcon $large
	 */
	public function setLarge($large) {
		$this->large = $large;
	}


	/**
	 * @return hubIcon
	 */
	public function getLarge() {
		return $this->large;
	}


	/**
	 * @param hubIcon $medium
	 */
	public function setMedium($medium) {
		$this->medium = $medium;
	}


	/**
	 * @return hubIcon
	 */
	public function getMedium() {
		return $this->medium;
	}


	/**
	 * @param hubIcon $small
	 */
	public function setSmall($small) {
		$this->small = $small;
	}


	/**
	 * @return hubIcon
	 */
	public function getSmall() {
		return $this->small;
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
}

?>
