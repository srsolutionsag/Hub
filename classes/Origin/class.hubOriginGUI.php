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
 * @version 1.1.03
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
			$this->{$cmd}();

			return true;
		} else {
			return false;
		}
	}


	public function index() {
//
//		require_once('./Customizing/global/plugins/Libraries/ActiveRecord/Demo/Message/class.arMessage.php');
////		arMessage::resetDB();
//		$arMessage = new arMessage();
//		$arMessage->setTitle('Erste Nachricht');
//		$arMessage->setBody('Hallo Welt');
//		$arMessage->setPriority(arMessage::PRIO_HIGH);
//		$arMessage->setReceiverId(6);
//		$arMessage->setSenderId(256);
//		$arMessage->setType(arMessage::TYPE_NEW);
////		$arMessage->create();
////		$arMessage->create();
//
//		$arMessage = new arMessage(1);
//		$arMessage->delete();
//
////		echo '<pre>' . print_r($arMessage, 1) . '</pre>';
//		echo '<pre>' . print_r(arMessage::get(), 1) . '</pre>';

		if (ilHubAccess::checkAccess()) {
//			hubCategory::updateDB();
			$tableGui = new hubOriginTableGUI($this, 'index');
			$this->tpl->setContent($tableGui->getHTML());
		}
	}


	public function export() {
		if (ilHubAccess::checkAccess()) {
			hubOriginExport::export(hubOrigin::find($_GET['origin_id']));
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
			 */
			$form = new hubOriginFormGUI($this, hubOrigin::find($_GET['origin_id']));
			$form->fillForm();
			$ilToolbar->addButton($this->pl->txt('common_export'), $this->ctrl->getLinkTarget($this, 'export'));
			$this->tpl->setContent($form->getHTML());
		}
	}


	private function activate() {
		if (ilHubAccess::checkAccess()) {
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
	}


	private function deactivate() {
		if (ilHubAccess::checkAccess()) {
			/**
			 * @var $hubOrigin hubOrigin
			 */
			$hubOrigin = hubOrigin::find($_GET['origin_id']);
			$hubOrigin->setActive(false);
			$hubOrigin->update();
			hubLog::getInstance()->write('Origin deactivated: ' . $hubOrigin->getTitle(), hubLog::L_PROD);
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
			$form = new hubOriginFormGUI($this, hubOrigin::find($_GET['origin_id']));
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
			$origin = hubOrigin::find($_GET['origin_id']);
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