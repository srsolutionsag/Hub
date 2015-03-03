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
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Notification/class.hubOriginNotification.php');

/**
 * GUI-Class hubOriginGUI
 *
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 * @version           1.1.04
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
	 * @var hubOrigin
	 */
	protected $hubOrigin;


	/**
	 * @param $parent_gui
	 */
	public function __construct($parent_gui) {
		global $tpl, $ilCtrl, $ilToolbar, $lng, $ilTabs;
		$this->tpl = $tpl;
		if (ilHubPlugin::getBaseClass() != 'ilRouterGUI') {
			$this->tpl->getStandardTemplate();
		}
		$this->ctrl = $ilCtrl;
		$this->parent = $parent_gui;
		$this->toolbar = $ilToolbar;
		$this->tabs_gui = $ilTabs;
		$this->lng = $lng;
		$this->pl = ilHubPlugin::getInstance();
		$this->hubOrigin = hubOrigin::findOrGetInstance($_GET['origin_id']);
		if ($_GET['hrl'] == 'true') {
			$this->pl->updateLanguageFiles();
		}
		if (! ilHubAccess::checkAccess() OR $this->pl->isActive() == 0) {
			ilUtil::redirect('/');
		}
	}


	/**
	 * @return bool
	 */
	public function executeCommand() {
		if (ilHubAccess::checkAccess()) {
			$cmd = $this->ctrl->getCmd();
			$next_class = $this->ctrl->getNextClass($this);
//			$this->tpl->getStandardTemplate();
			$this->ctrl->setParameterByClass('hubIconGUI', 'origin_id', $_GET['origin_id']);
			$this->ctrl->saveParameter($this, 'origin_id');
			$this->setTabs($next_class, $cmd);
			switch ($next_class) {
				case '':
					$this->performCommand($cmd);
					break;
				default:
					require_once($this->ctrl->lookupClassPath($next_class));
					if (! $cmd) {
						$this->ctrl->setCmd('index');
					}
					$gui = new $next_class($this);
					$this->ctrl->forwardCommand($gui);
					break;
			}

			if (ilHubPlugin::getBaseClass() != 'ilRouterGUI') {
				$this->tpl->show();
			}

			return true;
		} else {
			return false;
		}
	}


	/**
	 * @param $next_class
	 * @param $cmd
	 */
	private function setTabs($next_class, $cmd) {
		if ($_GET['origin_id'] AND ($cmd != 'index' OR $next_class != 'huborigingui')) {
			$this->tpl->setTitle($this->hubOrigin->getTitle());
			$this->tabs_gui->clearTargets();
			$this->tabs_gui->setBackTarget($this->pl->txt('common_back'), $this->ctrl->getLinkTarget($this, 'back'));
			$this->tabs_gui->addSubTab('common', $this->pl->txt('origin_subtab_settings'), $this->ctrl->getLinkTarget($this, 'edit'));

			if ($this->hubOrigin->supportsIcons()) {
				$this->tabs_gui->addSubTab('icons', $this->pl->txt('origin_subtab_icons'), $this->ctrl->getLinkTargetByClass('hubIconGUI'));
			}
		}
		switch ($next_class) {
			case 'hubicongui';
				$this->tabs_gui->setSubTabActive('icons');
				break;
			default:
				$this->tabs_gui->setSubTabActive('common');
				break;
		}
	}


	private function setTitleAndDescription() {
	}


	/**
	 * @param $cmd
	 */
	private function performCommand($cmd) {
		$this->{$cmd}();
	}


	public function index() {
		//		require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/sql/dbupdate.php');
		if (ilHubAccess::checkAccess()) {
			$tableGui = new hubOriginTableGUI($this, 'index');
			$this->tpl->setContent($tableGui->getHTML());
		}
	}


	public function export() {
		if (ilHubAccess::checkAccess()) {
			hubOriginExport::export($this->hubOrigin);
		}
	}


	public function import() {
		if (ilHubAccess::checkAccess()) {
			hubOriginExport::import($_FILES);
			$this->index();
		}
	}


	public function run() {
		if (ilHubAccess::checkAccess()) {
			$cron = new hubSyncCron();
			$cron->run();
			if (! hub::isCli()) {
				ilUtil::sendSuccess('Cronjob run');
			}
			$this->index();
		}
	}


	public function dryRun() {
		if (ilHubAccess::checkAccess()) {
			$cron = new hubSyncCron();
			$cron->setDryrun(true);
			$cron->run();
			if (! hub::isCli()) {
				ilUtil::sendSuccess('Cronjob run');
			}
			$this->index();
		}
	}


	public function runAsync() {
		if (ilHubAccess::checkAccess()) {
			$async = new hubAsyncSync();
			$async->run();
			if (! hub::isCli()) {
				ilUtil::sendSuccess('Cronjob run');
			}
			$this->ctrl->redirect($this, 'index');
		}
	}


	public function updateAllTables() {
		if (ilHubAccess::checkAccess()) {
			hubOriginConfiguration::installDB();
			hubOrigin::installDB();
			hubOriginObjectPropertyValue::installDB();
			hubCategory::installDB();
			hubCourse::installDB();
			hubMembership::installDB();
			hubUser::installDB();
			hubSyncHistory::installDB();
			ilUtil::sendInfo('Update ok', true);
			//			$this->ctrl->redirect($this, 'index');
		}
	}


	public function add() {
		if (ilHubAccess::checkAccess()) {
			$form = new hubOriginFormGUI($this, new hubOrigin());
			$form->fillForm();
			$this->tpl->setContent($form->getHTML());
		}
	}


	protected function back() {
		$this->ctrl->setParameter($this, 'origin_id', NULL);
		$this->ctrl->redirect($this);
	}


	public function create() {
		if (ilHubAccess::checkAccess()) {
			$form = new hubOriginFormGUI($this, new hubOrigin());
			$form->setValuesByPost();
			if ($form->saveObject()) {
				ilUtil::sendSuccess($this->pl->txt('success'), true);
				$this->ctrl->setParameter($this, 'origin_id', NULL);
				//				$this->ctrl->redirect($this, 'index');
			} else {
				$this->tpl->setContent($form->getHTML());
			}
		}
	}


	public function edit() {
		if (ilHubAccess::checkAccess()) {

			global $ilToolbar;
			/**
			 * @var $ilToolbar ilToolbarGUI
			 * @var $hubOrigin hubOrigin
			 */
			$form = new hubOriginFormGUI($this, $this->hubOrigin);
			$form->fillForm();
			$ilToolbar->addButton($this->pl->txt('common_export'), $this->ctrl->getLinkTarget($this, 'export'));
			$this->tpl->setContent($form->getHTML());
		}
	}


	private function activate() {
		if (ilHubAccess::checkAccess()) {
			$this->hubOrigin->setActive(true);
			$this->hubOrigin->update();
			hubLog::getInstance()->write('Origin activated: ' . $this->hubOrigin->getTitle(), hubLog::L_PROD);
			ilUtil::sendSuccess($this->pl->txt('msg_origin_activated'), true);
			$this->ctrl->redirect($this, 'index');
		}
	}


	private function deactivate() {
		if (ilHubAccess::checkAccess()) {
			$this->hubOrigin->setActive(false);
			$this->hubOrigin->update();
			hubLog::getInstance()->write('Origin deactivated: ' . $this->hubOrigin->getTitle(), hubLog::L_PROD);
			ilUtil::sendSuccess($this->pl->txt('msg_origin_deactivated'), true);
			$this->ctrl->redirect($this, 'index');
		}
	}


	public function deactivateAll() {
		if (ilHubAccess::checkAccess()) {
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
	}


	public function activateAll() {
		if (ilHubAccess::checkAccess()) {
			/**
			 * @var $hubOrigin hubOrigin
			 */
			foreach (hubOrigin::get() as $hubOrigin) {
				$hubOrigin->setActive(true);
				$hubOrigin->update();
				hubLog::getInstance()->write('Origin activated: ' . $hubOrigin->getTitle(), hubLog::L_PROD);
			}
			ilUtil::sendSuccess($this->pl->txt('msg_origin_activated'), true);
			$this->ctrl->redirect($this, 'index');
		}
	}


	/**
	 * @param bool $redirect
	 */
	public function update($redirect = true) {
		if (ilHubAccess::checkAccess()) {
			$form = new hubOriginFormGUI($this, $this->hubOrigin);
			$form->setValuesByPost();
			if ($form->saveObject()) {
				ilUtil::sendSuccess($this->pl->txt('msg_saved'), $redirect);
				hubLog::getInstance()->write('Origin updated: ' . hubOrigin::find($_GET['origin_id'])->getTitle(), hubLog::L_PROD);
				$this->ctrl->setParameter($this, 'origin_id', NULL);
				if ($redirect) {
					$this->ctrl->redirect($this, 'index');
				}
			}
			$this->tpl->setContent($form->getHTML());
		}
	}


	public function updateAndStay() {
		$this->update(false);
	}


	public function confirmDelete() {
		if (ilHubAccess::checkAccess()) {
			$this->ctrl->saveParameter($this, 'origin_id');
			$conf = new ilConfirmationGUI();
			$conf->setFormAction($this->ctrl->getFormAction($this));
			$conf->setHeaderText($this->pl->txt('msg_confirm_delete_origin'));
			$conf->setConfirm($this->lng->txt('delete'), 'delete');
			$conf->setCancel($this->lng->txt('cancel'), 'index');
			$this->tpl->setContent($conf->getHTML());
		}
	}


	public function delete() {
		if (ilHubAccess::checkAccess()) {
			$origin = hubOrigin::find($this->hubOrigin);
			$origin->delete();
			$this->ctrl->redirect($this, 'index');
		}
	}


	public function applyFilter() {
		$tableGui = new hubOriginTableGUI($this, 'index');
		$tableGui->writeFilterToSession();
		$tableGui->resetOffset();
		$this->ctrl->redirect($this, 'index');
	}


	public function resetFilter() {
		$tableGui = new hubOriginTableGUI($this, 'index');
		$tableGui->resetOffset();
		$tableGui->resetFilter();
		$this->ctrl->redirect($this, 'index');
	}
}

?>