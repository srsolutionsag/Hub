<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/User/class.hubUserFields.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/OriginProperties/class.hubOriginObjectPropertiesFormGUI.php');

/**
 * Class hubCoursePropertiesFormGUI
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.1.04
 */
class hubUserPropertiesFormGUI extends hubOriginObjectPropertiesFormGUI {

	/**
	 * @return string
	 * @description return prefix of object_type like cat, usr, crs, mem
	 */
	protected function getPrefix() {
		return 'usr';
	}


	/**
	 * @description build FormElements
	 */
	protected function initForm() {
		$syncfield = new ilSelectInputGUI($this->pl->txt('usr_prop_syncfield'), hubUserFields::F_SYNCFIELD);
		$opt = array(
			NULL => $this->pl->txt('usr_prop_syncfield_none'),
			'email' => 'E-Mail',
			'external_account' => 'External Account',
			//			'matriculation' => 'Matriculation',
		);
		$syncfield->setOptions($opt);
		$this->addItem($syncfield);
		//
		$syncfield = new ilSelectInputGUI($this->pl->txt('usr_prop_login_field'), hubUserFields::F_LOGIN_FIELD);
		$opt = array(
			NULL => $this->pl->txt('usr_prop_login_field_none'),
			'email' => 'E-Mail',
			'external_account' => 'External Account',
			'ext_id' => 'Externe ID',
			'first_and_lastname' => 'vorname.nachname',
			'own' => 'hub-Field login',
		);
		$syncfield->setOptions($opt);
		$this->addItem($syncfield);
		//
		$header = new ilFormSectionHeaderGUI();
		$header->setTitle($this->pl->txt('common_on_status') . ' NEW');
		$this->addItem($header);
		//
		$activate = new ilCheckboxInputGUI($this->pl->txt('usr_prop_activate_account'), hubUserFields::F_ACTIVATE_ACCOUNT);
		$this->addItem($activate);
		//
		//
		$activate = new ilCheckboxInputGUI($this->pl->txt('usr_prop_create_password'), hubUserFields::F_CREATE_PASSWORD);
		$this->addItem($activate);
		$send_password = new ilCheckboxInputGUI($this->pl->txt('usr_prop_send_password'), hubUserFields::F_SEND_PASSWORD);
		$syncfield = new ilSelectInputGUI($this->pl->txt('usr_prop_send_password_field'), hubUserFields::F_SEND_PASSWORD_FIELD);
		$opt = array(
			'email' => 'email',
			'external_account' => 'external_account',
			'email_password' => 'email_password',
		);
		$syncfield->setOptions($opt);
		$activate->addSubItem($syncfield);

		$subject = new ilTextInputGUI($this->pl->txt('usr_prop_password_mail_subject'), hubUserFields::F_PASSWORD_MAIL_SUBJECT);
		$send_password->addSubItem($subject);
		$mail_body = new ilTextareaInputGUI($this->pl->txt('usr_prop_password_mail_body'), hubUserFields::F_PASSWORD_MAIL_BODY);
		$mail_body->setInfo($this->pl->txt('usr_prop_password_mail_placeholders') . ': [LOGIN], [PASSWORD], [VALID_UNTIL], [COURSE_LINK]');
		$mail_body->setCols(80);
		$mail_body->setRows(15);
		$send_password->addSubItem($mail_body);
		$mail_date_format = new ilTextInputGUI($this->pl->txt('usr_prop_password_mail_date_format'), hubUserFields::F_PASSWORD_MAIL_DATE_FORMAT);
		$mail_date_format->setInfo('<a target=\'_blank\' href=\'http://php.net/manual/de/function.date.php\'>Info</a>');
		$send_password->addSubItem($mail_date_format);
		$this->addItem($send_password);
		//
		//
		$header = new ilFormSectionHeaderGUI();
		$header->setTitle($this->pl->txt('common_on_status') . ' UPDATED');
		$this->addItem($header);
		//
		$activate = new ilCheckboxInputGUI($this->pl->txt('usr_prop_reactivate_account'), hubUserFields::F_REACTIVATE_ACCOUNT);
		$this->addItem($activate);
		//
		$activate = new ilCheckboxInputGUI($this->pl->txt('usr_prop_update_firstname'), hubUserFields::F_UPDATE_FIRSTNAME);
		$this->addItem($activate);
		//
		$activate = new ilCheckboxInputGUI($this->pl->txt('usr_prop_update_lastname'), hubUserFields::F_UPDATE_LASTNAME);
		$this->addItem($activate);
		//
		$activate = new ilCheckboxInputGUI($this->pl->txt('usr_prop_update_email'), hubUserFields::F_UPDATE_EMAIL);
		$this->addItem($activate);
		//
		$activate = new ilCheckboxInputGUI($this->pl->txt('usr_prop_update_login'), hubUserFields::F_UPDATE_LOGIN);
		$this->addItem($activate);
		//
		//
		$header = new ilFormSectionHeaderGUI();
		$header->setTitle($this->pl->txt('common_on_status') . ' DELETED');
		$this->addItem($header);
		$delete = new ilRadioGroupInputGUI($this->pl->txt('usr_prop_delete_mode'), hubUserFields::F_DELETE);
		$delete->setValue(hubUser::DELETE_MODE_INACTIVE);
		$opt = new ilRadioOption($this->pl->txt('usr_prop_delete_mode_none'), NULL);
		$delete->addOption($opt);
		$opt = new ilRadioOption($this->pl->txt('usr_prop_delete_mode_inactive'), hubUser::DELETE_MODE_INACTIVE);
		$delete->addOption($opt);
		$opt = new ilRadioOption($this->pl->txt('usr_prop_delete_mode_delete'), hubUser::DELETE_MODE_DELETE);
		$delete->addOption($opt);
		$this->addItem($delete);
	}
}
