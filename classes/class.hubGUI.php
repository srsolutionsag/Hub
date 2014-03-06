<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/class.ilHubPlugin.php');

/**
 * Main GUI-Class hubGUI
 *
 * @description
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 * @version           $Id:
 * @revision          $r:
 *
 * @ilCtrl_IsCalledBy hubGUI: ilRouterGUI
 * @ilCtrl_Calls      hubGUI: hubOriginGUI, hubSyncHistoryGUI, hubCourseGUI, hubUserGUI, hubCategoryGUI, hubLogGUI
 */
class hubGUI {

	/**
	 * @var ilCtrl
	 */
	protected $ctrl;
	/**
	 * @var ilTabsGUI
	 */
	protected $tabs;
	/**
	 * @var ilAccessHandler
	 */
	protected $access;
	/**
	 * @var ilTemplate
	 */
	protected $tpl;
	/**
	 * @var ilToolbarGUI
	 */
	protected $toolbar;


	public function __construct() {
		global $tpl, $ilCtrl, $ilToolbar, $ilTabs, $ilAccess;
		$this->tpl = $tpl;
		$this->ctrl = $ilCtrl;
		$this->toolbar = $ilToolbar;
		$this->tabs = $ilTabs;
		$this->access = $ilAccess;
		$this->pl = new ilHubPlugin();
		//		$this->pl->updateLanguages();
	}


	/**
	 * @param $next_class
	 */
	private function setTabs($next_class) {
		$this->tabs->addTab('origins', $this->pl->txt('origins'), $this->ctrl->getLinkTargetByClass('hubOriginGUI', 'index'));
		$this->tabs->addTab('hub_users', $this->pl->txt('hub_users'), $this->ctrl->getLinkTargetByClass('hubUserGUI', 'index'));
		$this->tabs->addTab('hub_categories', $this->pl->txt('hub_categories'), $this->ctrl->getLinkTargetByClass('hubCategoryGUI', 'index'));
		$this->tabs->addTab('hub_courses', $this->pl->txt('hub_courses'), $this->ctrl->getLinkTargetByClass('hubCourseGUI', 'index'));
		//		$this->tabs->addTab('log', $this->pl->txt('log'), $this->ctrl->getLinkTargetByClass('hubLogGUI', 'index'));
		switch ($next_class) {
			case 'huborigingui';
				$this->tabs->setTabActive('origins');
				break;
			case 'hubcoursegui';
				$this->tabs->setTabActive('hub_courses');
				break;
			case 'hubusergui';
				$this->tabs->setTabActive('hub_users');
				break;
			case 'hubcategorygui';
				$this->tabs->setTabActive('hub_categories');
				break;
			case 'hubloggui';
				$this->tabs->setTabActive('log');
				break;
		}
	}


	private function setTitleAndDescription() {
	}


	/**
	 * @param $cmd
	 */
	private function performCommand($cmd) {
		$this->{$cmd}();
	}


	/**
	 * @return bool
	 */
	public function executeCommand() {
		$cmd = $this->ctrl->getCmd();
		$next_class = $this->ctrl->getNextClass($this);
		$this->tpl->getStandardTemplate();
		$this->setTabs($next_class);
		$this->setTitleAndDescription();
		switch ($next_class) {
			case '':
				$this->performCommand($cmd);
				break;
			default:
				require_once($this->ctrl->lookupClassPath($next_class));
				if (! $cmd) {
					$this->ctrl->setCmd('index');
				}
				$gui = new $next_class($this);
				$this->ctrl->forwardCommand($gui);
				break;
		}

		return true;
	}
}

?>