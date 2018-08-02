<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/OriginProperties/class.hubOriginObjectPropertiesFields.php');

/**
 * Class hubUserFields
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 *
 * @version 1.1.04
 */
class hubUserFields extends hubOriginObjectPropertiesFields {

	const F_SYNCFIELD = 'syncfield';
	const F_LOGIN_FIELD = 'login_field';
	const F_ACTIVATE_ACCOUNT = 'activate_account';
	const F_CREATE_PASSWORD = 'create_password';
	const F_SEND_PASSWORD = 'send_password';
	const F_SEND_PASSWORD_FIELD = 'send_password_field';
	const F_PASSWORD_MAIL_SUBJECT = 'password_mail_subject';
	const F_PASSWORD_MAIL_BODY = 'password_mail_body';
	const F_PASSWORD_MAIL_DATE_FORMAT = 'password_mail_date_format';
	const F_REACTIVATE_ACCOUNT = 'reactivate_account';
	const F_UPDATE_FIRSTNAME = 'update_firstname';
	const F_UPDATE_LASTNAME = 'update_lastname';
	const F_UPDATE_EMAIL = 'update_email';
	const F_UPDATE_LOGIN = 'update_login';
	const F_DELETE = 'delete';
}
