<?php

require_once('./Services/Component/classes/class.ilPluginConfigGUI.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Configuration/class.hubConfigFormGUI.php');

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
		 * @var $ilCtrl ilCtrl
		 * @var $tpl    ilTemplate
		 * @var $ilTabs ilTabsGUI
		 */
		$this->ctrl = $ilCtrl;
		$this->tpl = $tpl;
		$this->tabs = &$ilTabs;
		$this->pl = ilHubPlugin::getInstance();
	}


	/**
	 * @param $cmd
	 */
	public function performCommand($cmd) {
		switch ($cmd) {
			case 'configure':
			case 'save':
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
			$this->ctrl->redirect($this, 'configure');
		}
		$this->tpl->setContent($form->getHTML());
	}
}

?>
