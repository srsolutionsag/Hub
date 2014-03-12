<?php
require_once('./Customizing/global/plugins/Libraries/ActiveRecord/class.srModelObjectTableGUI.php');
require_once('class.hubOrigin.php');
require_once('./Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php');

/**
 * TableGUI srModelObjectTableGUI
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version $Id:
 *
 */
class hubOriginTableGUI extends srModelObjectTableGUI {

	const DEV = true;


	protected function initTableData() {
		$this->setData(hubOrigin::getArray());
	}


	/**
	 * @return bool
	 * @description returns false, if automatic columns are needed, otherwise implement your columns
	 */
	protected function initTableColumns() {
		$this->addColumn($this->pl->txt('origins_table_header_title'));
		$this->addColumn($this->pl->txt('origins_table_header_description'));
		$this->addColumn($this->pl->txt('origins_table_header_active'));
		$this->addColumn($this->pl->txt('origins_table_header_usage_type'));
		$this->addColumn($this->pl->txt('origins_table_header_last_update'));
		$this->addColumn($this->pl->txt('origins_table_header_duration'));
		$this->addColumn($this->pl->txt('origins_table_header_count'));
		$this->addColumn($this->lng->txt('actions'));
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
		$import = new ilFileInputGUI('import', 'import_file');
		$import->setSuffixes(array( 'json' ));
		$ilToolbar->addInputItem($import);
		$ilToolbar->addFormButton($this->pl->txt('origin_table_button_import'), 'import');
		// $this->addHeaderCommand($this->ctrl->getLinkTarget($this->parent_obj, 'add'), $this->pl->txt('origin_table_button_add'));
		$this->setFormAction($this->ctrl->getFormAction($this->parent_obj));
		$this->addCommandButton('run', $this->pl->txt('origin_table_button_run'));
		$this->addCommandButton('deactivateAll', $this->pl->txt('origin_deactivate_all'));
		$this->addCommandButton('activateAll', $this->pl->txt('origin_activate_all'));
		if (self::DEV) {
			//			$this->addCommandButton('reset', $this->pl->txt('origin_table_button_reset'));
			//			$this->addCommandButton('updateAllTables', $this->pl->txt('origin_table_button_update_tables'));
		}
	}


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
		$this->addCell($hubOrigin->getTitle());
		$this->addCell($hubOrigin->getDescription());
		$this->addCell($hubOrigin->getActive());
		$this->addCell($this->pl->txt('origin_form_field_usage_type_' . $a_set['usage_type']));
		$this->addCell($a_set['last_update']);
		$duration = $a_set['duration'] ? $a_set['duration'] : 0;
		$this->addCell($duration . ' s.');
		$this->addCell($hubOrigin->getCountOfHubObjects());
		$this->ctrl->setParameter($this->parent_obj, 'origin_id', $a_set['id']);
		$actions = new ilAdvancedSelectionListGUI();
		$actions->setId('actions_' . self::$num);
		$actions->setListTitle($this->pl->txt('actions'));
		$actions->addItem($this->pl->txt('edit'), 'edit', $this->ctrl->getLinkTarget($this->parent_obj, 'edit'));
		if ($a_set['active']) {
			$actions->addItem($this->pl->txt('deactivate'), 'deactivate', $this->ctrl->getLinkTarget($this->parent_obj, 'deactivate'));
		} else {
			$actions->addItem($this->pl->txt('activate'), 'activate', $this->ctrl->getLinkTarget($this->parent_obj, 'activate'));
		}
		$actions->addItem($this->pl->txt('delete'), 'delete', $this->ctrl->getLinkTarget($this->parent_obj, 'confirmDelete'));
		$actions->addItem($this->pl->txt('export'), 'export', $this->ctrl->getLinkTarget($this->parent_obj, 'export'));
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