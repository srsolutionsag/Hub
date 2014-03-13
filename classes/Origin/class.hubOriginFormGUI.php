<?php
require_once('./Services/Form/classes/class.ilPropertyFormGUI.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Category/class.hubCategoryPropertiesFormGUI.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/OriginProperties/class.hubOriginObjectPropertiesFormGUI.php');

/**
 * Class hubOriginFormGUI
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
class hubOriginFormGUI extends ilPropertyFormGUI {

	/**
	 * @var  hubOrigin
	 */
	protected $origin;
	/**
	 * @var
	 */
	protected $parent_gui;
	/**
	 * @var  ilCtrl
	 */
	protected $ctrl;


	public function __construct($parent_gui, hubOrigin $origin) {
		global $ilCtrl;
		$this->origin = $origin;
		$this->parent_gui = $parent_gui;
		$this->ctrl = $ilCtrl;
		$this->ctrl->saveParameter($parent_gui, 'origin_id');
		$this->pl = new ilHubPlugin();
		$this->initForm();
	}


	private function initForm() {
		$this->setFormAction($this->ctrl->getFormAction($this->parent_gui));
		if ($this->origin->getId() == 0) {
			$this->setTitle($this->pl->txt('origin_form_title_add'));
		} else {
			$this->setTitle($this->pl->txt('origin_form_title_edit'));
		}
		if ($this->origin->getId() == 0) {
			$this->addCommandButton('create', $this->pl->txt('origin_form_button_create'));
		} else {
			$this->addCommandButton('update', $this->pl->txt('origin_form_button_update'));
		}
		// Form Elements
		$te = new ilCheckboxInputGUI($this->pl->txt('origin_form_field_active'), 'active');
		$this->addItem($te);
		//
		$te = new ilTextInputGUI($this->pl->txt('origin_form_field_title'), 'title');
		$te->setRequired(true);
		$this->addItem($te);
		//
		$te = new ilTextAreaInputGUI($this->pl->txt('origin_form_field_description'), 'description');
		$te->setRequired(true);
		$this->addItem($te);
		//
		$h = new ilFormSectionHeaderGUI();
		$h->setTitle($this->pl->txt('origin_form_header_sync'));
		$this->addItem($h);
		//
		$te = new ilTextInputGUI($this->pl->txt('origin_form_field_class_name'), 'class_name');
		$te->setRequired(true);
		$this->addItem($te);
		//
		$ro = new ilRadioGroupInputGUI($this->pl->txt('origin_form_field_usage_type'), 'usage_type');
		{
			$prefix = 'origin_form_field_usage_type_';
			$cat = new ilRadioOption($this->pl->txt($prefix . hub::OBJECTTYPE_CATEGORY), hub::OBJECTTYPE_CATEGORY);
			if ($objectProperitesFormGUI = hubOriginObjectPropertiesFormGUI::getInstance($this->parent_gui, hub::OBJECTTYPE_CATEGORY, $this->origin)) {
				$objectProperitesFormGUI->appendToItem($cat);
			}
			$ro->addOption($cat);
			$crs = new ilRadioOption($this->pl->txt($prefix . hub::OBJECTTYPE_COURSE), hub::OBJECTTYPE_COURSE);
			if ($objectProperitesFormGUI = hubOriginObjectPropertiesFormGUI::getInstance($this->parent_gui, hub::OBJECTTYPE_COURSE, $this->origin)) {
				$objectProperitesFormGUI->appendToItem($crs);
			}
			$ro->addOption($crs);
			$usr = new ilRadioOption($this->pl->txt($prefix . hub::OBJECTTYPE_USER), hub::OBJECTTYPE_USER);
			if ($objectProperitesFormGUI = hubOriginObjectPropertiesFormGUI::getInstance($this->parent_gui, hub::OBJECTTYPE_USER, $this->origin)) {
				$objectProperitesFormGUI->appendToItem($usr);
			}
			$ro->addOption($usr);
			$mem = new ilRadioOption($this->pl->txt($prefix . hub::OBJECTTYPE_MEMBERSHIP), hub::OBJECTTYPE_MEMBERSHIP);
			if ($objectProperitesFormGUI = hubOriginObjectPropertiesFormGUI::getInstance($this->parent_gui, hub::OBJECTTYPE_MEMBERSHIP, $this->origin)) {
				$objectProperitesFormGUI->appendToItem($mem);
			}
			$ro->addOption($mem);
		}
		$this->addItem($ro);
		//
		$h = new ilFormSectionHeaderGUI();
		$h->setTitle($this->pl->txt('origin_form_header_connection'));
		$this->addItem($h);
		//
		// Settings
		$ro = new ilRadioGroupInputGUI($this->pl->txt('origin_form_field_conf_type'), 'conf_type');
		{
			$opt1 = new ilRadioOption($this->pl->txt('origin_form_field_conf_type_file'), hubOrigin::CONF_TYPE_FILE, $this->pl->txt('origin_form_field_conf_type_file_info'));
			{
				$te = new ilTextInputGUI($this->pl->txt('origin_form_field_conf_type_file_path'), 'file_path');
				$opt1->addSubItem($te);
			}
			$ro->addOption($opt1);
			$opt1 = new ilRadioOption($this->pl->txt('origin_form_field_conf_type_db'), hubOrigin::CONF_TYPE_DB, $this->pl->txt('origin_form_field_conf_type_db_info'));
			{
				$te = new ilTextInputGUI($this->pl->txt('origin_form_field_conf_type_db_host'), 'db_host');
				$opt1->addSubItem($te);
				$te = new ilTextInputGUI($this->pl->txt('origin_form_field_conf_type_db_port'), 'db_port');
				$opt1->addSubItem($te);
				$te = new ilTextInputGUI($this->pl->txt('origin_form_field_conf_type_db_username'), 'db_username');
				$opt1->addSubItem($te);
				$te = new ilTextInputGUI($this->pl->txt('origin_form_field_conf_type_db_password'), 'db_password');
				$opt1->addSubItem($te);
				$te = new ilTextInputGUI($this->pl->txt('origin_form_field_conf_type_db_database'), 'db_database');
				$opt1->addSubItem($te);
				$te = new ilTextInputGUI($this->pl->txt('origin_form_field_conf_type_db_search_base'), 'db_search_base');
				$opt1->addSubItem($te);
			}
			$ro->addOption($opt1);
		}
		$this->addItem($ro);
		//
		$h = new ilFormSectionHeaderGUI();
		$h->setTitle($this->pl->txt('origin_form_header_notification'));
		$this->addItem($h);
		//
		$te = new ilTextInputGUI($this->pl->txt('origin_form_field_notification_email'), 'notification_email');
		$this->addItem($te);
		//
		$te = new ilTextInputGUI($this->pl->txt('origin_form_field_summary_email'), 'summary_email');
		$this->addItem($te);
		//
		$this->addCommandButton('index', $this->pl->txt('origin_form_button_index'));
	}


	public function fillForm() {
		$array = $this->getValues();
		$this->setValuesByArray($array);
	}


	public function export() {
		$array = $this->getValues();
		header('Content-type: application/json');
		header("Content-Transfer-Encoding: Binary");
		header("Content-disposition: attachment; filename=\"export_" . $array['class_name'] . ".json\"");
		echo json_encode($array);
		exit;
	}


	/**
	 * @param null $json_import
	 */
	public function import($json_import = NULL) {
		$values = json_decode($json_import, true);
		if ($_FILES['import_file']['tmp_name']) {
			$values = json_decode(file_get_contents($_FILES['import_file']['tmp_name']), true);
			$this->setValuesByArray($values);
		}
	}


	/**
	 * @return bool
	 */
	public function fillObject() {
		if (! $this->checkInput()) {
			return false;
		}
		$this->origin->setTitle($this->getInput('title'));
		$this->origin->setDescription($this->getInput('description'));
		$this->origin->setMatchingKeyIlias($this->getInput('matching_key_ilias'));
		$this->origin->setMatchingKeyOrigin($this->getInput('matching_key_origin'));
		$this->origin->setUsageType($this->getInput('usage_type'));
		$this->origin->setClassName($this->getInput('class_name'));
		$this->origin->setActive($this->getInput('active'));
		$this->origin->setConfType($this->getInput('conf_type'));
		$this->origin->conf()->setFilePath($this->getInput('file_path'));
		$this->origin->conf()->setSrvUsername($this->getInput('db_username'));
		$this->origin->conf()->setSrvPassword($this->getInput('db_password'));
		$this->origin->conf()->setSrvHost($this->getInput('db_host'));
		$this->origin->conf()->setSrvDatabase($this->getInput('db_database'));
		$this->origin->conf()->setSrvPort($this->getInput('db_port'));
		$this->origin->conf()->setSrvSearchBase($this->getInput('db_search_base'));
		$this->origin->conf()->setNotificationEmail($this->getInput('notification_email'));
		$this->origin->conf()->setSummaryEmail($this->getInput('summary_email'));
		$objectProperitesFormGUI = hubOriginObjectPropertiesFormGUI::getInstance($this->parent_gui, $this->origin->getUsageType(), $this->origin);
		$objectProperitesFormGUI->fillObject();

		return true;
	}


	/**
	 * @return bool
	 */
	public function saveObject() {
		if (! $this->fillObject()) {
			return false;
		}
		if ($this->origin->getId()) {
			$this->origin->update();
		} else {
			$this->origin->create();
		}
		$objectProperitesFormGUI = hubOriginObjectPropertiesFormGUI::getInstance($this->parent_gui, $this->origin->getUsageType(), $this->origin);
		$objectProperitesFormGUI->saveObject();

		return true;
	}


	/**
	 * @return array
	 */
	protected function getValues() {
		$array = array(
			'title' => $this->origin->getTitle(),
			'description' => $this->origin->getDescription(),
			'matching_key_ilias' => $this->origin->getMatchingKeyIlias(),
			'matching_key_origin' => $this->origin->getMatchingKeyOrigin(),
			'usage_type' => $this->origin->getUsageType(),
			'class_name' => $this->origin->getClassName(),
			'active' => $this->origin->getActive(),
			'conf_type' => $this->origin->getConfType(),
			'file_path' => $this->origin->conf()->getFilePath(),
			'db_username' => $this->origin->conf()->getSrvUsername(),
			'db_password' => $this->origin->conf()->getSrvPassword(),
			'db_host' => $this->origin->conf()->getSrvHost(),
			'db_database' => $this->origin->conf()->getSrvDatabase(),
			'db_port' => $this->origin->conf()->getSrvPort(),
			'db_search_base' => $this->origin->conf()->getSrvSearchBase(),
			'notification_email' => $this->origin->conf()->getNotificationEmail(),
			'summary_email' => $this->origin->conf()->getSummaryEmail(),
		);
		$objectProperitesFormGUI = hubOriginObjectPropertiesFormGUI::getInstance($this->parent_gui, $this->origin->getUsageType(), $this->origin);
		$array = array_merge($objectProperitesFormGUI->returnValuesArray(), $array);

		return $array;
	}
}