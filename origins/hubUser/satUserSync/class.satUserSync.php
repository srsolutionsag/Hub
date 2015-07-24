<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Origin/class.hubOrigin.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Origin/int.hubOriginInterface.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/User/class.hubUser.php');
require_once('class.satUser.php');

/**
 * ClasssatUserSync
 *
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.
 */
class satUserSync extends hubOrigin implements hubOriginInterface {

	const FILENAME = 'ILIAS-E.txt';


	/**
	 * @return bool
	 * @description Connect to your Service, return bool status
	 */
	public function connect() {
		$return = false;
		if (is_readable($this->conf->getFilePath() . self::FILENAME)) {
			$return = true;
		}

		return $return;
	}


	/**
	 * @return bool
	 * @description read your Data an save in Class
	 */
	public function parseData() {
		$file_path = $this->conf->getFilePath() . self::FILENAME;
		$i = 0;
		foreach (file($file_path) as $i => $line) {
			if ($i == 0) {
				continue;
			}
			$entry = str_getcsv($line, ";");
			$satUser = new satUser($this);
			$satUser->setMatriculation($entry[0]); // MRX Mitarbeiternummer
			$satUser->setFirstname(utf8_encode($entry[1]));
			$satUser->setLastname(utf8_encode($entry[2]));
			$satUser->setBirthday($entry[3]);
			$satUser->setFourlc($entry[4]);
			$satUser->setGender($entry[5]);
			$satUser->setGrades(explode(',', $entry[6]));
			$satUser->setEmail($entry[7]);

			$this->data[] = $satUser;
			$i ++;
		}

		$this->setChecksum($i - 1); // -1 due the first line

		return true;
	}


	/**
	 * @return int
	 * @description read Checksum of your Data and return int Count
	 */
	public function getChecksum() {
		return $this->checksum;
	}


	/**
	 * @return satUser[]
	 * @description return array of Data
	 */
	public function getData() {
		return $this->data;
	}


	/**
	 * @return bool
	 */
	public function buildEntries() {
		foreach ($this->getData() as $satUser) {
			$hubUser = new hubUser($satUser->getFourlc());
			$hubUser->setAccountType(hubUser::ACCOUNT_TYPE_SHIB);
			$hubUser->setLastname($satUser->getLastname());
			$hubUser->setFirstname($satUser->getFirstname());
			$hubUser->setEmail($satUser->getEmail());
			$hubUser->setExternalAccount($satUser->getFourlc());
			$hubUser->setGender($satUser->getGender());
			$hubUser->setIliasRoles($satUser->getRoleIdsForGrades());
			$hubUser->setMatriculation($satUser->getMatriculation()); // MRX: Mitarbeiternummer

			$hubUser->create($this);
		}

		return true;
	}


	/**
	 * @param ilPropertyFormGUI $form_gui
	 *
	 * @return ilPropertyFormGUI|void
	 */
	public static function appendFieldsToPropForm(ilPropertyFormGUI $form_gui) {
		$te = new ilTextInputGUI('Role-ID Cockpit', 'role_id_cockpit');
		$te->setRequired(true);
		$form_gui->addItem($te);

		$te = new ilTextInputGUI('Role-ID Cabin', 'role_id_cabin');
		$te->setRequired(true);
		$form_gui->addItem($te);

		$te = new ilTextInputGUI('Role-ID Special', 'role_id_special');
		$te->setRequired(true);
		$form_gui->addItem($te);
	}
}
