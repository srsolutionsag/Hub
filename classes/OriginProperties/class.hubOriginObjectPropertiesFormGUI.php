<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/OriginProperties/class.hubOriginObjectPropertiesFields.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Membership/class.hubMembershipFields.php');
require_once('./Services/Form/classes/class.ilPropertyFormGUI.php');

/**
 * Class hubOriginObjectPropertiesFormGUI
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.1.04
 */
abstract class hubOriginObjectPropertiesFormGUI extends ilPropertyFormGUI {

	/**
	 * @var
	 */
	protected $parent_gui;
	/**
	 * @var  ilCtrl
	 */
	protected $ctrl;
	/**
	 * @var
	 */
	protected $origin_properties;
	/**
	 * @var hubOrigin
	 */
	protected $origin;


	/**
	 * @return string
	 * @description return prefix of object_type like cat, usr, crs, mem
	 */
	abstract protected function getPrefix();


	/**
	 * @param $var
	 *
	 * @return string
	 */
	protected function txt($var) {
		return $this->pl->txt($this->getPrefix() . '_prop_' . $var);
	}


	/**
	 * @param           $parent_gui
	 * @param           $usage_type
	 * @param hubOrigin $origin
	 *
	 * @return bool|hubCategoryPropertiesFormGUI|hubCoursePropertiesFormGUI
	 */
	public static function getInstance($parent_gui, $usage_type, hubOrigin $origin) {
		require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Category/class.hubCategoryPropertiesFormGUI.php');
		require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Course/class.hubCoursePropertiesFormGUI.php');
		require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/User/class.hubUserPropertiesFormGUI.php');
		require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Membership/class.hubMembershipPropertiesFormGUI.php');
		switch ($usage_type) {
			case hub::OBJECTTYPE_CATEGORY:
				return new hubCategoryPropertiesFormGUI($parent_gui, $origin);
			case hub::OBJECTTYPE_COURSE:
				return new hubCoursePropertiesFormGUI($parent_gui, $origin);
			case hub::OBJECTTYPE_USER:
				return new hubUserPropertiesFormGUI($parent_gui, $origin);
			case hub::OBJECTTYPE_MEMBERSHIP:
				return new hubMembershipPropertiesFormGUI($parent_gui, $origin);
		}

		return false;
	}


	public function __construct($parent_gui, hubOrigin $origin) {
		global $ilCtrl;
		$this->origin = $origin;
		$this->parent_gui = $parent_gui;
		$this->ctrl = $ilCtrl;
		$this->ctrl->saveParameter($parent_gui, 'origin_id');
		$this->pl = new ilHubPlugin();
		$this->origin_properties = hubOriginObjectProperties::getInstance($this->origin->getId());
		$this->locked = $this->origin->isLocked();
		$this->initStandardFields();
		$this->initForm();
		$origin->getObject()->appendFieldsToPropForm($this);
		$this->personalizeFields();
	}


	/**
	 * @param ilFormPropertyGUI $item
	 */
	protected function personalizeOneField($item) {
		/**
		 * @var ilRadioGroupInputGUI $item
		 * @var ilRadioGroupInputGUI $subItem
		 * @var ilRadioOption        $op
		 */
		if (self::hasValue($item)) {
			$item->setPostVar($this->getPrefix() . '_' . $item->getPostVar());
			if ($item instanceof ilRadioGroupInputGUI) {
				foreach ($item->getOptions() as $op) {
					foreach ($op->getSubItems() as $subItem) {
						$this->personalizeOneField($subItem);
					}
				}
			} else {
				if (self::hasSubitems($item)) {
					foreach ($item->getSubItems() as $subItem) {
						$this->personalizeOneField($subItem);
					}
				}
			}
		}
	}


	protected function personalizeFields() {
		foreach ($this->getItems() as $item) {
			$this->personalizeOneField($item);
		}
	}


