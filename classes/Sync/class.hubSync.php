<?php

/**
 * Class hubSync
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.1.04
 */
class hubSync {

	const OUTPUT_CLI = 1;
	const OUTPUT_WEB = 2;
	const PHP_CLI = 1;
	const PHP_WEB = 2;
	/**
	 * @var int
	 */
	protected $sr_hub_origin_id = 0;
	/**
	 * @var bool
	 */
	protected static $dry_run = false;
	/**
	 * @var int
	 */
	protected $output = self::OUTPUT_CLI;
	/**
	 * @var int
	 */
	protected $php = self::PHP_CLI;
	/**
	 * @var array
	 */
	protected $usage_types = array();


	public function __construct() {
		if (hub::isCli()) {
			$this->setPhp(self::PHP_CLI);
			$this->setOutput(self::OUTPUT_CLI);
		} else {
			$this->setPhp(self::PHP_WEB);
			$this->setOutput(self::OUTPUT_WEB);
		}
	}



	//
	// Setter & Getter
	//

	/**
	 * @param boolean $dry_run
	 */
	public function setDryRun($dry_run) {
		$this->dry_run = $dry_run;
	}


	/**
	 * @return boolean
	 */
	public function getDryRun() {
		return $this->dry_run;
	}


	/**
	 * @param int $output
	 */
	public function setOutput($output) {
		$this->output = $output;
	}


	/**
	 * @return int
	 */
	public function getOutput() {
		return $this->output;
	}


	/**
	 * @param int $php
	 */
	public function setPhp($php) {
		$this->php = $php;
	}


	/**
	 * @return int
	 */
	public function getPhp() {
		return $this->php;
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
	 * @param array $usage_types
	 */
	public function setUsageTypes($usage_types) {
		$this->usage_types = $usage_types;
	}


	/**
	 * @return array
	 */
	public function getUsageTypes() {
		return $this->usage_types;
	}
}
