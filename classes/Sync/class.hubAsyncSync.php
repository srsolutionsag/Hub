<?php

/**
 * Class hubAsyncSync
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class hubAsyncSync {

	/**
	 * @var string
	 */
	protected $user = '';
	/**
	 * @var string
	 */
	protected $password = '';
	/**
	 * @var string
	 */
	protected $client = '';
	/**
	 * @var string
	 */
	protected $cli_php;


	public function __construct() {
		$this->setCliPhp(hubConfig::get('async_cli_php') ? hubConfig::get('async_cli_php') : exec('which php'));
		$this->setUser(hubConfig::get('async_user'));
		$this->setPassword(hubConfig::get('async_password'));
		$this->setClient(hubConfig::get('async_client'));
	}


	public function run() {
		$cron = $this->getCliPhp() . ' ';
		$cron .= hub::getPath() . 'cron.php' . ' ';
		$cron .= $this->getUser() . ' ';
		$cron .= $this->getPassword() . ' ';
		$cron .= $this->getClient();
		$cron .= " > /dev/null &";

		exec($cron);
	}


	/**
	 * @param string $client
	 */
	public function setClient($client) {
		$this->client = $client;
	}


	/**
	 * @return string
	 */
	public function getClient() {
		return $this->client;
	}


	/**
	 * @param string $password
	 */
	public function setPassword($password) {
		$this->password = $password;
	}


	/**
	 * @return string
	 */
	public function getPassword() {
		return $this->password;
	}


	/**
	 * @param string $user
	 */
	public function setUser($user) {
		$this->user = $user;
	}


	/**
	 * @return string
	 */
	public function getUser() {
		return $this->user;
	}


	/**
	 * @param string $cli_php
	 */
	public function setCliPhp($cli_php) {
		$this->cli_php = $cli_php;
	}


	/**
	 * @return string
	 */
	public function getCliPhp() {
		return $this->cli_php;
	}
}

?>
