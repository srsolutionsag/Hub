<?php

require_once('./Services/Component/classes/class.ilPluginConfigGUI.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Configuration/class.hubConfigFormGUI.php');
require_once __DIR__ . "/Configuration/class.hubConfGUI.php";
require_once __DIR__ . "/class.ilHubPlugin.php";

/**
 * Hub Configuration
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.1.04
 *
 */
class ilHubConfigGUI extends ilPluginConfigGUI {

	public function __construct() {
		global $ilCtrl, $tpl, $ilTabs;
		/**
		 * @var ilCtrl     $ilCtrl
		 * @var ilTemplate $tpl
		 * @var ilTabsGUI  $ilTabs
		 */
		$this->ctrl = $ilCtrl;
		$this->tpl = $tpl;
		$this->tabs = &$ilTabs;
		$this->pl = ilHubPlugin::getInstance();
	}


	/**
	 * @param string $cmd
	 */
	public function performCommand($cmd) {
		switch ($cmd) {
			case hubConfGUI::CMD_CONFIGURE:
			case hubConfGUI::CMD_SAVE:
			case hubConfGUI::CMD_CANCEL:
				$this->$cmd();
				break;
		}
	}


	protected function configure() {
		$form = new hubConfigFormGUI($this);
		$form->fillForm();
		$this->tpl->setContent($form->getHTML());
	}


	protected function save() {
		$form = new hubConfigFormGUI($this);
		$form->setValuesByPost();
		if ($form->saveObject()) {
			$this->ctrl->redirect($this, hubConfGUI::CMD_CONFIGURE);
		}
		$this->tpl->setContent($form->getHTML());
	}


	protected function cancel() {
		$this->configure();
	}
}
