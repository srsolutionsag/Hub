<?php

require_once "Services/Utilities/classes/class.ilConfirmationGUI.php";
require_once "Services/Administration/classes/class.ilAdministrationGUI.php";
require_once "Services/Component/classes/class.ilObjComponentSettingsGUI.php";
require_once __DIR__ . "/../Configuration/class.hubConfig.php";
require_once __DIR__ . "/../class.ilHubPlugin.php";
require_once "Services/Utilities/classes/class.ilUtil.php";

/**
 * Class hubRemoveDataConfirm
 *
 * @ilCtrl_isCalledBy hubRemoveDataConfirm: ilUIPluginRouterGUI
 */
class hubRemoveDataConfirm {

	const CMD_CANCEL = "cancel";
	const CMD_CONFIRM_REMOVE_HUB_DATA = "confirmRemoveHubData";
	const CMD_DEACTIVATE_HUB = "deactivateHub";
	const CMD_SET_KEEP_HUB_DATA = "setKeepHubData";
	const CMD_SET_REMOVE_HUB_DATA = "setRemoveHubData";


	/**
	 * @param bool $plugin
	 */
	public static function saveParameterByClass($plugin = true) {
		global $ilCtrl;

		$ref_id = filter_input(INPUT_GET, "ref_id");
		$ilCtrl->setParameterByClass(ilObjComponentSettingsGUI::class, "ref_id", $ref_id);
		$ilCtrl->setParameterByClass(self::class, "ref_id", $ref_id);

		if ($plugin) {
			$ctype = filter_input(INPUT_GET, "ctype");
			$ilCtrl->setParameterByClass(ilObjComponentSettingsGUI::class, "ctype", $ctype);
			$ilCtrl->setParameterByClass(self::class, "ctype", $ctype);

			$cname = filter_input(INPUT_GET, "cname");
			$ilCtrl->setParameterByClass(ilObjComponentSettingsGUI::class, "cname", $cname);
			$ilCtrl->setParameterByClass(self::class, "cname", $cname);

			$slot_id = filter_input(INPUT_GET, "slot_id");
			$ilCtrl->setParameterByClass(ilObjComponentSettingsGUI::class, "slot_id", $slot_id);
			$ilCtrl->setParameterByClass(self::class, "slot_id", $slot_id);

			$plugin_id = filter_input(INPUT_GET, "plugin_id");
			$ilCtrl->setParameterByClass(ilObjComponentSettingsGUI::class, "plugin_id", $plugin_id);
			$ilCtrl->setParameterByClass(self::class, "plugin_id", $plugin_id);

			$pname = filter_input(INPUT_GET, "pname");
			$ilCtrl->setParameterByClass(ilObjComponentSettingsGUI::class, "pname", $pname);
			$ilCtrl->setParameterByClass(self::class, "pname", $pname);
		}
	}


	/**
	 * @var ilCtrl
	 */
	protected $ctrl;
	/**
	 * @var ilHubPlugin
	 */
	protected $pl;
	/**
	 * @var ilTemplate
	 */
	protected $tpl;


	/**
	 *
	 */
	public function __construct() {
		global $ilCtrl, $tpl;

		$this->ctrl = $ilCtrl;
		$this->pl = ilHubPlugin::getInstance();
		$this->tpl = $tpl;
	}


	/**
	 *
	 */
	public function executeCommand() {
		$next_class = $this->ctrl->getNextClass($this);

		switch ($next_class) {
			default:
				$cmd = $this->ctrl->getCmd();

				switch ($cmd) {
					case self::CMD_CANCEL:
					case self::CMD_CONFIRM_REMOVE_HUB_DATA:
					case self::CMD_DEACTIVATE_HUB:
					case self::CMD_SET_KEEP_HUB_DATA:
					case self::CMD_SET_REMOVE_HUB_DATA:
						$this->{$cmd}();
						break;

					default:
						break;
				}
				break;
		}
	}


	/**
	 *
	 * @param string $html
	 */
	protected function show($html) {
		if ($this->ctrl->isAsynch()) {
			echo $html;

			exit();
		} else {
			$this->tpl->setContent($html);
			$this->tpl->getStandardTemplate();
			$this->tpl->show();
		}
	}


	/**
	 * @param string $cmd
	 */
	protected function redirectToPlugins($cmd) {
		self::saveParameterByClass($cmd !== "listPlugins");

		$this->ctrl->redirectByClass([
			ilAdministrationGUI::class,
			ilObjComponentSettingsGUI::class
		], $cmd);
	}


	/**
	 *
	 */
	protected function cancel() {
		$this->redirectToPlugins("listPlugins");
	}


	/**
	 *
	 */
	protected function confirmRemoveHubData() {
		self::saveParameterByClass();

		$confirmation = new ilConfirmationGUI();

		$confirmation->setFormAction($this->ctrl->getFormAction($this));

		$confirmation->setHeaderText($this->pl->txt("uninstall_confirm_remove_hub_data"));

		$confirmation->addItem("_", "_", $this->pl->txt("uninstall_hub_data"));

		$confirmation->addButton($this->pl->txt("uninstall_remove_hub_data"), self::CMD_SET_REMOVE_HUB_DATA);
		$confirmation->addButton($this->pl->txt("uninstall_keep_hub_data"), self::CMD_SET_KEEP_HUB_DATA);
		$confirmation->addButton($this->pl->txt("uninstall_deactivate_hub"), self::CMD_DEACTIVATE_HUB);
		$confirmation->setCancel($this->pl->txt("cancel"), self::CMD_CANCEL);

		$this->show($confirmation->getHTML());
	}


	/**
	 *
	 */
	protected function deactivateHub() {
		$this->redirectToPlugins("deactivatePlugin");
	}


	/**
	 *
	 */
	protected function setKeepHubData() {
		hubConfig::set(ilHubPlugin::UNINSTALL_REMOVE_HUB_DATA, false);

		ilUtil::sendInfo($this->pl->txt("uninstall_msg_kept_hub_data"), true);

		$this->redirectToPlugins("uninstallPlugin");
	}


	/**
	 *
	 */
	protected function setRemoveHubData() {
		hubConfig::set(ilHubPlugin::UNINSTALL_REMOVE_HUB_DATA, true);

		ilUtil::sendInfo($this->pl->txt("uninstall_msg_removed_hub_data"), true);

		$this->redirectToPlugins("uninstallPlugin");
	}
}
