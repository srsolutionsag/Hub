<?php
require_once('./Services/Utilities/classes/class.ilConfirmationGUI.php');
require_once('class.hubUser.php');
require_once('class.hubUserTableGUI.php');

/**
 * GUI-Class hubUserGUI
 *
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 * @version           1.1.04
 *
 */
class hubUserGUI {

	/**
	 * @var ilTabsGUI
	 */
	protected $tabs_gui;
	/**
	 * @var ilPropertyFormGUI
	 */
	protected $form;
	/**
	 * @var ilToolbarGUI
	 */
	protected $toolbar;
	/**
	 * @var ilCtrl
	 */
	protected $ctrl;
	/**
	 * @var ilTemplate
	 */
	protected $tpl;


	/**
	 * @param null $parent_gui
	 */
	public function __construct($parent_gui) {
		global $tpl, $ilCtrl, $ilToolbar, $lng, $ilTabs;
		$this->tpl = $tpl;
		$this->ctrl = $ilCtrl;
		$this->parent = $parent_gui;
		$this->toolbar = $ilToolbar;
		$this->tabs_gui = $ilTabs;
		$this->lng = $lng;
		$this->pl = ilHubPlugin::getInstance();
	}


	/**
	 * @return bool
	 */
	public function executeCommand() {
		if (ilHubPlugin::getBaseClass() != 'ilRouterGUI') {
			$this->tpl->getStandardTemplate();
		}

		$cmd = $this->ctrl->getCmd();
		$this->performCommand($cmd);

		if (ilHubPlugin::getBaseClass() != 'ilRouterGUI') {
			$this->tpl->show();
		}

		return true;
	}


	/**
	 * @param string $cmd
	 *
	 * @return mixed|void
	 */
	protected function performCommand($cmd) {
		// TODO Rechteprüfung
		$this->{$cmd}();
	}


	public function index() {
		$tableGui = new hubUserTableGUI($this, 'index');
		$this->tpl->setContent($tableGui->getHTML());
	}


	public function applyFilter() {
		$tableGui = new hubUserTableGUI($this, 'index');
		$tableGui->writeFilterToSession();
		$tableGui->resetOffset();
		$this->ctrl->redirect($this, 'index');
	}


	public function resetFilter() {
		$tableGui = new hubUserTableGUI($this, 'index');
		$tableGui->resetOffset();
		$tableGui->resetFilter();
		$this->ctrl->redirect($this, 'index');
	}
}

?>