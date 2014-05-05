<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Course/class.hubCourseFields.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/OriginProperties/class.hubOriginObjectPropertiesFormGUI.php');

/**
 * Class hubCoursePropertiesFormGUI
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.1.02
 */
class hubCoursePropertiesFormGUI extends hubOriginObjectPropertiesFormGUI {

	/**
	 * @return string
	 * @description return prefix of object_type like cat, usr, crs, mem
	 */
	protected function getPrefix() {
		return 'crs';
	}


	/**
	 * @description build FormElements
	 */
	protected function initForm() {
		//
		$te = new ilTextInputGUI($this->pl->txt('crs_prop_node_noparent'), hubCourseFields::F_NODE_NOPARENT);
		$this->addItem($te);
		//
		$h = new ilFormSectionHeaderGUI();
		$h->setTitle($this->pl->txt('common_on_status') . ' NEW');
		$this->addItem($h);

		$cb = new ilCheckboxInputGUI($this->pl->txt('crs_prop_activate'), hubCourseFields::F_ACTIVATE);
		$this->addItem($cb);
		//
		$cb = new ilCheckboxInputGUI($this->pl->txt('crs_prop_create_icon'), hubCourseFields::F_CREATE_ICON);
		$this->addItem($cb);
		//
		$h = new ilFormSectionHeaderGUI();
		$h->setTitle($this->pl->txt('common_on_status') . ' UPDATED');
		$this->addItem($h);
		//
		$cb = new ilCheckboxInputGUI($this->pl->txt('crs_prop_move'), hubCourseFields::F_MOVE);
		$this->addItem($cb);
		//
		$cb = new ilCheckboxInputGUI($this->pl->txt('crs_prop_update_title'), hubCourseFields::F_UPDATE_TITLE);
		$this->addItem($cb);
		//
		$cb = new ilCheckboxInputGUI($this->pl->txt('crs_prop_update_description'), hubCourseFields::F_UPDATE_DESCRIPTION);
		$this->addItem($cb);
		//
		$cb = new ilCheckboxInputGUI($this->pl->txt('crs_prop_update_icon'), hubCourseFields::F_UPDATE_ICON);
		$this->addItem($cb);
		//
		$cb = new ilCheckboxInputGUI($this->pl->txt('crs_prop_reactivate'), hubCourseFields::F_REACTIVATE);
		$this->addItem($cb);
		//
		$h = new ilFormSectionHeaderGUI();
		$h->setTitle($this->pl->txt('common_on_status') . ' DELETED');
		$this->addItem($h);
		//
		//
		$delete = new ilRadioGroupInputGUI($this->pl->txt('crs_prop_delete_mode'), hubCourseFields::F_DELETE);
		//
		$opt_none = new ilRadioOption($this->pl->txt('crs_prop_delete_mode_none'), NULL);
		$delete->addOption($opt_none);

		$opt_inactive = new ilRadioOption($this->pl->txt('crs_prop_delete_mode_inactive'), hubCourse::DELETE_MODE_INACTIVE);
		$delete->addOption($opt_inactive);
		//
		$opt_delete = new ilRadioOption($this->pl->txt('crs_prop_delete_mode_delete'), hubCourse::DELETE_MODE_DELETE);
		$delete->addOption($opt_delete);
		//
		$this->addItem($delete);
	}
}