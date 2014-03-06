<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/OriginProperties/class.hubOriginObjectPropertiesFormGUI.php');

/**
 * Class hubOriginFormGUI
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
class hubCategoryPropertiesFormGUI extends hubOriginObjectPropertiesFormGUI {

	/**
	 * @return string
	 * @description return prefix of object_type like cat, usr, crs, mem
	 */
	protected function getPrefix() {
		return 'cat';
	}


	/**
	 * @description build FormElements
	 */
	protected function initForm() {
		$h = new ilFormSectionHeaderGUI();
		$h->setTitle($this->pl->txt('cat_prop_header_base'));
		$this->addItem($h);
		//
		$te = new ilTextInputGUI($this->pl->txt('cat_prop_base_node_ilias'), 'base_node_ilias');
		$this->addItem($te);
		//
		$te = new ilTextInputGUI($this->pl->txt('cat_prop_base_node_external'), 'base_node_external');
		$this->addItem($te);
		//
		$se = new ilSelectInputGUI($this->pl->txt('cat_prop_syncfield'), 'syncfield');
		$opt = array(
			NULL => $this->pl->txt('cat_prop_syncfield_none'),
			'title' => $this->pl->txt('cat_prop_syncfield_title'),
		);
		$se->setOptions($opt);
		$this->addItem($se);
		//
		$h = new ilFormSectionHeaderGUI();
		$h->setTitle($this->pl->txt('on_status') . ' NEW');
		$this->addItem($h);
		//
		$cb = new ilCheckboxInputGUI($this->pl->txt('cat_prop_create_icon'), 'create_icon');
		$this->addItem($cb);
		//
		$h = new ilFormSectionHeaderGUI();
		$h->setTitle($this->pl->txt('on_status') . ' UPDATED');
		$this->addItem($h);
		//
		$cb = new ilCheckboxInputGUI($this->pl->txt('cat_prop_move'), 'move');
		$this->addItem($cb);
		//
		$cb = new ilCheckboxInputGUI($this->pl->txt('cat_prop_update_title'), 'update_title');
		$this->addItem($cb);
		//
		$cb = new ilCheckboxInputGUI($this->pl->txt('cat_prop_update_description'), 'update_description');
		$this->addItem($cb);
		//
		//
		$cb = new ilCheckboxInputGUI($this->pl->txt('cat_prop_update_icon'), 'update_icon');
		$this->addItem($cb);
		//
		$h = new ilFormSectionHeaderGUI();
		$h->setTitle($this->pl->txt('on_status') . ' DELETED');
		$this->addItem($h);
		//
		$ro = new ilRadioGroupInputGUI($this->pl->txt('cat_prop_delete_mode'), 'delete');
		$ro->setValue(hubCategory::DELETE_MODE_INACTIVE);
		$opt = new ilRadioOption($this->pl->txt('cat_prop_delete_mode_none'), NULL);
		$ro->addOption($opt);
		$opt = new ilRadioOption(sprintf($this->pl->txt('cat_prop_delete_mode_inactive'), $this->pl->txt('ilias_object_mark_deleted')), hubCategory::DELETE_MODE_INACTIVE);
		{
			$m = new ilCheckboxInputGUI(sprintf($this->pl->txt('cat_prop_change_icon'),
				hubOrigin::getClassnameForOriginId($_GET['origin_id']) . '_deleted.png'), 'deleted_icon');
			$opt->addSubItem($m);
		}
		$ro->addOption($opt);
		$opt = new ilRadioOption($this->pl->txt('cat_prop_delete_mode_delete'), hubCategory::DELETE_MODE_DELETE);
		$ro->addOption($opt);
		//
		$opt = new ilRadioOption($this->pl->txt('cat_prop_delete_mode_archive'), hubCategory::DELETE_MODE_ARCHIVE);
		$te = new ilTextInputGUI($this->pl->txt('cat_prop_delete_mode_archive_node'), 'archive_node');
		$opt->addSubItem($te);
		//$ro->addOption($opt); FSX Archiv
		//
		$this->addItem($ro);
	}
}