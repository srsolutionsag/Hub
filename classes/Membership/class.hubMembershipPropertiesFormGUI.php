<?php
//require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Membership/class.hubMembership.php');

require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/OriginProperties/class.hubOriginObjectPropertiesFormGUI.php');

/**
 * Class hubMembershipPropertiesFormGUI
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.1.04
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

		$se = new ilSelectInputGUI($this->pl->txt('mem_prop_get_usr_id'), hubMembershipFields::GET_USR_ID_FROM_ORIGIN);

		//		$sync_only = new ilCheckboxInputGUI($this->txt(hubMembershipFields::F_SYNC_ONLY), hubMembershipFields::F_SYNC_ONLY);
		//		$sync_only_period_chooser =
		//		$this->addItem($sync_only);
		/**
		 * @var hubOrigin $origin
		 */
		$opt[0] = $this->pl->txt('common_none');
		foreach (hubOrigin::where(array( 'usage_type' => hub::OBJECTTYPE_USER ))->get() as $origin) {
			$opt[$origin->getId()] = $origin->getTitle();
		}
		$se->setOptions($opt);
		$this->addItem($se);

		$h = new ilFormSectionHeaderGUI();
		$h->setTitle($this->pl->txt('common_on_status') . ' NEW');
		$this->addItem($h);

		$cb = new ilCheckboxInputGUI($this->pl->txt('mem_prop_desktop_new'), hubMembershipFields::DESKTOP_NEW);
		$this->addItem($cb);

		$cb = new ilCheckboxInputGUI($this->pl->txt('mem_prop_add_notification'), hubMembershipFields::ADD_NOTIFICATION);
		$this->addItem($cb);

		$h = new ilNonEditableValueGUI($this->pl->txt('mem_prop_new_send_mail_title'));
		$this->addItem($h);

		$cb = new ilCheckboxInputGUI($this->pl->txt('mem_prop_new_send_mail_admin'), hubMembershipFields::NEW_SEND_MAIL_ADMIN);
		$this->addItem($cb);

		$cb = new ilCheckboxInputGUI($this->pl->txt('mem_prop_new_send_mail_tutor'), hubMembershipFields::NEW_SEND_MAIL_TUTOR);
		$this->addItem($cb);

		$cb = new ilCheckboxInputGUI($this->pl->txt('mem_prop_new_send_mail_member'), hubMembershipFields::NEW_SEND_MAIL_MEMBER);
		$this->addItem($cb);

		$h = new ilFormSectionHeaderGUI();
		$h->setTitle($this->pl->txt('common_on_status') . ' UPDATED');
		$this->addItem($h);

		$cb_new = new ilCheckboxInputGUI($this->pl->txt('mem_prop_update_role'), hubMembershipFields::UPDATE_ROLE);

		$cb = new ilCheckboxInputGUI($this->pl->txt('mem_prop_update_notification'), hubMembershipFields::UPDATE_NOTIFICATION);
		$cb_new->addSubItem($cb);

		$cb = new ilCheckboxInputGUI($this->pl->txt('mem_prop_desktop_updated'), hubMembershipFields::DESKTOP_UPDATED);
		$cb_new->addSubItem($cb);

		$h = new ilNonEditableValueGUI($this->pl->txt('mem_prop_updated_send_mail_title'));
		$cb_new->addSubItem($h);

		$cb = new ilCheckboxInputGUI($this->pl->txt('mem_prop_updated_send_mail_admin'), hubMembershipFields::UPDATED_SEND_MAIL_ADMIN);
		$cb_new->addSubItem($cb);

		$cb = new ilCheckboxInputGUI($this->pl->txt('mem_prop_updated_send_mail_tutor'), hubMembershipFields::UPDATED_SEND_MAIL_TUTOR);
		$cb_new->addSubItem($cb);

		$cb = new ilCheckboxInputGUI($this->pl->txt('mem_prop_updated_send_mail_member'), hubMembershipFields::UPDATED_SEND_MAIL_MEMBER);
		$cb_new->addSubItem($cb);

		$this->addItem($cb_new);

		$h = new ilFormSectionHeaderGUI();
		$h->setTitle($this->pl->txt('common_on_status') . ' DELETED');
		$this->addItem($h);

		$ro = new ilRadioGroupInputGUI($this->pl->txt('mem_prop_delete_mode'), hubMembershipFields::DELETE);
		$opt = new ilRadioOption($this->pl->txt('mem_prop_delete_mode_none'), null);
		$ro->addOption($opt);
		$opt = new ilRadioOption($this->pl->txt('mem_prop_delete_mode_inactive'), hubCourse::DELETE_MODE_INACTIVE);
		//$ro->addOption($opt);
		$opt = new ilRadioOption($this->pl->txt('mem_prop_delete_mode_delete'), hubCourse::DELETE_MODE_DELETE);
		$ro->addOption($opt);
		$opt = new ilRadioOption($this->pl->txt('mem_prop_delete_mode_delete_or_inactive'), hubCourse::DELETE_MODE_DELETE_OR_INACTIVE);
		$opt->setInfo($this->pl->txt('mem_prop_delete_mode_delete_or_inactive_info'));
		$ro->addOption($opt);
		$this->addItem($ro);

		$h = new ilNonEditableValueGUI($this->pl->txt('mem_prop_deleted_send_mail_title'));
		$this->addItem($h);

		$cb = new ilCheckboxInputGUI($this->pl->txt('mem_prop_deleted_send_mail_admin'), hubMembershipFields::DELETED_SEND_MAIL_ADMIN);
		$this->addItem($cb);

		$cb = new ilCheckboxInputGUI($this->pl->txt('mem_prop_deleted_send_mail_tutor'), hubMembershipFields::DELETED_SEND_MAIL_TUTOR);
		$this->addItem($cb);

		$cb = new ilCheckboxInputGUI($this->pl->txt('mem_prop_deleted_send_mail_member'), hubMembershipFields::DELETED_SEND_MAIL_MEMBER);
		$this->addItem($cb);
	}
}