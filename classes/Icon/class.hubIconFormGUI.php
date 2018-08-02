<?php
require_once('./Services/Form/classes/class.ilPropertyFormGUI.php');

/**
 * Class hubIconFormGUI
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
class hubIconFormGUI extends ilPropertyFormGUI {

	/**
	 * @var array
	 */
	protected static $file_types = array( 'png' );
	/**
	 * @var ilHubConfigGUI
	 */
	protected $parent_gui;
	/**
	 * @var ilCtrl
	 */
	protected $ctrl;


	/**
	 * @param hubIconGUI        $parent_gui
	 * @param hubIconCollection $hubIconCollection
	 */
	public function __construct($parent_gui, hubIconCollection $hubIconCollection) {
		if (ILIAS_VERSION_NUMERIC >= "5.2") {
			parent::__construct();
		} else {
			parent::ilPropertyFormGUI();
		}
		global $ilCtrl;
		$this->parent_gui = $parent_gui;
		$this->ctrl = $ilCtrl;
		$this->pl = ilHubPlugin::getInstance();
		$this->setFormAction($this->ctrl->getFormAction($this->parent_gui));
		$this->collection = $hubIconCollection;
		$this->large = $hubIconCollection->getLarge();
		$this->medium = $hubIconCollection->getMedium();
		$this->small = $hubIconCollection->getSmall();
		$this->initForm();
	}


	/**
	 * @return string
	 */
	public function getHTML() {
		return parent::getHTML() . '<br><br>';
	}


	protected function initForm() {
		$this->setTitelAndDescription();

		$small = new ilImageFileInputGUI($this->pl->txt('icon_title_' . hubIcon::PREF_SMALL), hubIcon::PREF_SMALL);
		$small->setSuffixes(self::$file_types);
		//		$small->setInfo($this->small->getDeleted());
		if ($this->small->exists()) {
			$small->setImage($this->small->getPath());
		}
		$this->addItem($small);
		//
		$medium = new ilImageFileInputGUI($this->pl->txt('icon_title_' . hubIcon::PREF_MEDIUM), hubIcon::PREF_MEDIUM);
		$medium->setSuffixes(self::$file_types);
		//		$medium->setInfo($this->medium->getDeleted());
		if ($this->medium->exists()) {
			$medium->setImage($this->medium->getPath());
		}
		$this->addItem($medium);
		//
		$large = new ilImageFileInputGUI($this->pl->txt('icon_title_' . hubIcon::PREF_LARGE), hubIcon::PREF_LARGE);
		$large->setSuffixes(self::$file_types);
		//		$large->setInfo($this->large->getDeleted());
		if ($this->large->exists()) {
			$large->setImage($this->large->getPath());
		}
		$this->addItem($large);

		$this->addButtons();
	}


	public function save() {
		if (!$this->checkInput()) {
			return false;
		}
		$this->import(hubIcon::PREF_SMALL);
		$this->import(hubIcon::PREF_MEDIUM);
		$this->import(hubIcon::PREF_LARGE);

		$this->delete(hubIcon::PREF_SMALL);
		$this->delete(hubIcon::PREF_MEDIUM);
		$this->delete(hubIcon::PREF_LARGE);

		return true;
	}


	/**
	 * @param string $type
	 */
	protected function import($type) {
		$input = $this->getInput($type);
		if ($input['tmp_name']) {
			$this->{$type}->importFromUpload($input['tmp_name']);
		}
	}


	/**
	 * @param string $type
	 */
	protected function delete($type) {
		if ($_POST[$type . '_delete']) {
			$icon = $this->{$type};
			/**
			 * @var hubIcon $icon
			 */
			$icon->setDeleted(true);
			$icon->update();
		}
	}


	protected function addButtons() {
		switch ($this->collection->getUsageType()) {
			case  hubIcon::USAGE_OBJECT:
				$this->addCommandButton('save', $this->pl->txt('icon_form_button_save'));
				break;
			case  hubIcon::USAGE_FIRST_DEPENDENCE:
				$this->addCommandButton('saveFirstDep', $this->pl->txt('icon_form_button_save'));
				break;
			case  hubIcon::USAGE_SECOND_DEPENDENCE:
				$this->addCommandButton('saveSecondDep', $this->pl->txt('icon_form_button_save'));
				break;
			case  hubIcon::USAGE_THIRD_DEPENDENCE:
				$this->addCommandButton('saveThirdDep', $this->pl->txt('icon_form_button_save'));
				break;
		}
	}


	protected function setTitelAndDescription() {
		$this->setTitle($this->pl->txt('icon_form_title_' . hubIcon::getFolderName($this->collection->getUsageType())));
		$this->setId('icon_form_' . $this->collection->getUsageType());
	}
}
