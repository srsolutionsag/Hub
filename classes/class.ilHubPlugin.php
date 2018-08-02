<?php
require_once('./Services/UIComponent/classes/class.ilUserInterfaceHookPlugin.php');
require_once(dirname(__FILE__) . '/Configuration/class.hubConfig.php');

/**
 * Class ilHubPlugin
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.1.04
 */
class ilHubPlugin extends ilUserInterfaceHookPlugin {

	const PLUGIN_ID = "hub";
	const PLUGIN_NAME = "Hub";
	/**
	 * @var ilHubPlugin
	 */
	protected static $instance;
	/**
	 * @var string
	 */
	protected static $baseClass;


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
		/**
		 * @var ilCtrl $ilCtrl
		 */
		if (self::getBaseClass() == false) {
			ilUtil::sendFailure('hub needs ILIAS >= 4.5 OR for ILIAS < 4.5 ilRouterGUI (https://svn.ilias.de/svn/ilias/branches/sr/Router)', true);

			return false;
		}

		return true;
	}


	/**
	 * @var ilDB
	 */
	protected $db;


	/**
	 *
	 */
	public function __construct() {
		parent::__construct();

		global $ilDB;

		$this->db = $ilDB;
	}


	/**
	 * @return bool
	 */
	public function beforeActivation() {
		return self::checkPreconditions();
	}


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
			$hub_menu->setGuiClass(self::getBaseClass() . ',hubGUI,hubOriginGUI');
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
	 * @var string
	 *
	 * In what class the command/ctrl chain should start for this plugin.
	 *
	 * This will return ilRouterGUI for ILIAS <= 4.4 if the corresponding plugin is installed
	 * and ilUIPluginRouterGUI for ILIAS >= 4.5 and false otherwise.
	 *
	 * @return string
	 */
	public static function getBaseClass() {
		if (self::$baseClass !== NULL) {
			return self::$baseClass;
		}

		global $ilCtrl;
		if ($ilCtrl->lookupClassPath('ilUIPluginRouterGUI')) {
			self::$baseClass = 'ilUIPluginRouterGUI';
		} elseif ($ilCtrl->lookupClassPath('ilRouterGUI')) {
			self::$baseClass = 'ilRouterGUI';
		} else {
			self::$baseClass = false;
		}

		return self::$baseClass;
	}


	/**
	 * @return bool
	 */
	protected function beforeUninstall() {
		require_once "Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Origin/class.hubOriginConfiguration.php";
		require_once "Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Origin/class.hubOrigin.php";
		require_once "Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/OriginProperties/class.hubOriginObjectPropertyValue.php";
		require_once "Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Category/class.hubCategory.php";
		require_once "Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Course/class.hubCourse.php";
		require_once "Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Membership/class.hubMembership.php";
		require_once "Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/User/class.hubUser.php";
		require_once "Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Sync/class.hubSyncHistory.php";
		require_once "Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Configuration/class.hubConfig.php";
		require_once "Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Icon/class.hubIcon.php";
		require_once "Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Log/class.hubLog.php";

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

		return true;
	}
}
