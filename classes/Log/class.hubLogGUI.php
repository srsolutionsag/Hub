<?php

/**
 * GUI-Class hubOriginGUI
 *
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 * @version           1.1.04
 *
 */
class hubLogGUI {

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
		$this->pl = ilHubPlugin::getInstance();
		if (!ilHubAccess::checkAccess() OR $this->pl->isActive() == 0) {
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
		$this->{$cmd}();
	}


	public function index() {
		$this->tpl->addJavaScript('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/templates/js-logtail-master/logtail.js');
		$this->tpl->addCss('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/templates/js-logtail-master/logtail.css');
		$log_tpl = new ilTemplate(hub::getPath() . 'templates/tpl.log.html');
		//$log_tpl->setVariable('URL', $this->ctrl->getLinkTarget($this, 'getLineAjax'));
		$this->tpl->setContent($log_tpl->get());
		$this->getLine($this->getNumberOfLastLine());
	}


	/**
	 * @param int $line
	 *
	 * @return string
	 */
	public function getLine($line = 0) {
		$lines = $this->getLogFileAsArray();

		return $lines[$line];
	}


	/**
	 * @return array
	 */
	protected function getLogFileAsArray() {
		$log_file = hub::getPath() . 'log/hub.log';

		return file($log_file);
	}


	/**
	 * @return int
	 */
	protected function getNumberOfLastLine() {
		return count($this->getLogFileAsArray()) - 1;
	}


	public function getLineAjax() {
		$line = $_GET['line'] != 'false' ? $_GET['line'] : ($this->getNumberOfLastLine() - 10);
		header('Content-Type: application/json');
		echo json_encode(array(
			'line'      => $line,
			'content'   => $this->getLine($line),
			'get'       => $_GET,
			'next_line' => $line + 1,
		));
		exit;
	}
}

?>