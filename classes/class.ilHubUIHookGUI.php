<?php
require_once('./Services/UIComponent/classes/class.ilUIHookPluginGUI.php');

/**
 * Class ilHubUIHookGUI
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
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
	function getHTML($a_comp, $a_part, $a_par = array()) {
		global $ilUser;
		if ($a_comp == 'Services/MainMenu' AND
			$a_part == 'main_menu_search' AND ($ilUser->getId() == 6 OR $ilUser->getId() == 22409)
		) {
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

			return array(
				'mode' => ilUIHookPluginGUI::KEEP,
				'html' => '<div class=\'FSXrequestWorkspaceHeader\'>
				<a href=\'' . $link . '\'>HUB</a></div>'
			);
		}

		return array( 'mode' => ilUIHookPluginGUI::KEEP, 'html' => '' );
	}
}

?>
