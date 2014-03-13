<?php
//require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Membership/class.hubMembership.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/OriginProperties/class.hubOriginObjectPropertiesFormGUI.php');

/**
 * Class hubMembershipPropertiesFormGUI
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
class hubMembershipPropertiesFormGUI extends hubOriginObjectPropertiesFormGUI {

	/**
	 * @return string
	 * @description return prefix of object_type like cat, usr, crs, mem
	 */
	protected function getPrefix() {
		return 'mem';
	}


	/**
	 * @description build FormElements
	 */
	protected function initForm() {
		$this->removeItemByPostVar('shortlink');

		$h = new ilFormSectionHeaderGUI();
		$h->setTitle($this->pl->txt('common_on_status') . ' NEW');
		$this->addItem($h);

		$cb = new ilCheckboxInputGUI($this->pl->txt('mem_prop_desktop_new'), 'desktop_new');
		$this->addItem($cb);

		$cb = new ilCheckboxInputGUI($this->pl->txt('mem_prop_add_notification'), 'add_notification');
		$this->addItem($cb);

		$h = new ilNonEditableValueGUI($this->pl->txt('mem_prop_new_send_mail_title'));
		$this->addItem($h);

		$cb = new ilCheckboxInputGUI($this->pl->txt('mem_prop_new_send_mail_admin'), 'new_send_mail_admin');
		$this->addItem($cb);

		$cb = new ilCheckboxInputGUI($this->pl->txt('mem_prop_new_send_mail_tutor'), 'new_send_mail_tutor');
		$this->addItem($cb);

		$cb = new ilCheckboxInputGUI($this->pl->txt('mem_prop_new_send_mail_member'), 'new_send_mail_member');
		$this->addItem($cb);

		$h = new ilFormSectionHeaderGUI();
		$h->setTitle($this->pl->txt('common_on_status') . ' UPDATED');
		$this->addItem($h);

		$cb_new = new ilCheckboxInputGUI($this->pl->txt('mem_prop_update_role'), 'update_role');

		$cb = new ilCheckboxInputGUI($this->pl->txt('mem_prop_update_notification'), 'update_notification');
		$cb_new->addSubItem($cb);

		$cb = new ilCheckboxInputGUI($this->pl->txt('mem_prop_desktop_updated'), 'desktop_updated');
		$cb_new->addSubItem($cb);

		$h = new ilNonEditableValueGUI($this->pl->txt('mem_prop_updated_send_mail_title'));
		$cb_new->addSubItem($h);

		$cb = new ilCheckboxInputGUI($this->pl->txt('mem_prop_updated_send_mail_admin'), 'updated_send_mail_admin');
		$cb_new->addSubItem($cb);

		$cb = new ilCheckboxInputGUI($this->pl->txt('mem_prop_updated_send_mail_tutor'), 'updated_send_mail_tutor');
		$cb_new->addSubItem($cb);

		$cb = new ilCheckboxInputGUI($this->pl->txt('mem_prop_updated_send_mail_member'), 'updated_send_mail_member');
		$cb_new->addSubItem($cb);

		$this->addItem($cb_new);

		$h = new ilFormSectionHeaderGUI();
		$h->setTitle($this->pl->txt('common_on_status') . ' DELETED');
		$this->addItem($h);

		$ro = new ilRadioGroupInputGUI($this->pl->txt('mem_prop_delete_mode'), 'delete');
		$opt = new ilRadioOption($this->pl->txt('mem_prop_delete_mode_none'), NULL);
		$ro->addOption($opt);
		$opt = new ilRadioOption($this->pl->txt('mem_prop_delete_mode_inactive'), hubCourse::DELETE_MODE_INACTIVE);
		//$ro->addOption($opt);
		$opt = new ilRadioOption($this->pl->txt('mem_prop_delete_mode_delete'), hubCourse::DELETE_MODE_DELETE);
		$ro->addOption($opt);
		$this->addItem($ro);

		$h = new ilNonEditableValueGUI($this->pl->txt('mem_prop_deleted_send_mail_title'));
		$this->addItem($h);

		$cb = new ilCheckboxInputGUI($this->pl->txt('mem_prop_deleted_send_mail_admin'), 'deleted_send_mail_admin');
		$this->addItem($cb);

		$cb = new ilCheckboxInputGUI($this->pl->txt('mem_prop_deleted_send_mail_tutor'), 'deleted_send_mail_tutor');
		$this->addItem($cb);

		$cb = new ilCheckboxInputGUI($this->pl->txt('mem_prop_deleted_send_mail_member'), 'deleted_send_mail_member');
		$this->addItem($cb);
	}
}