<?php
require_once('class.hubIconFormGUI.php');
require_once('class.hubIcon.php');
require_once('class.hubIconCollection.php');

/**
 * Class hubIconGUI
 *
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 *
 * @version           1.0.0
 *
 * @ilCtrl_IsCalledBy hubIconGUI : hubOriginGUI
 */
class hubIconGUI {

	/**
	 * @var hubOrigin
	 */
	protected $origin;
	/**
	 * @var hubIconCollection
	 */
	protected $hubIconCollection;


	/**
	 * @param $parent_gui
	 */
	public function __construct($parent_gui) {
		global $tpl, $ilCtrl, $ilToolbar, $lng, $ilTabs;
		$this->tpl = $tpl;
		$this->ctrl = $ilCtrl;
		$this->parent = $parent_gui;
		$this->toolbar = $ilToolbar;
		$this->tabs_gui = $ilTabs;
		$this->lng = $lng;
		$this->pl = new ilHubPlugin();
		if ($_GET['hrl'] == 'true') {
			$this->pl->updateLanguageFiles();
		}
		if (! ilHubAccess::checkAccess() OR $this->pl->isActive() == 0) {
			ilUtil::redirect('/');
		}
		$this->origin = hubOrigin::find($_GET['origin_id']);
		$this->hubIconCollection = hubIconCollection::getInstance($this->origin);
	}


	/**
	 * @return bool
	 */
	public function executeCommand() {
		if (ilHubAccess::checkAccess()) {
			$cmd = $this->ctrl->getCmd();
			$next_class = $this->ctrl->getNextClass($this);
			$this->tpl->getStandardTemplate();
			switch ($next_class) {
				case '':
					$this->{$cmd}();
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

			return true;
		} else {
			return false;
		}
	}


	public function index() {
		$hubIconFormGUI = new hubIconFormGUI($this, $this->hubIconCollection);

		if ($this->origin->getUsageType() == hub::OBJECTTYPE_COURSE) {
			$dep1 = new hubIconFormGUI($this, hubIconCollection::getInstance($this->origin, hubIcon::USAGE_FIRST_DEPENDENCE));
			$dep2 = new hubIconFormGUI($this, hubIconCollection::getInstance($this->origin, hubIcon::USAGE_SECOND_DEPENDENCE));
			$dep3 = new hubIconFormGUI($this, hubIconCollection::getInstance($this->origin, hubIcon::USAGE_THIRD_DEPENDENCE));

			$this->tpl->setContent($hubIconFormGUI->getHTML() . $dep1->getHTML() . $dep2->getHTML() . $dep3->getHTML());
		} else {
			$this->tpl->setContent($hubIconFormGUI->getHTML());
		}
	}


	public function save() {
		$this->saveCollection($this->hubIconCollection);
	}


	public function saveFirstDep() {
		$this->saveCollection(hubIconCollection::getInstance($this->origin, hubIcon::USAGE_FIRST_DEPENDENCE));
	}


	public function saveSecondDep() {
		$this->saveCollection(hubIconCollection::getInstance($this->origin, hubIcon::USAGE_SECOND_DEPENDENCE));
	}


	public function saveThirdDep() {
		$this->saveCollection(hubIconCollection::getInstance($this->origin, hubIcon::USAGE_THIRD_DEPENDENCE));
	}



	/**
	 * @param hubIconCollection $hubIconCollection
	 */
	protected function saveCollection(hubIconCollection $hubIconCollection) {
		$hubIconFormGUI = new hubIconFormGUI($this, $hubIconCollection);
		if ($hubIconFormGUI->save()) {
			ilUtil::sendSuccess($this->pl->txt('icon_icons_saved'), true);
			$this->ctrl->redirect($this, 'index');
		} else {
			$hubIconFormGUI->setValuesByPost();
			$this->tpl->setContent($hubIconFormGUI->getHTML());
		}
	}
}


?>
