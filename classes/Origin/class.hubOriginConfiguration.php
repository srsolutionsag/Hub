<?php
require_once('./Customizing/global/plugins/Libraries/ActiveRecord/class.ActiveRecord.php');

/**
 * Class hubOriginConfiguration
 *
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 *
 * @revision $r$
 */
class hubOriginConfiguration extends ActiveRecord {

	const SALT = 'bcb0b417e2ffb2a00a33e109fdeba4e5e7ef08bc55aa0cd8e021db54763051cb';
	/**
	 * @var string
	 * @db_has_field    true
	 * @db_is_notnull   true
	 * @db_fieldtype    integer
	 * @db_is_primary   true
	 * @db_length       4
	 */
	protected $sr_hub_origin_id;
	/**
	 * @var string
	 * @db_has_field    true
	 * @db_fieldtype    text
	 * @db_length       2000
	 */
	protected $file_path;
	/**
	 * @var string
	 * @db_has_field    true
	 * @db_fieldtype    text
	 * @db_length       2000
	 */
	protected $srv_username;
	/**
	 * @var string
	 * @db_has_field    true
	 * @db_fieldtype    text
	 * @db_length       2000
	 */
	protected $srv_password;
	/**
	 * @var string
	 * @db_has_field    true
	 * @db_fieldtype    text
	 * @db_length       2000
	 */
	protected $srv_host;
	/**
	 * @var string
	 * @db_has_field    true
	 * @db_fieldtype    text
	 * @db_length       2000
	 */
	protected $srv_database;
	/**
	 * @var string
	 * @db_has_field    true
	 * @db_fieldtype    text
	 * @db_length       2000
	 */
	protected $srv_port;
	/**
	 * @var string
	 * @db_has_field    true
	 * @db_fieldtype    text
	 * @db_length       2000
	 */
	protected $srv_instance;
	/**
	 * @var string
	 * @db_has_field    true
	 * @db_fieldtype    text
	 * @db_length       2000
	 */
	protected $srv_search_base;
	/**
	 * @var string
	 * @db_has_field    true
	 * @db_fieldtype    text
	 * @db_length       2000
	 */
	protected $notification_email;
	/**
	 * @var string
	 * @db_has_field    true
	 * @db_fieldtype    text
	 * @db_length       2000
	 */
	protected $summary_email;


	/**
	 * @param $sr_hub_origin_id
	 *
	 * @return hubOriginConfiguration
	 */
	public static function conf($sr_hub_origin_id) {
		$where = array( 'sr_hub_origin_id' => $sr_hub_origin_id );
		if (self::where($where)->hasSets()) {
			return self::where($where)->first();
		} else {
			$obj = new self();
			$obj->setSrHubOriginId($sr_hub_origin_id);

			return $obj;
		}
	}


	/**
	 * @param string $db_database
	 */
	public function setSrvDatabase($db_database) {
		$this->srv_database = $db_database;
	}


	/**
	 * @return string
	 */
	public function getSrvDatabase() {
		return $this->srv_database;
	}


	/**
	 * @param string $db_host
	 */
	public function setSrvHost($db_host) {
		$this->srv_host = $db_host;
	}


	/**
	 * @return string
	 */
	public function getSrvHost() {
		return $this->srv_host;
	}


	/**
	 * @param string $db_password
	 */
	public function setSrvPassword($db_password) {
		$this->srv_password = self::enc($db_password);
	}


	/**
	 * @return string
	 */
	public function getSrvPassword() {
		return self::dec($this->srv_password);
	}


	/**
	 * @param string $db_port
	 */
	public function setSrvPort($db_port) {
		$this->srv_port = $db_port;
	}


	/**
	 * @return string
	 */
	public function getSrvPort() {
		return $this->srv_port;
	}


	/**
	 * @param string $db_username
	 */
	public function setSrvUsername($db_username) {
		$this->srv_username = $db_username;
	}


	/**
	 * @return string
	 */
	public function getSrvUsername() {
		return $this->srv_username;
	}


	/**
	 * @param string $file_path
	 */
	public function setFilePath($file_path) {
		$this->file_path = $file_path;
	}


	/**
	 * @return string
	 */
	public function getFilePath() {
		return $this->file_path;
	}


	/**
	 * @param string $sr_hub_origin_id
	 */
	public function setSrHubOriginId($sr_hub_origin_id) {
		$this->sr_hub_origin_id = $sr_hub_origin_id;
	}


	/**
	 * @return string
	 */
	public function getSrHubOriginId() {
		return $this->sr_hub_origin_id;
	}


	/**
	 * @param string $srv_instance
	 */
	public function setSrvInstance($srv_instance) {
		$this->srv_instance = $srv_instance;
	}


	/**
	 * @return string
	 */
	public function getSrvInstance() {
		return $this->srv_instance;
	}


	/**
	 * @param string $srv_search_base
	 */
	public function setSrvSearchBase($srv_search_base) {
		$this->srv_search_base = $srv_search_base;
	}


	/**
	 * @return string
	 */
	public function getSrvSearchBase() {
		return $this->srv_search_base;
	}


	/**
	 * @param string $notification_email
	 */
	public function setNotificationEmail($notification_email) {
		$this->notification_email = $notification_email;
	}


	/**
	 * @return string
	 */
	public function getNotificationEmail() {
		return $this->notification_email;
	}


	/**
	 * @param string $summary_email
	 */
	public function setSummaryEmail($summary_email) {
		$this->summary_email = $summary_email;
	}


	/**
	 * @return string
	 */
	public function getSummaryEmail() {
		return $this->summary_email;
	}


	/**
	 * @return string
	 */
	static function returnDbTableName() {
		return 'sr_hub_origin_conf';
	}


	//
	// Helper
	//
	/**
	 * @param $text
	 *
	 * @return string
	 */
	private static function enc($text) {
		return trim(base64_encode(@mcrypt_encrypt(MCRYPT_RIJNDAEL_256, self::SALT, $text, MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND))));
	}


	/**
	 * @param $text
	 *
	 * @return string
	 */
	private static function dec($text) {
		return trim(@mcrypt_decrypt(MCRYPT_RIJNDAEL_256, self::SALT, base64_decode($text), MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND)));
	}
}

?>