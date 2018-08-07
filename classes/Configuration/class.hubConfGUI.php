<?php
require_once('class.hubConfigFormGUI.php');

/**
 * Class hubConfGUI
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.1.04
 */
class hubConfGUI {

	const CMD_INDEX = 'index';
	const CMD_CONFIGURE = 'configure';
	const CMD_SAVE = 'save';
	const CMD_CANCEL = 'cancel';
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
		//		if(ilHubAccess::checkAccess()) {
		$this->{$cmd}();
		//		}
	}


	public function index() {
		$form = new hubConfigFormGUI($this);
		$form->fillForm();
		$this->tpl->setContent($form->getHTML());
	}


	protected function save() {
		$form = new hubConfigFormGUI($this);
		$form->setValuesByPost();
		if ($form->saveObject()) {
			$this->ctrl->redirect($this, self::CMD_INDEX);
		}
		$this->tpl->setContent($form->getHTML());
	}
}
