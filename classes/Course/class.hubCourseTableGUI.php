<?php
require_once('./Customizing/global/plugins/Libraries/ActiveRecord/class.srModelObjectTableGUI.php');
require_once('./Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php');
require_once('class.hubCourse.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Sync/class.hubSyncHistory.php');
@include_once('./Services/Link/classes/class.ilLink.php');

/**
 * TableGUI srModelObjectTableGUI
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version $Id:
 *
 */
class hubCourseTableGUI extends srModelObjectTableGUI {

	protected function initTableData() {
		$this->setData(hubCourse::orderBy('title')->getArray());
	}


	/**
	 * @return bool
	 * @description returns false, if automatic columns are needed, otherwise implement your columns
	 */
	protected function initTableColumns() {
		$this->addColumn('External ID');
		$this->addColumn('Title');
		$this->addColumn('Category');
		$this->addColumn('Status');

		return true;
	}


	/**
	 * @param $a_set
	 *
	 * @return bool
	 * @description implement your woen fillRow or return false
	 */
	protected function fillTableRow($a_set) {
		/**
		 * @var $hubSyncHistory hubSyncHistory
		 * @var $hubCourse      hubCourse
		 */
		if ($a_set['ext_id']) {
			$hubCourse = hubCourse::find($a_set['ext_id']);
			$hubSyncHistory = hubSyncHistory::find($a_set['ext_id']);
			$this->addCell($hubCourse->getExtId());
			$this->addCell('<a target=\'_blank\' href=\'' . ilLink::_getLink($hubSyncHistory->getIliasId()) . '\'>' . $hubCourse->getTitlePrefix()
				. $hubCourse->getTitle() . '</a>');
			$this->addCell('<a target=\'_blank\' href=\'' . ilLink::_getLink($hubCourse->getParentId()) . '\'>'
				. ilObject2::_lookupTitle(ilObject2::_lookupObjId($hubCourse->getParentId())) . '</a>');

			$this->addCell($this->pl->txt('list_status_' . $hubSyncHistory->getTemporaryStatus()));
		}

		return true;
	}


	/**
	 * @return bool
	 */
	protected function initTableFilter() {
		return false;
	}


	protected function initTableProperties() {
		$this->table_title = $this->pl->txt('hub_courses_table_title');
		$this->table_id = 'hub_courses';
		$this->prefix = 'hub_courses';
	}


	/**
	 * @return bool
	 * @description return false or implements own form action and
	 */
	protected function initFormActionsAndCmdButtons() {
		//$this->setFormAction($this->ctrl->getFormAction($this->parent_obj));
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