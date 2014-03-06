<?php

require_once('./Services/UIComponent/classes/class.ilUserInterfaceHookPlugin.php');

/**
 * Class ilHubPlugin
 */
class ilHubPlugin extends ilUserInterfaceHookPlugin {

	/**
	 * @return string
	 */
	function getPluginName() {
		return 'Hub';
	}


	/**
	 * @return bool
	 */
	public static function checkPreconditions() {
		/**
		 * @var $ilCtrl ilCtrl
		 */
		$path = strstr(__FILE__, 'Services', true) . 'Libraries/ActiveRecord/';
		global $ilCtrl;
		if ($ilCtrl->lookupClassPath('ilRouterGUI') === NULL OR ! is_file($path . 'class.ActiveRecord.php')
			OR ! is_file($path . 'class.ActiveRecordList.php')
		) {
			return false;
		}

		return true;
	}


	/**
	 * @return bool
	 */
	public function beforeActivation() {
		if (! self::checkPreconditions()) {
			ilUtil::sendFailure('hub needs ActiveRecord (https://svn.ilias.de/svn/ilias/branches/sr/ActiveRecord) and ilRouterGUI (https://svn.ilias.de/svn/ilias/branches/sr/Router)', true);

			return false;
		}

		return true;
	}
}

?>
