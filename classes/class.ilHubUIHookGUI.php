<?php
require_once('./Services/UIComponent/classes/class.ilUIHookPluginGUI.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Shortlink/class.hubShortlink.php');

/**
 * Class ilHubUIHookGUI
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.1.04
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
		$this->pl = ilHubPlugin::getInstance();
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

		//$ilUser is not necesseraly defined (access for newsfeed etc.)
		if (!$ilUser) {
			return array();
		}
		$is_admin = in_array($ilUser->getId(), $rbacreview->assignedUsers(2));

		if ($a_comp == 'Services/MainMenu' AND $a_part == 'main_menu_search' AND $is_admin) {
			$link = $this->ctrl->getLinkTargetByClass(array(
				ilHubPlugin::getBaseClass(),
				'hubGUI',
				'hubOriginGUI',
			), 'index');

			$plugins = ilPluginAdmin::getActivePluginsForSlot("Services", "UIComponent", "uihk");
			if (!in_array('CtrlMainMenu', $plugins)) {
				$mode = ilUIHookPluginGUI::APPEND;
			} else {
				$mode = ilUIHookPluginGUI::KEEP;
			}

			return array(
				'mode' => $mode,
				'html' => '<a href=\'' . $link . '\'>HUB</a>',
			);
		}

		return array( 'mode' => ilUIHookPluginGUI::KEEP, 'html' => '' );
	}


	public function gotoHook() {
		if (preg_match("/^uihk_hub_(.*)/uim", $_GET['target'], $matches)) {
			$token = $matches[1];
			hubShortlink::redirect($token, false);
		}
	}
}

?>
