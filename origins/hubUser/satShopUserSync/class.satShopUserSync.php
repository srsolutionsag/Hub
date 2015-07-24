<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Origin/class.hubOrigin.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Origin/int.hubOriginInterface.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/User/class.hubUser.php');
require_once('class.satShopRequest.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Membership/class.hubMembership.php');

/**
 * Class satShopUserSync
 *
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
class satShopUserSync extends hubOrigin implements hubOriginInterface {

	const F_SAT_ARCHIVE = 'sat_archive';
	const F_ROLE_ID_SAT_SHOP = 'role_id_sat_shop';
	/**
	 * @var array
	 */
	protected $file_paths = array();
	/**
	 * @var satShopRequest[]
	 */
	protected $data;
	/**
	 * @var int
	 */
	protected static $x = 0;


	/**
	 * @return bool
	 * @description Connect to your Service, return bool status
	 */
	public function connect() {
		$path = $this->conf->getFilePath();
		if (is_readable($path)) {
			foreach (glob($path . '/*.CSV') as $file) {
				if (is_readable($file)) {
					$this->file_paths[] = $file;
				}
			}
		}

		return true;
	}


	/**
	 * @return bool
	 * @description read your Data an save in Class
	 */
	public function parseData() {
		foreach ($this->file_paths as $file_path) {
			foreach (file($file_path) as $i => $line) {
				$entry = str_getcsv($line, ";");
				$satShopRequest = new satShopRequest($this);
				$satShopRequest->setRequestEmail($entry[0]);
				$satShopRequest->setCountry($entry[1]);
				$satShopRequest->setUid($entry[2]);
				$satShopRequest->setAmount($entry[3]);
				$satShopRequest->setShopId($entry[4]);
				$satShopRequest->setRequestId($entry[2]);
				$satShopRequest->initCrsRefIds();
				$satShopRequest->initCountryCode();
				$this->data[] = $satShopRequest;
				$this->checksum ++;
			}
		}

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
		$existing = hubUser::where(array( 'sr_hub_origin_id' => $this->getId() ))->count() + 100001;
		$satShopMembership = new hubOrigin($this->props()->get(hubOriginObjectPropertiesFields::F_ORIGIN_LINK));
		foreach ($this->data as $satShopRequest) {
			for ($x = self::$x; $x < self::$x + $satShopRequest->getAmount(); $x ++) {
				if ($satShopRequest->getCrsRefIds()) {
					$usr_ext_id = $satShopRequest->getRequestId() . '_' . $x;
					$hubUser = new hubUser($usr_ext_id);
					$hubUser->setCountry($satShopRequest->getCountry());
					$hubUser->setSelCountry($satShopRequest->getCounryCode());
					while(ilObjUser::_loginExists('WBT' . $existing)) {
						$existing ++;
					}
					$hubUser->setLogin('WBT' . $existing);
					$hubUser->setIliasRoles($satShopRequest->getRoleIds());
					$hubUser->setEmailPassword($satShopRequest->getRequestEmail());
					$hubUser->setTimeLimitUntil(strtotime('today', time() + (16 * 24 * 3600)) - 1);
					$hubUser->setTimeLimitUnlimited(false);
					$hubUser->create($this);
					$existing ++;
					foreach ($satShopRequest->getCrsRefIds() as $ref_id) {
						$hubMembership = hubMembership::getInstance($usr_ext_id, $ref_id);
						$hubMembership->setContainerId($ref_id);
						$hubMembership->setContainerRole(hubMembership::CONT_ROLE_CRS_MEMBER);
						$hubMembership->create($satShopMembership);
					}
				} else {
					hubLog::getInstance()->write('SAT-Shop: No Crs_ref-ID found for Request: ' . $satShopRequest->getRequestId(), hubLog::L_DEBUG);
				}
			}
			self::$x = $x;
		}

		return true;
	}


	/**
	 * @return bool
	 */
	public function afterSync() {
		$archive_path = $this->props()->get(self::F_SAT_ARCHIVE) . DIRECTORY_SEPARATOR;
		if (is_writable($archive_path)) {
			foreach ($this->file_paths as $file_path) {
				$new_path = $archive_path . basename($file_path);
				rename($file_path, $new_path);
			}

			return true;
		} else {
			return false;
		}
	}


	/**
	 * @param ilPropertyFormGUI $form_gui
	 *
	 * @return ilPropertyFormGUI|void
	 */
	public static function appendFieldsToPropForm(ilPropertyFormGUI $form_gui) {
		$te = new ilTextInputGUI('Role-ID sat_shop', self::F_ROLE_ID_SAT_SHOP);
		$te->setRequired(true);
		$form_gui->addItem($te);
		$te = new ilTextInputGUI('Archiv', self::F_SAT_ARCHIVE);
		$te->setRequired(true);
		$form_gui->addItem($te);
	}
}
