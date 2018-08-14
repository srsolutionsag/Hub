<?php
require_once('class.hubObject.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Icon/class.hubIconCollection.php');

/**
 * Class hubRepositoryObject
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.1.04
 */
abstract class hubRepositoryObject extends hubObject {

	const PARENT_ID_TYPE_REF_ID = 1;
	const PARENT_ID_TYPE_EXTERNAL_ID = 2;
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           2048
	 * @con_index           true
	 */
	protected $title = '';
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           2048
	 */
	protected $description = '';
	/**
	 * @var int
	 *
	 * @db_has_field        true
	 * @db_is_notnull       true
	 * @db_fieldtype        text
	 * @db_length           128
	 * @con_index           true
	 */
	protected $parent_id = 0;
	/**
	 * @var int
	 *
	 * @db_has_field        true
	 * @db_is_notnull       true
	 * @db_fieldtype        text
	 * @db_length           1
	 */
	protected $parent_id_type = self::PARENT_ID_TYPE_EXTERNAL_ID;


	/**
	 * @return string
	 */
	public function returnParentImportId() {
		return self::IMPORT_PREFIX . $this->getHistoryObject()->getSrHubOriginId() . '_' . $this->getParentId();
	}


	/***
	 * @var ilObjCourse
	 */
	public $ilias_object;


	/**
	 * @param ilObject|ilObject2 $ilias_object
	 *
	 * @param int                $usage
	 *
	 * @return bool
	 */
	protected function updateIcon(ilObject $ilias_object, $usage = hubIcon::USAGE_OBJECT) {
		$hubOrigin = hubOrigin::find($this->getSrHubOriginId());
		/**
		 * @var hubOrigin $hubOrigin
		 */
		if ($hubOrigin) {
			$hubIconCollection = hubIconCollection::getInstance($hubOrigin, $usage);
			$small = $hubIconCollection->getSmall()->getPath();
			$medium = $hubIconCollection->getMedium()->getPath();
			$large = $hubIconCollection->getLarge()->getPath();
			if ($large) {
				$ilias_object->saveIcons('./'.$large);
			} else {
				$ilias_object->removeCustomIcon();
				return false;
			}
		} else {
			return false;
		}
	}


	//
	// Setter & Getter
	//
	/**
	 * @param string $title
	 */
	public function setTitle($title) {
		$this->title = $title;
	}


	/**
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}


	/**
	 * @param int $parent_id_type
	 */
	public function setParentIdType($parent_id_type) {
		$this->parent_id_type = $parent_id_type;
	}


	/**
	 * @return int
	 */
	public function getParentIdType() {
		return $this->parent_id_type;
	}


	/**
	 * @param int $parent_id
	 */
	public function setParentId($parent_id) {
		$this->parent_id = $parent_id;
	}


	/**
	 * @return int
	 */
	public function getParentId() {
		return $this->parent_id;
	}


	/**
	 * @param string $description
	 */
	public function setDescription($description) {
		$this->description = $description;
	}


	/**
	 * @return string
	 */
	public function getDescription() {
		return $this->description;
	}
}
