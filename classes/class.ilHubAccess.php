<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserinterfaceHook/Hub/classes/Configuration/class.hubConfig.php');
/**
 * ilHubAccess
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
class ilHubAccess {

	/**
	 * @param int $user_id
	 *
	 * @return bool
	 */
	public static function checkAccess($user_id = NULL) {
		global $rbacreview;
		/**
		 * @var $ilUser     ilObjUser
		 * @var $rbacreview ilRbacReview
		 */
		if (! $user_id) {
			global $ilUser;
			$user_id = $ilUser->getId();
		}
		$roles = hubConfig::get('admin_roles') ? hubConfig::get('admin_roles') : 2;

		foreach (explode(',', $roles) as $role_id) {
			if (in_array($user_id, $rbacreview->assignedUsers($role_id))) {
				return true;
			}
		}

		return false;
	}
}

?>
