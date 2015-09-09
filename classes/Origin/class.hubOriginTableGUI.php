<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Table/class.hubAbstractTableGUI.php');
require_once('class.hubOrigin.php');
require_once('./Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Sync/class.hubAsyncSync.php');

/**
 * TableGUI srModelObjectTableGUI
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.1.04
 *
 */
class hubOriginTableGUI extends hubAbstractTableGUI {

	const DEV = true;


	protected function initTableData() {
		$this->setData(hubOrigin::getArray());
	}


	/**
	 * @return bool
	 * @description returns false, if automatic columns are needed, otherwise implement your columns
	 */
	protected function initTableColumns() {
		$this->addColumn($this->pl->txt('origin_table_header_active'));
		$this->addColumn($this->pl->txt('origin_table_header_title'));
		$this->addColumn($this->pl->txt('origin_table_header_description'));
		$this->addColumn($this->pl->txt('origin_table_header_usage_type'));
		$this->addColumn($this->pl->txt('origin_table_header_last_update'));
		$this->addColumn($this->pl->txt('origin_table_header_duration'));
		$this->addColumn($this->pl->txt('origin_table_header_duration_objects'));
		$this->addColumn($this->pl->txt('origin_table_header_count'));
		$this->addColumn($this->pl->txt('common_actions'));
	}


	/**
	 * @return bool
	 */
	protected function initTableFilter() {
		return false;
	}


	protected function initTableProperties() {
		$this->table_title = $this->pl->txt('origin_table_title');
		$this->table_id = 'origins';
		$this->prefix = 'origins';
	}


	/**
	 * @return bool
	 * @description return false or implements own form action and
	 */
	protected function initFormActionsAndCmdButtons() {
		global $ilToolbar;
		/**
		 * @var $ilToolbar ilToolbarGUI
		 */
		$ilToolbar->setFormAction($this->ctrl->getFormAction($this->parent_obj), true);
		$ilToolbar->addButton($this->pl->txt('origin_table_button_add'), $this->ctrl->getLinkTarget($this->parent_obj, 'add'));

		if (hubConfig::isImportEnabled()) {
			$import = new ilFileInputGUI('import', 'import_file');
			$import->setSuffixes(array( 'json', 'zip' ));
			$ilToolbar->addInputItem($import);
			$ilToolbar->addFormButton($this->pl->txt('origin_table_button_import'), 'import');
		}
		$this->setFormAction($this->ctrl->getFormAction($this->parent_obj));
		if (hubConfig::get(hubConfig::F_USE_ASYNC)) {
			$this->addCommandButton('runAsync', $this->pl->txt('origin_table_button_run') . ' (Async)');
		}
		$this->addCommandButton('run', $this->pl->txt('origin_table_button_run'));
		$this->addCommandButton('dryRun', $this->pl->txt('origin_table_button_dryrun'));
		$this->addCommandButton('deactivateAll', $this->pl->txt('origin_table_button_deactivate_all'));
		$this->addCommandButton('activateAll', $this->pl->txt('origin_table_button_activate_all'));
	}


	/**
	 * @return bool
	 */
	protected function initTableRowTemplate() {
		return false;
	}


	protected function initLanguage() {
		$this->pl = new ilHubPlugin();
	}


	/**
	 * @param $a_set
	 *
	 * @return bool
	 * @description implement your woen fillRow or return false
	 */
	protected function fillTableRow($a_set) {
		self::$num ++;
		/**
		 * @var $hubOrigin hubOrigin
		 */
		$hubOrigin = hubOrigin::find($a_set['id']);
		$this->ctrl->setParameter($this->parent_obj, 'origin_id', $hubOrigin->getId());
		$this->ctrl->setParameterByClass('hubIconGUI', 'origin_id', $hubOrigin->getId());
        if(hubConfig::is50()){
            $img = $hubOrigin->getActive() ? ilUtil::img(ilUtil::getImagePath('icon_ok.svg')) : ilUtil::img(ilUtil::getImagePath('icon_not_ok.svg'));
        }else{
            $img = $hubOrigin->getActive() ? ilUtil::img(ilUtil::getImagePath('icon_ok.png')) : ilUtil::img(ilUtil::getImagePath('icon_not_ok.png'));
        }
		$img_link = $hubOrigin->getActive() ? $this->ctrl->getLinkTarget($this->parent_obj, 'deactivate') : $this->ctrl->getLinkTarget($this->parent_obj, 'activate');
		$this->addCell('<a href=\'' . $img_link . '\'>' . $img . '</a>');
		$this->addCell('<a href=\'' . $this->ctrl->getLinkTarget($this->parent_obj, 'edit') . '\'>' . $hubOrigin->getTitle() . '</a>');
		$this->addCell($hubOrigin->getShortDescription());
		$this->addCell($this->pl->txt('origin_form_field_usage_type_' . $hubOrigin->getUsageType()));
		$this->addCell($hubOrigin->getLastUpdate());
		$duration = $hubOrigin->getDuration() ? $hubOrigin->getDuration() : 0;
		$duration_objects = $hubOrigin->getDurationObjects() ? $hubOrigin->getDurationObjects() : 0;
		$this->addCell($duration . ' s.');
		$this->addCell($duration_objects . ' s.');
		$this->addCell($hubOrigin->getCountOfHubObjects());
		$actions = new ilAdvancedSelectionListGUI();
		$actions->setId('actions_' . self::$num);
		$actions->setListTitle($this->pl->txt('common_actions'));
		$actions->addItem($this->pl->txt('common_edit'), 'edit', $this->ctrl->getLinkTarget($this->parent_obj, 'edit'));
		if ($hubOrigin->getActive()) {
			$actions->addItem($this->pl->txt('common_deactivate'), 'deactivate', $this->ctrl->getLinkTarget($this->parent_obj, 'deactivate'));
		} else {
			$actions->addItem($this->pl->txt('common_activate'), 'activate', $this->ctrl->getLinkTarget($this->parent_obj, 'activate'));
		}
		$actions->addItem($this->pl->txt('common_delete'), 'delete', $this->ctrl->getLinkTarget($this->parent_obj, 'confirmDelete'));
		if (hubConfig::isImportEnabled()) {
			$actions->addItem($this->pl->txt('common_export'), 'export', $this->ctrl->getLinkTarget($this->parent_obj, 'export'));
		}
		$this->tpl->setCurrentBlock('cell');
		$this->tpl->setVariable('VALUE', $actions->getHTML());
		$this->tpl->parseCurrentBlock();
	}


	/**
	 * @param $value
	 */
	public function addCell($value) {
		$this->tpl->setCurrentBlock('cell');
		$this->tpl->setVariable('VALUE', $value !== NULL ? $value : '&nbsp;');
		$this->tpl->parseCurrentBlock();
	}
}

?>