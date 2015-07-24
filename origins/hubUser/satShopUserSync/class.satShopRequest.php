<?php

/**
 * Class satShopUser
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class satShopRequest {

	const SHOPID_PREFIX = 'shopid_';
	/**
	 * @var array
	 */
	protected $role_ids = array();
	/**
	 * @var string
	 */
	protected $request_email = '';
	/**
	 * @var int
	 */
	protected $shop_id = 0;
	/**
	 * @var int
	 */
	protected $uid = 0;
	/**
	 * @var string
	 */
	protected $country = '';
	/**
	 * @var string
	 */
	protected $counry_code = '';
	/**
	 * @var int
	 */
	protected $amount = 0;
	/**
	 * @var string
	 */
	protected $request_id = '';
	/**
	 * @var int
	 */
	protected $crs_ref_ids = 0;
	/**
	 * @var array
	 */
	protected static $ref_id_cache = array();
	/**
	 * @var array
	 */
	protected static $country_code_cache = array();


	/**
	 * @param satShopUserSync $satUserSync
	 */
	public function __construct(satShopUserSync $satUserSync) {
		$this->setRoleIds(array( $satUserSync->props()->get(satShopUserSync::F_ROLE_ID_SAT_SHOP) ));
	}


	public function initCrsRefIds() {
		$this->setCrsRefIds(self::getCrsRefIdsForShopId($this->getShopId()));
	}


	public function initCountryCode() {
		$this->setCounryCode(self::lookupCountryCode($this->getCountry()));
	}


	/**
	 * @param int $crs_ref_id
	 */
	public function setCrsRefIds($crs_ref_ids) {
		$this->crs_ref_ids = $crs_ref_ids;
	}


	/**
	 * @return array
	 */
	public function getCrsRefIds() {
		return $this->crs_ref_ids;
	}


	/**
	 * @param string $country
	 */
	public function setCountry($country) {
		$this->country = $country;
	}


	/**
	 * @return string
	 */
	public function getCountry() {
		return $this->country;
	}


	/**
	 * @param string $request_email
	 */
	public function setRequestEmail($request_email) {
		$this->request_email = $request_email;
	}


	/**
	 * @return string
	 */
	public function getRequestEmail() {
		return $this->request_email;
	}


	/**
	 * @param array $role_ids
	 */
	public function setRoleIds($role_ids) {
		$this->role_ids = $role_ids;
	}


	/**
	 * @return array
	 */
	public function getRoleIds() {
		return $this->role_ids;
	}


	/**
	 * @param int $ship_id
	 */
	public function setShopId($ship_id) {
		$this->shop_id = $ship_id;
	}


	/**
	 * @return int
	 */
	public function getShopId() {
		return $this->shop_id;
	}


	/**
	 * @param int $uid
	 */
	public function setUid($uid) {
		$this->uid = $uid;
	}


	/**
	 * @return int
	 */
	public function getUid() {
		return $this->uid;
	}


	/**
	 * @param int $amount
	 */
	public function setAmount($amount) {
		$this->amount = $amount;
	}


	/**
	 * @return int
	 */
	public function getAmount() {
		return $this->amount;
	}


	/**
	 * @param string $request_id
	 */
	public function setRequestId($request_id) {
		$this->request_id = $request_id;
	}


	/**
	 * @return string
	 */
	public function getRequestId() {
		return $this->request_id;
	}


	/**
	 * @param string $counry_code
	 */
	public function setCounryCode($counry_code) {
		$this->counry_code = $counry_code;
	}


	/**
	 * @return string
	 */
	public function getCounryCode() {
		return $this->counry_code;
	}


	/**
	 * @param $country
	 *
	 * @return mixed
	 */
	protected static function lookupCountryCode($country) {
		if (! isset(self::$country_code_cache[$country])) {
			global $ilDB;
			/**
			 * @var $ilDB ilDB
			 */
			$sql = "SELECT REPLACE(identifier, 'meta_c_', '') country_code
			FROM lng_data WHERE identifier LIKE 'meta_c%' AND value LIKE " . $ilDB->quote($country, 'text') . " LIMIT 0,1;";
			$set = $ilDB->query($sql);
			$ref_id = $ilDB->fetchObject($set);

			self::$country_code_cache[$country] = $ref_id->country_code;
		}

		return self::$country_code_cache[$country];
	}


	/**
	 * @param $shop_id
	 *
	 * @return mixed
	 */
	public static function getCrsRefIdsForShopId($shop_id) {
		if (! isset(self::$ref_id_cache[$shop_id])) {
			global $ilDB;
			/**
			 * @var $ilDB ilDB
			 */
			$sql = 'SELECT ref_id FROM il_meta_keyword
					JOIN object_reference ref ON ref.obj_id = il_meta_keyword.obj_id
					WHERE keyword = ' . $ilDB->quote(self::SHOPID_PREFIX . $shop_id, 'text') . '
					AND obj_type = ' . $ilDB->quote('crs', 'text');
			$set = $ilDB->query($sql);
			self::$ref_id_cache[$shop_id] = array();
			while ($ref_id = $ilDB->fetchObject($set)) {
				self::$ref_id_cache[$shop_id][] = $ref_id->ref_id;
			}


		}

		return self::$ref_id_cache[$shop_id];
	}
}

?>
