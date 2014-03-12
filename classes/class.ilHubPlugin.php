<?php

require_once('./Services/UIComponent/classes/class.ilUserInterfaceHookPlugin.php');

/**
 * Class ilHubPlugin
 */
class ilHubPlugin extends ilUserInterfaceHookPlugin {

	/**
	 * @return string
	 */
	function getPluginName() {
		return 'Hub';
	}


	/**
	 * @return bool
	 */
	public static function checkPreconditions() {
		/**
		 * @var $ilCtrl ilCtrl
		 */
		$path = strstr(__FILE__, 'Services', true) . 'Libraries/ActiveRecord/';
		global $ilCtrl;
		if ($ilCtrl->lookupClassPath('ilRouterGUI') === NULL OR ! is_file($path . 'class.ActiveRecord.php')
			OR ! is_file($path . 'class.ActiveRecordList.php')
		) {
			return false;
		}

		return true;
	}


	/**
	 * @return bool
	 */
	public function beforeActivation() {
		if (! self::checkPreconditions()) {
			ilUtil::sendFailure('hub needs ActiveRecord (https://svn.ilias.de/svn/ilias/branches/sr/ActiveRecord) and ilRouterGUI (https://svn.ilias.de/svn/ilias/branches/sr/Router)', true);

			return false;
		}

		return true;
	}


	/**
	 * @param int $id
	 *
	 * @return ctrlmmEntryCtrl[]
	 */
	public static function getMenuEntries($id = 0) {
		$entries = array();
		if (is_file('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/CtrlMainMenu/classes/EntryTypes/Ctrl/class.ctrlmmEntryCtrl.php')) {
			$hub_menu = new ctrlmmEntryCtrl();
			$hub_menu->setGuiClass('ilRouterGUI,hubGUI,hubOriginGUI');
			$hub_menu->setTitle('HUB');
			$hub_menu->setPermissionType(ctrlmmMenu::PERM_ROLE);
			$hub_menu->setPermission(2);
			$hub_menu->setPlugin(true);

			$entries[0][] = $hub_menu;
		}

		return $entries[$id];
	}
}

?>
