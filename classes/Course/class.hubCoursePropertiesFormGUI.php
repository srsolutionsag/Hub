<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/OriginProperties/class.hubOriginObjectPropertiesFormGUI.php');

/**
 * Class hubCoursePropertiesFormGUI
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
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
		$te = new ilTextInputGUI($this->pl->txt('crs_prop_node_noparent'), 'node_noparent');
		$this->addItem($te);
		//
		$h = new ilFormSectionHeaderGUI();
		$h->setTitle($this->pl->txt('common_on_status') . ' NEW');
		$this->addItem($h);

		$cb = new ilCheckboxInputGUI($this->pl->txt('crs_prop_activate'), 'activate');
		$this->addItem($cb);
		//
		$cb = new ilCheckboxInputGUI($this->pl->txt('crs_prop_create_icon'), 'create_icon');
		$this->addItem($cb);
		//
		$h = new ilFormSectionHeaderGUI();
		$h->setTitle($this->pl->txt('common_on_status') . ' UPDATED');
		$this->addItem($h);
		//
		$cb = new ilCheckboxInputGUI($this->pl->txt('crs_prop_move'), 'move');
		$this->addItem($cb);
		//
		$cb = new ilCheckboxInputGUI($this->pl->txt('crs_prop_update_title'), 'update_title');
		$this->addItem($cb);
		//
		$cb = new ilCheckboxInputGUI($this->pl->txt('crs_prop_update_description'), 'update_description');
		$this->addItem($cb);
		//
		$cb = new ilCheckboxInputGUI($this->pl->txt('crs_prop_update_icon'), 'update_icon');
		$this->addItem($cb);
		//
		$cb = new ilCheckboxInputGUI($this->pl->txt('crs_prop_reactivate'), 'reactivate');
		$this->addItem($cb);
		//
		$h = new ilFormSectionHeaderGUI();
		$h->setTitle($this->pl->txt('common_on_status') . ' DELETED');
		$this->addItem($h);
		$ro = new ilRadioGroupInputGUI($this->pl->txt('crs_prop_delete_mode'), 'delete');
		$ro->setValue(hubCourse::DELETE_MODE_INACTIVE);
		$opt = new ilRadioOption($this->pl->txt('crs_prop_delete_mode_none'), NULL);
		$ro->addOption($opt);
		$opt = new ilRadioOption($this->pl->txt('crs_prop_delete_mode_inactive'), hubCourse::DELETE_MODE_INACTIVE);
		{
			$m = new ilCheckboxInputGUI(sprintf($this->pl->txt('crs_prop_change_icon'),
				hubOrigin::getClassnameForOriginId($_GET['origin_id']) . '_deleted.png'), 'deleted_icon');
			//			$opt->addSubItem($m);
		}
		$ro->addOption($opt);
		$opt = new ilRadioOption($this->pl->txt('crs_prop_delete_mode_delete'), hubCourse::DELETE_MODE_DELETE);
		$ro->addOption($opt);
		$this->addItem($ro);
	}
}