<?php
require_once('./Services/Form/classes/class.ilPropertyFormGUI.php');
require_once('class.hubConfig.php');

/**
 * Form-Class hubConfigFormGUI
 *
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 * @version           $Id:
 *
 */
class hubConfigFormGUI extends ilPropertyFormGUI {

	/**
	 * @var ilHubConfigGUI
	 */
	protected $parent_gui;
	/**
	 * @var  ilCtrl
	 */
	protected $ctrl;


	/**
	 * @param $parent_gui
	 */
	public function __construct($parent_gui) {
		global $ilCtrl;
		$this->parent_gui = $parent_gui;
		$this->ctrl = $ilCtrl;
		$this->pl = new ilHubPlugin();
		$this->setFormAction($this->ctrl->getFormAction($this->parent_gui));
		$this->initForm();
	}


	protected  function initForm() {
		$this->setTitle($this->pl->txt('admin_form_title'));

		$te = new ilTextInputGUI($this->pl->txt('admin_origins_path'), 'origins_path');
		$te->setInfo($this->pl->txt('admin_origins_path_info'));
		$this->addItem($te);

		$cb = new ilCheckboxInputGUI($this->pl->txt('admin_lock'), 'lock');
		$this->addItem($cb);

		$h = new ilFormSectionHeaderGUI();
		$h->setTitle($this->pl->txt('admin_header_sync'));
		$this->addItem($h);

		$cb = new ilCheckboxInputGUI($this->pl->txt('admin_use_async'), 'use_async');
		$cb->setInfo($this->pl->txt('admin_use_async_info'));

		$te = new ilTextInputGUI($this->pl->txt('admin_async_user'), 'async_user');
		$cb->addSubItem($te);
		$te = new ilTextInputGUI($this->pl->txt('admin_async_password'), 'async_password');
		$cb->addSubItem($te);
		$te = new ilTextInputGUI($this->pl->txt('admin_async_client'), 'async_client');
		$cb->addSubItem($te);
		$te = new ilTextInputGUI($this->pl->txt('admin_async_cli_php'), 'async_cli_php');
		$cb->addSubItem($te);
		$this->addItem($cb);

		$te = new ilTextInputGUI($this->pl->txt('admin_roles'), 'admin_roles');
		$te->setInfo($this->pl->txt('admin_roles_info'));
		$this->addItem($te);

		$cb = new ilCheckboxInputGUI($this->pl->txt('admin_import_export'), 'import_export');
		$this->addItem($cb);

		$this->addCommandButtons();
	}


	public function fillForm() {
		$array = array();
		foreach ($this->getItems() as $item) {
			$this->getValuesForItem($item, $array);
		}
		$this->setValuesByArray($array);
	}


	/**
	 * @param $item
	 * @param $array
	 *
	 * @internal param $key
	 */
	private function getValuesForItem($item, &$array) {
		if (self::checkItem($item)) {
			$key = $item->getPostVar();
			$array[$key] = hubConfig::get($key);
			foreach ($item->getSubItems() as $subitem) {
				$this->getValuesForItem($subitem, $array);
			}
		}
	}


	/**
	 * returns whether checkinput was successful or not.
	 *
	 * @return bool
	 */
	public function fillObject() {
		if (! $this->checkInput()) {
			return false;
		}

		return true;
	}


	/**
	 * @return bool
	 */
	public function saveObject() {
		if (! $this->fillObject()) {
			return false;
		}
		foreach ($this->getItems() as $item) {
			$this->saveValueForItem($item);
		}

		return true;
	}


	/**
	 * @param $item
	 */
	private function saveValueForItem($item) {
		if (self::checkItem($item)) {
			$key = $item->getPostVar();
			hubConfig::set($key, $this->getInput($key));
			foreach ($item->getSubItems() as $subitem) {
				$this->saveValueForItem($subitem);
			}
		}
	}


	/**
	 * @param $item
	 *
	 * @return bool
	 */
	public static function checkItem($item) {
		return get_class($item) != 'ilFormSectionHeaderGUI';
	}


	protected function addCommandButtons() {
		$this->addCommandButton('save', $this->pl->txt('admin_form_button_save'));
		$this->addCommandButton('cancel', $this->pl->txt('admin_form_button_cancel'));
	}
}