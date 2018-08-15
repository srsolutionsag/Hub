<?php
require_once('./Services/UIComponent/classes/class.ilUserInterfaceHookPlugin.php');
require_once(__DIR__ . '/Configuration/class.hubConfig.php');
require_once __DIR__ . "/Origin/class.hubOriginConfiguration.php";
require_once __DIR__ . "/Origin/class.hubOrigin.php";
require_once __DIR__ . "/OriginProperties/class.hubOriginObjectPropertyValue.php";
require_once __DIR__ . "/Category/class.hubCategory.php";
require_once __DIR__ . "/Course/class.hubCourse.php";
require_once __DIR__ . "/Membership/class.hubMembership.php";
require_once __DIR__ . "/User/class.hubUser.php";
require_once __DIR__ . "/Sync/class.hubSyncHistory.php";
require_once __DIR__ . "/Configuration/class.hubConfig.php";
require_once __DIR__ . "/Icon/class.hubIcon.php";
require_once __DIR__ . "/Log/class.hubLog.php";
require_once __DIR__ . "/uninstall/class.hubRemoveDataConfirm.php";
require_once "Services/UIComponent/classes/class.ilUIPluginRouterGUI.php";
require_once "Services/Component/classes/class.ilObjComponentSettingsGUI.php";
require_once __DIR__ . "/class.hubGUI.php";
require_once __DIR__ . "/Origin/class.hubOriginGUI.php";

/**
 * Class ilHubPlugin
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.1.04
 */
class ilHubPlugin extends ilUserInterfaceHookPlugin {

	const PLUGIN_ID = "hub";
	const PLUGIN_NAME = "Hub";
	const UNINSTALL_REMOVE_HUB_DATA = "uninstall_remove_hub_data";
	/**
	 * @var ilHubPlugin
	 */
	protected static $instance;


	/**
	 * @return string
	 */
	function getPluginName() {
		return self::PLUGIN_NAME;
	}


	/**
	 * @return ilHubPlugin
	 */
	public static function getInstance() {
		if (!isset(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * @return bool
	 */
	public static function checkPreconditions() {
		return true;
	}


	/**
	 * @var ilCtrl
	 */
	protected $ctrl;
	/**
	 * @var ilDB
	 */
	protected $db;


	/**
	 *
	 */
	public function __construct() {
		parent::__construct();

		global $ilCtrl, $ilDB;

		$this->ctrl = $ilCtrl;
		$this->db = $ilDB;
	}


	/**
	 * @return bool
	 */
	public function beforeActivation() {
		return self::checkPreconditions();
	}


	/**
	 *
	 */
	public function updateLanguageFiles() {
		if (!in_array('SimpleXLSX', get_declared_classes())) {
			require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/lib/simplexlsx.class.php');
		}
		$path = substr(__FILE__, 0, strpos(__FILE__, 'classes')) . 'lang/';
		if (file_exists($path . 'lang_custom.xlsx')) {
			$file = $path . 'lang_custom.xlsx';
		} else {
			$file = $path . 'lang.xlsx';
		}
		$xslx = new SimpleXLSX($file);
		$new_lines = array();
		$keys = array();
		foreach ($xslx->rows() as $n => $row) {
			if ($n == 0) {
				$keys = $row;
				continue;
			}
			$data = $row;
			foreach ($keys as $i => $k) {
				if ($k != 'var' AND $k != 'part') {
					$new_lines[$k][] = $data[0] . '_' . $data[1] . '#:#' . $data[$i];
				}
			}
		}
		$start = '<!-- language file start -->' . PHP_EOL;
		$status = true;
		foreach ($new_lines as $lng_key => $lang) {
			$status = file_put_contents($path . 'ilias_' . $lng_key . '.lang', $start . implode(PHP_EOL, $lang));
		}

		if (!$status) {
			ilUtil::sendFailure('Language-Files could not be written');
		}
		$this->updateLanguages();
	}


	/**
	 * @param int $id
	 *
	 * @return ctrlmmEntryCtrl[]
	 */
	public static function getMenuEntries($id = 0) {

		global $rbacreview, $ilUser;
		if (!$rbacreview->isAssigned($ilUser->getId(), hubConfig::get(hubConfig::F_ADMIN_ROLES))) {
			return array();
		}

		$entries[$id] = array();
		$entries[0] = array();
		if (is_file('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/CtrlMainMenu/classes/EntryTypes/Ctrl/class.ctrlmmEntryCtrl.php')) {
			$hub_menu = new ctrlmmEntryCtrl();
			$hub_menu->setGuiClass(ilUIPluginRouterGUI::class . ',' . hubGUI::class . ',' . hubOriginGUI::class);
			$hub_menu->setTitle('HUB');
			$hub_menu->setPermissionType(ctrlmmMenu::PERM_ROLE);
			if (!function_exists('hubConfig::get')) {
				$hub_menu->setPermission(2);
			} else {
				$hub_menu->setPermission(hubConfig::get(hubConfig::F_ADMIN_ROLES));
			}
			$hub_menu->setPlugin(true);

			$entries[0][] = $hub_menu;
		}

		return $entries[$id];
	}


	/**
	 * @return bool
	 */
	protected function beforeUninstall() {
		$uninstall_remove_hub_data = hubConfig::get(self::UNINSTALL_REMOVE_HUB_DATA);

		if ($uninstall_remove_hub_data === NULL) {
			hubRemoveDataConfirm::saveParameterByClass();

			$this->ctrl->redirectByClass([
				ilUIPluginRouterGUI::class,
				hubRemoveDataConfirm::class
			], hubRemoveDataConfirm::CMD_CONFIRM_REMOVE_HUB_DATA);

			return false;
		}

		$uninstall_remove_hub_data = boolval($uninstall_remove_hub_data);

		if ($uninstall_remove_hub_data) {
			$this->removeHubData();
		} else {
			// Ask again if reinstalled
			hubConfig::remove(self::UNINSTALL_REMOVE_HUB_DATA);
		}

		return true;
	}


	/**
	 *
	 */
	protected function removeHubData() {
		$this->db->dropTable(hubOriginConfiguration::TABLE_NAME, false);
		$this->db->dropTable(hubOrigin::TABLE_NAME, false);
		$this->db->dropTable(hubOriginObjectPropertyValue::TABLE_NAME, false);
		$this->db->dropTable(hubCategory::TABLE_NAME, false);
		$this->db->dropTable(hubCourse::TABLE_NAME, false);
		$this->db->dropTable(hubMembership::TABLE_NAME, false);
		$this->db->dropTable(hubUser::TABLE_NAME, false);
		$this->db->dropTable(hubSyncHistory::TABLE_NAME, false);
		$this->db->dropTable(hubConfig::TABLE_NAME, false);
		$this->db->dropTable(hubIcon::TABLE_NAME, false);

		if (file_exists(hubLog::getFilePath())) {
			unlink(hubLog::getFilePath());
		}

		ilUtil::delDir(ILIAS_ABSOLUTE_PATH . '/' . ILIAS_WEB_DIR . '/' . CLIENT_ID . '/xhub');
	}
}
