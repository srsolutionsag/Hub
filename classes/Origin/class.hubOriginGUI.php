<?php
require_once('./Services/Utilities/classes/class.ilConfirmationGUI.php');
require_once('class.hubOrigin.php');
require_once('class.hubOriginTableGUI.php');
require_once('class.hubOriginFormGUI.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Sync/class.hubSyncCron.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Sync/class.hubSyncHistory.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Course/class.hubCourse.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Category/class.hubCategory.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/User/class.hubUser.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Membership/class.hubMembership.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Origin/class.hubOrigin.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/OriginProperties/class.hubOriginObjectProperties.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/class.ilHubAccess.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/origins/hubCourse/unibasSLCM/class.unibasSLCM.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Notification/class.hubOriginNotification.php');

/**
 * GUI-Class hubOriginGUI
 *
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 * @version           $Id:
 *
 */
class hubOriginGUI {

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
	 * @param $parent_gui
	 */
	public function __construct($parent_gui) {
		global $tpl, $ilCtrl, $ilToolbar, $lng;
		$this->tpl = $tpl;
		$this->ctrl = $ilCtrl;
		$this->parent = $parent_gui;
		$this->toolbar = $ilToolbar;
		$this->tabs_gui = $this->parent->tabs_gui;
		$this->lng = $lng;
		$this->pl = new ilHubPlugin();
		//		$this->pl->updateLanguages();
		if (! ilHubAccess::checkAccess() OR $this->pl->isActive() == 0) {
			ilUtil::redirect('/');
		}
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
	 * @param $cmd
	 *
	 * @return mixed|void
	 */
	protected function performCommand($cmd) {
		// TODO Rechteprüfung
		$this->{$cmd}();
	}


	public function index() {
		// require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/sql/dbupdate.php');
		//		hubOriginConfiguration::updateDB();
//		self::getChilds(261);
//		foreach (self::$childs as $child) {
//			$objCrs = new ilObjCourse($child);
//			$objCrs->getMemberObject()->add(76051, IL_CRS_MEMBER);
//		}
		$tableGui = new hubOriginTableGUI($this, 'index');
		$this->tpl->setContent($tableGui->getHTML());
	}


	protected static $childs = array();


	/**
	 * @param $node
	 */
	public static function getChilds($node) {
		global $tree;
		/**
		 * @var $tree ilTree
		 */
		foreach ($tree->getChilds($node) as $ch) {
			if ($ch['type'] == 'crs') {
				self::$childs[] = $ch['ref_id'];
			} elseif ($ch['type'] == 'cat') {
				self::getChilds($ch['ref_id']);
			}
		}
	}


	protected function reset() {
		/**
		 * @var $hist hubSyncHistory
		 */
		foreach (hubSyncHistory::where(array( 'sr_hub_origin_id' => $_GET['origin_id'] ))->get() as $hist) {
			if (ilObjectFactory::ObjectIdExists(ilObject2::_lookupObjId($hist->getIliasId()))) {
				$ilObj = ilObjectFactory::getInstanceByRefId($hist->getIliasId());
				$ilObj->delete();
				$hist->getHubObject()->delete();
				$hist->delete();
			}
		}
		ilUtil::sendInfo('Reset ok', true);
		$this->ctrl->redirect($this, 'index');
	}


	public function run() {
		$cron = new hubSyncCron();
		$cron->run();
		if (! hub::isCli()) {
			ilUtil::sendInfo('Cronjob run');
		}
		//		$this->ctrl->redirect($this, 'index');
		$this->index();
	}


	public function updateAllTables() {
		hubSyncHistory::updateDB();
		hubCategory::updateDB();
		hubCourse::updateDB();
		hubMembership::updateDB();
		hubOrigin::updateDB();
		hubOriginObjectPropertyValue::updateDB();
		hubUser::updateDB();
		ilUtil::sendInfo('Update ok', true);
		$this->ctrl->redirect($this, 'index');
	}


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


	private function activate() {
		/**
		 * @var $hubOrigin hubOrigin
		 */
		$hubOrigin = hubOrigin::find($_GET['origin_id']);
		$hubOrigin->setActive(true);
		$hubOrigin->update();
		hubLog::getInstance()->write('Origin activated: ' . $hubOrigin->getTitle(), hubLog::L_PROD);
		ilUtil::sendSuccess($this->pl->txt('msg_origin_activated'), true);
		$this->ctrl->redirect($this, 'index');
	}


	private function deactivate() {
		/**
		 * @var $hubOrigin hubOrigin
		 */
		$hubOrigin = hubOrigin::find($_GET['origin_id']);
		$hubOrigin->setActive(false);
		$hubOrigin->update();
		hubLog::getInstance()->write('Origin deactivated: ' . $hubOrigin->getTitle(), hubLog::L_PROD);
	}


	public function deactivateAll() {
		/**
		 * @var $hubOrigin hubOrigin
		 */
		foreach (hubOrigin::get() as $hubOrigin) {
			$hubOrigin->setActive(false);
			$hubOrigin->update();
			hubLog::getInstance()->write('Origin deactivated: ' . $hubOrigin->getTitle(), hubLog::L_PROD);
		}
		ilUtil::sendSuccess($this->pl->txt('msg_origin_deactivated'), true);
		$this->ctrl->redirect($this, 'index');
	}


	public function activateAll() {
		/**
		 * @var $hubOrigin hubOrigin
		 */
		foreach (hubOrigin::get() as $hubOrigin) {
			$hubOrigin->setActive(true);
			$hubOrigin->update();
			hubLog::getInstance()->write('Origin deactivated: ' . $hubOrigin->getTitle(), hubLog::L_PROD);
		}
		ilUtil::sendSuccess($this->pl->txt('msg_origin_deactivated'), true);
		$this->ctrl->redirect($this, 'index');
	}


	public function update() {
		$form = new hubOriginFormGUI($this, hubOrigin::find($_GET['origin_id']));
		$form->setValuesByPost();
		if ($form->saveObject()) {
			ilUtil::sendSuccess($this->lng->txt('success'), true);
			hubLog::getInstance()->write('Origin updated: ' . hubOrigin::find($_GET['origin_id'])
					->getTitle(), hubLog::L_PROD);
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
	}


	function applyFilter() {
		$tableGui = new hubOriginTableGUI($this, 'index');
		$tableGui->writeFilterToSession();
		$tableGui->resetOffset();
		$this->ctrl->redirect($this, 'index');
	}


	function resetFilter() {
		$tableGui = new hubOriginTableGUI($this, 'index');
		$tableGui->resetOffset();
		$tableGui->resetFilter();
		$this->ctrl->redirect($this, 'index');
	}
}

?>