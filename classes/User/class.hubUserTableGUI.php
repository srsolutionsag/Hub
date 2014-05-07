<?php
require_once('./Customizing/global/plugins/Libraries/ActiveRecord/class.srModelObjectTableGUI.php');
require_once('./Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php');
require_once('class.hubUser.php');

/**
 * TableGUI srModelObjectTableGUI
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.1.03
 *
 */
class hubUserTableGUI extends srModelObjectTableGUI {

	protected function initTableData() {
		$this->setData(hubUser::getArray());
	}


	/**
	 * @return bool
	 * @description returns false, if automatic columns are needed, otherwise implement your columns
	 */
	protected function initTableColumns() {
		return false;
	}


	/**
	 * @param $a_set
	 *
	 * @return bool
	 * @description implement your woen fillRow or return false
	 */
	protected function fillTableRow($a_set) {
		return false;
	}


	/**
	 * @return bool
	 */
	protected function initTableFilter() {
		$this->setTopCommands(true);
		$this->setEnableHeader(true);
		$this->setShowRowsSelector(true);
		$this->setShowTemplates(true);
		$this->setEnableHeader(true);
		$this->setEnableTitle(true);
		$this->setDefaultOrderDirection("asc");

		//		$this->setDefaultOrderField('id');
		return true;
	}


	protected function initTableProperties() {
		$this->table_title = $this->pl->txt('hub_users_table_title');
		$this->table_id = 'hub_users';
		$this->prefix = 'hub_users';
	}


	/**
	 * @return bool
	 * @description return false or implements own form action and
	 */
	protected function initFormActionsAndCmdButtons() {
		return false;
	}


	protected function initTableRowTemplate() {
		return false;
	}


	protected function initLanguage() {
		$this->pl = new ilHubPlugin();
	}
}

?>