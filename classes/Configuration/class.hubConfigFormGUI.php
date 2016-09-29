<?php

require_once('./Services/Form/classes/class.ilPropertyFormGUI.php');
require_once('class.hubConfig.php');

/**
 * Form-Class hubConfigFormGUI
 *
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 * @version           1.1.04
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
		$this->pl = ilHubPlugin::getInstance();
		$this->setFormAction($this->ctrl->getFormAction($this->parent_gui));
		$this->initForm();
	}


	protected function initForm() {
		$this->setTitle($this->pl->txt('admin_form_title'));

		$te = new ilTextInputGUI($this->pl->txt('admin_origins_path'), hubConfig::F_ORIGINS_PATH);
		$te->setInfo($this->pl->txt('admin_origins_path_info'));
		$this->addItem($te);

		$cb = new ilCheckboxInputGUI($this->pl->txt('admin_lock'), hubConfig::F_LOCK);
		$this->addItem($cb);

		$h = new ilFormSectionHeaderGUI();
		$h->setTitle($this->pl->txt('admin_membership'));
		$this->addItem($h);

		$cb = new ilCheckboxInputGUI($this->pl->txt('admin_membership_activate'), hubCOnfig::F_MMAIL_ACTIVE);
		$cb->setInfo($this->pl->txt('admin_membership_activate_info'));
		$this->addItem($cb);

		$mm = new ilTextInputGUI($this->pl->txt('admin_membership_mail_subject'), hubConfig::F_MMAIL_SUBJECT);
		$mm->setInfo($this->pl->txt('admin_membership_mail_subject_info'));
		$this->addItem($mm);

		$mm = new ilTextAreaInputGUI($this->pl->txt('admin_membership_mail_msg'), hubConfig::F_MMAIL_MSG);
		$mm->setInfo($this->pl->txt('admin_membership_mail_msg_info'));
		$this->addItem($mm);

		$h = new ilFormSectionHeaderGUI();
		$h->setTitle($this->pl->txt('admin_user_creation'));
		$this->addItem($h);

		$ti = new ilTextInputGUI($this->pl->txt('admin_user_creation_standard_role'), hubConfig::F_STANDARD_ROLE);
		$this->addItem($ti);

		$h = new ilFormSectionHeaderGUI();
		$h->setTitle($this->pl->txt('admin_header_sync'));
		$this->addItem($h);

		$cb = new ilCheckboxInputGUI($this->pl->txt('admin_use_async'), hubConfig::F_USE_ASYNC);
		$cb->setInfo($this->pl->txt('admin_use_async_info'));

		$te = new ilTextInputGUI($this->pl->txt('admin_async_user'), hubConfig::F_ASYNC_USER);
		$cb->addSubItem($te);
		$te = new ilTextInputGUI($this->pl->txt('admin_async_password'), hubConfig::F_ASYNC_PASSWORD);
		$cb->addSubItem($te);
		$te = new ilTextInputGUI($this->pl->txt('admin_async_client'), hubConfig::F_ASYNC_CLIENT);
		$cb->addSubItem($te);
		$te = new ilTextInputGUI($this->pl->txt('admin_async_cli_php'), hubConfig::F_ASYNC_CLI_PHP);
		$cb->addSubItem($te);
		$this->addItem($cb);

		$te = new ilTextInputGUI($this->pl->txt(hubConfig::F_ADMIN_ROLES), hubConfig::F_ADMIN_ROLES);
		$te->setInfo($this->pl->txt('admin_roles_info'));
		$this->addItem($te);

		$cb = new ilCheckboxInputGUI($this->pl->txt('admin_import_export'), hubConfig::F_IMPORT_EXPORT);
		$this->addItem($cb);

		$h = new ilFormSectionHeaderGUI();
		$h->setTitle($this->pl->txt('admin_shortlink'));
		$this->addItem($h);

		$ti = new ilTextInputGUI($this->pl->txt('admin_msg_shortlink_not_found'), hubConfig::F_MSG_SHORTLINK_NOT_FOUND);
		$ti->setInfo($this->pl->txt('admin_msg_shortlink_not_found_info'));
		$this->addItem($ti);

		$ti = new ilTextInputGUI($this->pl->txt('admin_msg_shortlink_no_ilias_id'), hubConfig::F_MSG_SHORTLINK_NO_ILIAS_ID);
		$ti->setInfo($this->pl->txt('admin_msg_shortlink_no_ilias_id_info'));
		$this->addItem($ti);

		$ti = new ilTextInputGUI($this->pl->txt('admin_msg_shortlink_not_active'), hubConfig::F_MSG_SHORTLINK_NOT_ACTIVE);
		$ti->setInfo($this->pl->txt('admin_msg_shortlink_not_active'));
		$this->addItem($ti);

		$h = new ilFormSectionHeaderGUI();
		$h->setTitle($this->pl->txt('admin_header_db'));
		//		$this->addItem($h);

		//
		// DB
		//
		$db = new ilCheckboxInputGUI($this->pl->txt('admin_db'), hubConfig::F_DB);
		$db_host = new ilTextInputGUI($this->pl->txt('admin_db_host'), hubConfig::F_DB_HOST);
		$db->addSubItem($db_host);
		$db_name = new ilTextInputGUI($this->pl->txt('admin_db_name'), hubConfig::F_DB_NAME);
		$db->addSubItem($db_name);
		$db_user = new ilTextInputGUI($this->pl->txt('admin_db_user'), hubConfig::F_DB_USER);
		$db->addSubItem($db_user);
		$db_password = new ilTextInputGUI($this->pl->txt('admin_db_password'), hubConfig::F_DB_PASSWORD);
		$db->addSubItem($db_password);
		$db_password = new ilTextInputGUI($this->pl->txt('admin_db_port'), hubConfig::F_DB_PORT);
		$db->addSubItem($db_password);

		//		$this->addItem($db);

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
		if (!$this->checkInput()) {
			return false;
		}

		return true;
	}


	/**
	 * @return bool
	 */
	public function saveObject() {
		if (!$this->fillObject()) {
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