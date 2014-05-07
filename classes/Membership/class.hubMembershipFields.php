<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/OriginProperties/class.hubOriginObjectPropertiesFields.php');

/**
 * Class hubMembershipFields
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.1.03
 */
class hubMembershipFields extends hubOriginObjectPropertiesFields {

	const GET_USR_ID_FROM_ORIGIN = 'get_usr_id_from_origin';
	const DESKTOP_NEW = 'desktop_new';
	const ADD_NOTIFICATION = 'add_notification';
	const NEW_SEND_MAIL_ADMIN = 'new_send_mail_admin';
	const NEW_SEND_MAIL_TUTOR = 'new_send_mail_tutor';
	const NEW_SEND_MAIL_MEMBER = 'new_send_mail_member';
	const UPDATE_ROLE = 'update_role';
	const UPDATE_NOTIFICATION = 'update_notification';
	const DESKTOP_UPDATED = 'desktop_updated';
	const UPDATED_SEND_MAIL_ADMIN = 'updated_send_mail_admin';
	const UPDATED_SEND_MAIL_TUTOR = 'updated_send_mail_tutor';
	const UPDATED_SEND_MAIL_MEMBER = 'updated_send_mail_member';
	const DELETE = 'delete';
	const DELETED_SEND_MAIL_ADMIN = 'deleted_send_mail_admin';
	const DELETED_SEND_MAIL_TUTOR = 'deleted_send_mail_tutor';
	const DELETED_SEND_MAIL_MEMBER = 'deleted_send_mail_member';
}

?>
