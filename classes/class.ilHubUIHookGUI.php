<?php
require_once('./Services/UIComponent/classes/class.ilUIHookPluginGUI.php');

/**
 * Class ilHubUIHookGUI
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.1.03
 */
class ilHubUIHookGUI extends ilUIHookPluginGUI {

	/**
	 * @var ilCtrl
	 */
	protected $ctrl;
	/**
	 * @var $ilTabs
	 */
	protected $tabs;
	/**
	 * @var ilAccessHandler
	 */
	protected $access;


	function __construct() {
		global $ilCtrl, $ilTabs, $ilAccess;
		/**
		 * @var $ilCtrl ilCtrl
		 */
		$this->ctrl = $ilCtrl;
		$this->tabs = $ilTabs;
		$this->access = $ilAccess;
		$this->pl = new ilHubPlugin();
	}


	/**
	 * @param       $a_comp
	 * @param       $a_part
	 * @param array $a_par
	 *
	 * @return array
	 */
	public function getHTML($a_comp, $a_part, $a_par = array()) {
		global $ilUser, $rbacreview, $ilCtrl;

		$is_admin = in_array($ilUser->getId(), $rbacreview->assignedUsers(2));

		if ($a_comp == 'Services/MainMenu' AND $a_part == 'main_menu_search' AND $is_admin) {
			$ctrlTwo = new ilCtrl();
			$ctrlTwo->setTargetScript('ilias.php');
			$a_base_class = $_GET['baseClass'];
			$cmd = $_GET['cmd'];
			$cmdClass = $_GET['cmdClass'];
			$cmdNode = $_GET['cmdNode'];
			$ctrlTwo->initBaseClass('ilRouterGUI');
			$link = $ctrlTwo->getLinkTargetByClass(array(
				'ilRouterGUI',
				'hubGUI',
				'hubOriginGUI'
			), 'index');
			$_GET['baseClass'] = $a_base_class;
			$_GET['cmd'] = $cmd;
			$_GET['cmdClass'] = $cmdClass;
			$_GET['cmdNode'] = $cmdNode;


			$link = $this->ctrl->getLinkTargetByClass(array(
				'ilRouterGUI',
				'hubGUI',
				'hubOriginGUI'
			), 'index');


			$plugins = ilPluginAdmin::getActivePluginsForSlot("Services", "UIComponent", "uihk");
			if (! in_array('CtrlMainMenu', $plugins)) {
				$mode = ilUIHookPluginGUI::APPEND;
			} else {
				$mode = ilUIHookPluginGUI::KEEP;
			}

			return array(
				'mode' => $mode,
				'html' => '<a href=\'' . $link . '\'>HUB</a>'
			);
		}

		return array( 'mode' => ilUIHookPluginGUI::KEEP, 'html' => '' );
	}
}

?>
