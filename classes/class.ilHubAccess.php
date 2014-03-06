<?php

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

		return in_array($user_id, $rbacreview->assignedUsers(2));
	}
}

?>
