<?php

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

	}
}


?>
