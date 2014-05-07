<?php
require_once('./Services/UIComponent/classes/class.ilUserInterfaceHookPlugin.php');

/**
 * Class ilHubPlugin
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.1.03
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
		if ($ilCtrl->lookupClassPath('ilRouterGUI') === NULL) {
			ilUtil::sendFailure('hub needs ilRouterGUI (https://svn.ilias.de/svn/ilias/branches/sr/Router)', true);

			return false;
		}
		if (! is_file($path . 'class.ActiveRecord.php') OR ! is_file($path . 'class.ActiveRecordList.php')) {
			ilUtil::sendFailure('hub needs ActiveRecord (https://svn.ilias.de/svn/ilias/branches/sr/ActiveRecord) ', true);

			return false;
		}

		return true;
	}


	/**
	 * @return bool
	 */
	public function beforeActivation() {
		return self::checkPreconditions();
	}


	public function updateLanguageFiles() {
		if (! in_array('SimpleXLSX', get_declared_classes())) {
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

		if (! $status) {
			ilUtil::sendFailure('Language-Files coul\'d not be written');
		}
		$this->updateLanguages();
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
			if (! function_exists('hubConfig::get')) {
				$hub_menu->setPermission(2);
			} else {
				$hub_menu->setPermission(hubConfig::get(hubConfig::F_ADMIN_ROLES));
			}
			$hub_menu->setPlugin(true);

			$entries[0][] = $hub_menu;
		}

		return $entries[$id];
	}
}

?>