	private function initStandardFields() {
		$se = new ilSelectInputGUI($this->pl->txt('com_prop_link_to_origin'), hubOriginObjectPropertiesFields::F_ORIGIN_LINK);
		/**
		 * @var $origin hubOrigin
		 */
		$opt[0] = $this->pl->txt('common_none');
		foreach (hubOrigin::get() as $origin) {
			$opt[$origin->getId()] = $origin->getTitle();
		}
		$se->setOptions($opt);
		$this->addItem($se);
		//
		$cb = new ilCheckboxInputGUI($this->pl->txt('com_prop_use_ext_status'), hubOriginObjectPropertiesFields::F_USE_EXT_STATUS);
		$status_string = '';
		foreach (hubSyncHistory::getAllStatusAsArray() as $name => $int) { // FSX externer Status
			$status_string .= $name . ': ' . $int . ', ';
		}
		$cb->setInfo($status_string);
		// $this->addItem($cb);

		$cb = new ilCheckboxInputGUI($this->pl->txt('com_prop_check_amount'), hubOriginObjectPropertiesFields::F_CHECK_AMOUNT);
		$cb->setInfo($this->pl->txt('com_prop_check_amount_info'));
		$se = new ilSelectInputGUI($this->pl->txt('com_prop_check_amount_percentage'), hubOriginObjectPropertiesFields::F_CHECK_AMOUNT_PERCENTAGE);
		$opt = array(
			10 => '10%',
			20 => '20%',
			30 => '30%',
			40 => '40%',
			50 => '50%',
			60 => '60%',
			70 => '70%',
			80 => '80%',
			90 => '90%',
			100 => '100%',
		);
		$se->setOptions($opt);
		$cb->addSubItem($se);
		$this->addItem($cb);
		//
		$cb = new ilCheckboxInputGUI($this->pl->txt('com_prop_shortlink'), hubOriginObjectPropertiesFields::F_SHORTLINK);
		$this->addItem($cb);
	}


	/**
	 * @param ilPropertyFormGUI $form_gui
	 *
	 * @return ilPropertyFormGUI
	 */
	public function appendToForm(ilPropertyFormGUI $form_gui) {
		if ($this->origin->getId()) {
			foreach ($this->getItems() as $item) {
				if (self::hasValue($item)) {
					$item->setDisabled($this->locked);
				}
				if (self::hasSubitems($item)) {
					foreach ($item->getSubItems() as $subitem) {
						$subitem->setDisabled($this->locked);
					}
				}
				$form_gui->addItem($item);
			}
		}

		return $form_gui;
	}


	/**
	 * @param $form_item
	 *
	 * @return mixed
	 */
	public function appendToSubItem($form_item) {
		foreach ($this->getItems() as $item) {
			$form_item->addSubItem($item);
		}

		return $form_item;
	}


	/**
	 * @description build FormElements
	 */
	abstract protected function initForm();


	public function fillForm() {
		$this->setValuesByArray($this->returnValuesArray());
	}


	/**
	 * @return mixed
	 * @description $array = array('title' => $this->origin->getTitle(), // [...]);
	 */
	public function returnValuesArray() {
		$array = array();
		/**
		 * @var $item    ilCheckboxInputGUI
		 * @var $subItem ilCheckboxInputGUI
		 */
		foreach ($this->getItems() as $item) {
			$this->returnValuesOfItem($item, $array);
		}

		return $array;
	}


	/**
	 * @param $item
	 * @param $array
	 */
	public function returnValuesOfItem($item, &$array) {
		if (self::hasValue($item)) {
			$value = $this->origin_properties->getByShortPrefix($item->getPostVar());
			$array[$item->getPostVar()] = $value;
			foreach (self::getSubItems($item) as $subtitem) {
				$this->returnValuesOfItem($subtitem, $array);
			}
		}
	}


	/**
	 * @param $item
	 *
	 * @return bool
	 */
	public static function hasSubitems($item) {
		return (! $item instanceof ilFormSectionHeaderGUI AND ! $item instanceof ilRadioGroupInputGUI AND ! $item instanceof ilMultiSelectInputGUI);
	}


	/**
	 * @param $item
	 *
	 * @return bool
	 */
	public static function hasValue($item) {
		return (! $item instanceof ilFormSectionHeaderGUI AND $item instanceof ilFormPropertyGUI);
	}


	/**
	 * @param $item
	 *
	 * @return array
	 */
	public static function getSubItems($item) {
		$return = array();
		if (self::hasSubitems($item)) {
			return $item->getSubItems();
		} elseif ($item instanceof ilRadioGroupInputGUI) {
			foreach ($item->getOptions() as $op) {
				$return[] = $op->getSubItems();
			}
		}

		return $return;
	}


	protected function fillValueByItem($item) {
		if (self::hasValue($item)) {
			$this->origin_properties->setByKey($item->getPostVar(), $this->getInput($item->getPostVar()));
			foreach (self::getSubItems($item) as $subtitem) {
				$this->fillValueByItem($subtitem);
			}
		}
	}


	/**
	 * @return bool
	 */
	protected function fillObjectValues() {
		foreach ($this->getItems() as $item) {
			$this->fillValueByItem($item);
		}
	}


	/**
	 * @return bool
	 */
	public function fillObject() {
		if (! $this->checkInput()) {
			return false;
		}

		return $this->fillObjectValues();
	}


	/**
	 * @return bool
	 */
	public function saveObject() {
		if (! $this->fillObject()) {
			return false;
		}

		return true;
	}
}