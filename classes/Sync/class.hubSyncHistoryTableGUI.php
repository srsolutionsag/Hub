<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Table/class.hubAbstractTableGUI.php');
require_once('./Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php');
require_once('class.hubSyncHistory.php');

/**
 * TableGUI srModelObjectTableGUI
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.1.04
 *
 */
class hubSyncHistoryTableGUI extends hubAbstractTableGUI {

	protected function initTableData() {
		switch ($this->parent_cmd) {
			case 'indexCourses':
				$this->setData(hubSyncHistory::where('ilias_id > 0 AND sr_hub_origin_id = 1')->getArray());
				break;
		}
	}


	/**
	 * @return bool
	 * @description returns false, if automatic columns are needed, otherwise implement your columns
	 */
	protected function initTableColumns() {
		$this->addColumn($this->pl->txt('origins_table_header_title'));

		//		$this->addColumn($this->pl->txt('origins_table_header_ilias_id'));
		//		$this->addColumn($this->pl->txt('origins_table_header_title'));
		//		$this->addColumn($this->pl->txt('origins_table_header_description'));
		//		$this->addColumn($this->pl->txt('origins_table_header_active'));
		//		$this->addColumn($this->pl->txt('origins_table_header_usage_type'));
		//		$this->addColumn($this->pl->txt('origins_table_header_last_update'));
		//		$this->addColumn($this->pl->txt('origins_table_header_duration'));
		//		$this->addColumn($this->lng->txt('actions'));
		return true;
	}


	/**
	 * @param $a_set
	 *
	 * @return bool
	 * @description implement your woen fillRow or return false
	 */
	protected function fillTableRow($a_set) {
		self::$num ++;
		//		Array
		//		(
		//			[ilias_id] => 152935
		//          [ilias_id_type] => 2
		//          [sr_hub_origin_id] => 3
		//          [pickup_date_micro] => 1385124707.1758
		//          [deleted] => 0
		//          [ext_id] => 00002426
		//      )
		switch ($a_set['ilias_id_type']) {
			case hubObject::ILIAS_ID_TYPE_OBJ_ID:
			case hubObject::ILIAS_ID_TYPE_USER:
				$title = ilObject2::_lookupTitle($a_set['ilias_id']);
				break;
			case hubObject::ILIAS_ID_TYPE_REF_ID:
				$title = ilObject2::_lookupTitle(ilObject2::_lookupObjId($a_set['ilias_id']));
				$link = ilLink::_getLink($a_set['ilias_id']);
				break;
		}
		$this->addCell('<a target=\'_blank\' href=\'' . $link . '\'>' . $title . '</a>');

		//		$this->addCell($a_set['description']);
		//		$this->addCell($a_set['active']);
		//		$this->addCell($this->pl->txt('origin_form_field_usage_type_' . $a_set['usage_type']));
		//		$this->addCell($a_set['last_update']);
		//		$duration = $a_set['duration'] ? $a_set['duration'] : 0;
		//		$this->addCell($duration . ' s.');
		return true;
		//		$this->ctrl->setParameter($this->parent_obj, 'origin_id', $a_set['id']);
		//		$actions = new ilAdvancedSelectionListGUI();
		//		$actions->setId('actions_' . self::$num);
		//		$actions->setListTitle($this->pl->txt('actions'));
		//		$actions->addItem($this->pl->txt('edit'), 'edit', $this->ctrl->getLinkTarget($this->parent_obj, 'edit'));
		//		if ($a_set['active']) {
		//			$actions->addItem($this->pl->txt('deactivate'), 'deactivate', $this->ctrl->getLinkTarget($this->parent_obj, 'deactivate'));
		//		} else {
		//			$actions->addItem($this->pl->txt('activate'), 'activate', $this->ctrl->getLinkTarget($this->parent_obj, 'activate'));
		//		}
		//		$actions->addItem($this->pl->txt('delete'), 'delete', $this->ctrl->getLinkTarget($this->parent_obj, 'confirmDelete'));
		//		$this->tpl->setCurrentBlock('cell');
		//		$this->tpl->setVariable('VALUE', $actions->getHTML());
		//		$this->tpl->parseCurrentBlock();
	}


	/**
	 * @return bool
	 */
	protected function initTableFilter() {
		return false;
	}


	protected function initTableProperties() {
		$this->table_title = $this->pl->txt('sync_history_table_title');
		$this->table_id = 'sync_history';
		$this->prefix = 'sync_history';
	}


	/**
	 * @return bool
	 * @description return false or implements own form action and
	 */
	protected function initFormActionsAndCmdButtons() {
		$this->addHeaderCommand($this->ctrl->getLinkTarget($this->parent_obj, 'add'), $this->pl->txt('origin_table_button_add'));
		$this->setFormAction($this->ctrl->getFormAction($this->parent_obj));

		return false;
	}


	protected function initTableRowTemplate() {
		return false;
	}


	protected function initLanguage() {
		$this->pl = ilHubPlugin::getInstance();
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