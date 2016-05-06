<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Configuration/class.hubConfig.php');

/**
 * ilHubAccess
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.1.04
 */
class ilHubAccess {

	/**
	 * @param int $user_id
	 *
	 * @return bool
	 */
	public static function checkAccess($user_id = null) {
		global $rbacreview;
		/**
		 * @var $ilUser     ilObjUser
		 * @var $rbacreview ilRbacReview
		 */
		if (!$user_id) {
			global $ilUser;
			$user_id = $ilUser->getId();
		}
		$roles = hubConfig::get(hubConfig::F_ADMIN_ROLES) ? hubConfig::get(hubConfig::F_ADMIN_ROLES) : 2;

		foreach (explode(',', $roles) as $role_id) {
			if (in_array($user_id, $rbacreview->assignedUsers($role_id))) {
				return true;
			}
		}

		return false;
	}
}

?>
