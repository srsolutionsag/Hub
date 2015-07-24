<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Origin/class.hubOrigin.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Origin/int.hubOriginInterface.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/User/class.hubUser.php');

/**
 * Class csvDemo
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
class csvDemo extends hubOrigin implements hubOriginInterface {

	/**
	 * @return bool
	 * @description Connect to your Service, return bool status
	 */
	public function connect() {
		return is_readable(dirname(__FILE__) . DIRECTORY_SEPARATOR . $this->conf->getFilePath());
	}


	/**
	 * @return bool
	 * @description read your Data an save in Class
	 */
	public function parseData() {
		foreach (file(dirname(__FILE__) . DIRECTORY_SEPARATOR . $this->conf->getFilePath()) as $i => $line) {
			$this->data[] = str_getcsv($line, ";");
		}

		$this->checksum = $i + 1;

		return true;
	}


	/**
	 * @return bool
	 */
	public function buildEntries() {
		foreach ($this->data as $dat) {
			$hubUser = new hubUser($dat[0]);
			$hubUser->setFirstname($dat[1]);
			$hubUser->setLastname($dat[2]);
			$hubUser->setEmail($dat[3]);
			$hubUser->setPasswd($dat[4]);
			$hubUser->create($this);
		}

		return true;
	}
}
