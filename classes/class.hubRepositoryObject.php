<?php
require_once('class.hubObject.php');

/**
 * Class hubRepositoryObject
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
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


	protected function updateIcon() {
		$icon = $this->props()->getIconPath();
		if ($icon) {
			$this->ilias_object->saveIcons($icon, $icon, $icon);
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

?>