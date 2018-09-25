<?php

require_once "Services/Utilities/classes/class.ilConfirmationGUI.php";
require_once "Services/Administration/classes/class.ilAdministrationGUI.php";
require_once "Services/Component/classes/class.ilObjComponentSettingsGUI.php";
require_once __DIR__ . "/../Configuration/class.hubConfig.php";
require_once __DIR__ . "/../class.ilHubPlugin.php";
require_once "Services/Utilities/classes/class.ilUtil.php";

use srag\RemovePluginDataConfirm\AbstractRemovePluginDataConfirm;

/**
 * Class hubRemoveDataConfirm
 *
 * @ilCtrl_isCalledBy hubRemoveDataConfirm: ilUIPluginRouterGUI
 */
class hubRemoveDataConfirm extends AbstractRemovePluginDataConfirm {

	const PLUGIN_CLASS_NAME = ilHubPlugin::class;


	/**
	 * @inheritdoc
	 */
	public function getUninstallRemovesData()/*: ?bool*/ {
		return hubConfig::get(self::KEY_UNINSTALL_REMOVES_DATA);
	}


	/**
	 * @inheritdoc
	 */
	public function setUninstallRemovesData(/*bool*/
		$uninstall_removes_data)/*: void*/ {
		hubConfig::set(self::KEY_UNINSTALL_REMOVES_DATA, false);
	}


	/**
	 * @inheritdoc
	 */
	public function removeUninstallRemovesData()/*: void*/ {
		hubConfig::remove(self::KEY_UNINSTALL_REMOVES_DATA);
	}
}
