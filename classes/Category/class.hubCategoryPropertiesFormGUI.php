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
		$te = new ilTextInputGUI($this->pl->txt('cat_prop_base_node_ilias'), hubCategoryFields::BASE_NODE_ILIAS);
		$this->addItem($te);
		//
		$te = new ilTextInputGUI($this->pl->txt('cat_prop_base_node_external'), hubCategoryFields::BASE_NODE_EXTERNAL);
		$this->addItem($te);
		//
		$se = new ilSelectInputGUI($this->pl->txt('cat_prop_syncfield'), hubCategoryFields::SYNCFIELD);
		$opt = array(
			NULL => $this->pl->txt('cat_prop_syncfield_none'),
			'title' => $this->pl->txt('cat_prop_syncfield_title'),
		);
		$se->setOptions($opt);
		$this->addItem($se);
		//
		$h = new ilFormSectionHeaderGUI();
		$h->setTitle($this->pl->txt('common_on_status') . ' NEW');
		$this->addItem($h);
		//
		$cb = new ilCheckboxInputGUI($this->pl->txt('cat_prop_create_icon'), hubCategoryFields::CREATE_ICON);
		$this->addItem($cb);
		//
		$h = new ilFormSectionHeaderGUI();
		$h->setTitle($this->pl->txt('common_on_status') . ' UPDATED');
		$this->addItem($h);
		//
		$cb = new ilCheckboxInputGUI($this->pl->txt('cat_prop_move'), hubCategoryFields::MOVE);
		$this->addItem($cb);
		//
		$cb = new ilCheckboxInputGUI($this->pl->txt('cat_prop_update_title'), hubCategoryFields::UPDATE_TITLE);
		$this->addItem($cb);
		//
		$cb = new ilCheckboxInputGUI($this->pl->txt('cat_prop_update_description'), hubCategoryFields::UPDATE_DESCRIPTION);
		$this->addItem($cb);
		//
		//
		$cb = new ilCheckboxInputGUI($this->pl->txt('cat_prop_update_icon'), hubCategoryFields::UPDATE_ICON);
		$this->addItem($cb);
		//
		$h = new ilFormSectionHeaderGUI();
		$h->setTitle($this->pl->txt('common_on_status') . ' DELETED');
		$this->addItem($h);
		//
		$ro = new ilRadioGroupInputGUI($this->pl->txt('cat_prop_delete_mode'), hubCategoryFields::DELETE);
		$ro->setValue(hubCategory::DELETE_MODE_INACTIVE);
		$opt = new ilRadioOption($this->pl->txt('cat_prop_delete_mode_none'), NULL);
		$ro->addOption($opt);
		$opt = new ilRadioOption(sprintf($this->pl->txt('cat_prop_delete_mode_inactive'), $this->pl->txt('com_prop_mark_deleted_text')), hubCategory::DELETE_MODE_INACTIVE);
		{
			$m = new ilCheckboxInputGUI(sprintf($this->pl->txt('cat_prop_change_icon'),
				hubOrigin::getClassnameForOriginId($_GET['origin_id']) . '_deleted.png'), hubCategoryFields::DELETED_ICON);
			$opt->addSubItem($m);
		}
		$ro->addOption($opt);
		$opt = new ilRadioOption($this->pl->txt('cat_prop_delete_mode_delete'), hubCategory::DELETE_MODE_DELETE);
		$ro->addOption($opt);
		//
		$opt = new ilRadioOption($this->pl->txt('cat_prop_delete_mode_archive'), hubCategory::DELETE_MODE_ARCHIVE);
		$te = new ilTextInputGUI($this->pl->txt('cat_prop_delete_mode_archive_node'), hubCategoryFields::ARCHIVE_NODE);
		$opt->addSubItem($te);
		//$ro->addOption($opt); FSX TODO Archiv
		//
		$this->addItem($ro);
	}
}