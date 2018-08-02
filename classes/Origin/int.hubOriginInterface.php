<?php

/**
 * Class hubOriginInterface
 *
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.1.04
 *
 * @revision $r$
 */
interface hubOriginInterface {

	/**
	 * @return bool
	 * @description Connect to your Service, return bool status
	 */
	public function connect();


	/**
	 * @return bool
	 * @description read your Data an save in Class
	 */
	public function parseData();


	/**
	 * @return int
	 * @description read Checksum of your Data and return int Count
	 */
	public function getChecksum();


	/**
	 * @return array
	 * @description return array of Data
	 */
	public function getData();


	/**
	 * @param array $data
	 *
	 * @return void
	 */
	public function setData(array $data);


	/**
	 * @return bool
	 */
	public function buildEntries();
}
