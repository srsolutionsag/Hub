<?php
require_once('./Services/Utilities/classes/class.ilConfirmationGUI.php');
require_once('class.hubSyncHistory.php');
require_once('class.hubSyncHistoryTableGUI.php');

/**
 * GUI-Class hubSyncHistoryGUI
 *
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 * @version           1.1.04
 *
 */
class hubSyncHistoryGUI {

	/**
	 * @var ilTabsGUI
	 */
	protected $tabs_gui;
	/**
	 * @var ilPropertyFormGUI
	 */
	protected $form;
	/**
	 * @var ilToolbarGUI
	 */
	protected $toolbar;
	/**
	 * @var ilCtrl
	 */
	protected $ctrl;
	/**
	 * @var ilTemplate
	 */
	protected $tpl;


	/**
	 * @param null $parent_gui
	 */
	public function __construct($parent_gui) {
		global $tpl, $ilCtrl, $ilToolbar, $lng, $ilTabs;
		$this->tpl = $tpl;
		$this->ctrl = $ilCtrl;
		$this->parent = $parent_gui;
		$this->toolbar = $ilToolbar;
		$this->tabs_gui = $ilTabs;
		$this->lng = $lng;
		$this->pl = ilHubPlugin::getInstance();
	}


	/**
	 * @return bool
	 */
	public function executeCommand() {
		$cmd = $this->ctrl->getCmd();
		$this->performCommand($cmd);

		return true;
	}


	/**
	 * @param string $cmd
	 *
	 * @return mixed|void
	 */
	protected function performCommand($cmd) {
		// TODO Rechteprüfung
		$this->{$cmd}();
	}


	public function indexCourses() {
		$this->tabs_gui->setTabActive('sync_history_courses');
		$tableGui = new hubSyncHistoryTableGUI($this, 'indexCourses');
		$this->tpl->setContent($tableGui->getHTML());
	}


	public function applyFilter() {
		$tableGui = new hubSyncHistoryTableGUI($this, 'index');
		$tableGui->writeFilterToSession();
		$tableGui->resetOffset();
		$this->ctrl->redirect($this, 'index');
	}


	public function resetFilter() {
		$tableGui = new hubSyncHistoryTableGUI($this, 'index');
		$tableGui->resetOffset();
		$tableGui->resetFilter();
		$this->ctrl->redirect($this, 'index');
	}
	/*
		public function add() {
			$form = new hubOriginFormGUI($this, new hubOrigin());
			$form->fillForm();
			$this->tpl->setContent($form->getHTML());
		}


		public function create() {
			$form = new hubOriginFormGUI($this, new hubOrigin());
			$form->setValuesByPost();
			if ($form->saveObject()) {
				ilUtil::sendSuccess($this->pl->txt('success'), true);
				$this->ctrl->setParameter($this, 'origin_id', NULL);
				$this->ctrl->redirect($this, 'index');
			} else {
				$this->tpl->setContent($form->getHTML());
			}
		}


		public function edit() {
			$form = new hubOriginFormGUI($this, hubOrigin::find($_GET['origin_id']));
			$form->fillForm();
			$this->tpl->setContent($form->getHTML());
		}




		public function update() {
			$form = new hubOriginFormGUI($this, hubOrigin::find($_GET['origin_id']));
			$form->setValuesByPost();
			if ($form->saveObject()) {
				ilUtil::sendSuccess($this->lng->txt('success'), true);
				$this->ctrl->setParameter($this, 'origin_id', NULL);
				$this->ctrl->redirect($this, 'index');
			} else {
				$this->tpl->setContent($form->getHTML());
			}
		}


		public function confirmDelete() {
			$this->ctrl->saveParameter($this, 'origin_id');
			$conf = new ilConfirmationGUI();
			$conf->setFormAction($this->ctrl->getFormAction($this));
			$conf->setHeaderText($this->pl->txt('msg_confirm_delete_origin'));
			$conf->setConfirm($this->lng->txt('delete'), 'delete');
			$conf->setCancel($this->lng->txt('cancel'), 'index');
			$this->tpl->setContent($conf->getHTML());
		}


		public function delete() {
			$origin = hubOrigin::find($_GET['origin_id']);
			$origin->delete();
			$this->ctrl->redirect($this, 'index');
		}*/
}

?>