<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/class.hubRepositoryObject.php');
require_once('./Modules/Course/classes/class.ilObjCourse.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Course/class.hubCourseFields.php');

/**
 * Class hubCourse
 *
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.1.03
 */
class hubCourse extends hubRepositoryObject {

	/**
	 * @var ilObjCourse
	 */
	public $ilias_object;


	/**
	 * @return string
	 */
	static function returnDbTableName() {
		return 'sr_hub_course';
	}


	/**
	 * @return bool
	 */
	public static function buildILIASObjects() {
		/**
		 * @var $hubCourse hubCourse
		 * @var $hubOrigin hubOrigin
		 */
		hubCounter::logRunning();
		foreach (self::get() as $hubCourse) {
			if (! hubSyncHistory::isLoaded($hubCourse->getSrHubOriginId())) {
				continue;
			}

			$full_title = $hubCourse->getTitlePrefix() . $hubCourse->getTitle() . $hubCourse->getTitleExtension();
			$history = $hubCourse->getHistoryObject();
			switch ($history->getStatus()) {
				case hubSyncHistory::STATUS_NEW:
					if (! hubSyncCron::getDryRun()) {
						$hubCourse->createCourse();
					}
					hubCounter::incrementCreated($hubCourse->getSrHubOriginId());
					hubOriginNotification::addMessage($hubCourse->getSrHubOriginId(), $full_title, 'Courses created:');
					break;
				case hubSyncHistory::STATUS_UPDATED:
					if (! hubSyncCron::getDryRun()) {
						$hubCourse->updateCourse();
					}
					hubCounter::incrementUpdated($hubCourse->getSrHubOriginId());
					break;
				case hubSyncHistory::STATUS_DELETED:
					if (! hubSyncCron::getDryRun()) {
						$hubCourse->deleteCourse();
					}
					hubCounter::incrementDeleted($hubCourse->getSrHubOriginId());
					hubOriginNotification::addMessage($hubCourse->getSrHubOriginId(), $full_title, 'Courses deleted:');
					break;
				case hubSyncHistory::STATUS_ALREADY_DELETED:
					hubCounter::incrementIgnored($hubCourse->getSrHubOriginId());
//					hubOriginNotification::addMessage($hubCourse->getSrHubOriginId(), $full_title, 'Courses ignored:');
					break;
				case hubSyncHistory::STATUS_NEWLY_DELIVERED:
					hubCounter::incrementNewlyDelivered($hubCourse->getSrHubOriginId());
					hubOriginNotification::addMessage($hubCourse->getSrHubOriginId(), $full_title, 'Courses newly delivered:');
					if (! hubSyncCron::getDryRun()) {
						$hubCourse->updateCourse();
					}
					break;
			}
			$history->updatePickupDate();
			$hubOrigin = hubOrigin::getClassnameForOriginId($hubCourse->getSrHubOriginId());
			$hubOrigin::afterObjectModification($hubCourse);
		}

		return true;
	}


	public function createCourse() {
		$this->ilias_object = new ilObjCourse();
		$this->ilias_object->setTitle($this->getTitlePrefix() . $this->getTitle() . $this->getTitleExtension());
		$this->ilias_object->setDescription($this->getDescription());
		$this->ilias_object->setImportId($this->returnImportId());
		if ($this->props()->get(hubCourseFields::F_ACTIVATE)) {
			$this->ilias_object->setActivationType(IL_CRS_ACTIVATION_UNLIMITED);
		}
		$this->updateAdditionalFields();
		$this->ilias_object->create();
		$this->ilias_object->createReference();
		$node = $this->getDependecesNode();
		$this->ilias_object->putInTree($node);
		$this->ilias_object->setPermissions($node);
		if ($this->props()->get(hubCourseFields::F_CREATE_ICON)) {
			$this->updateIcon();
			$this->ilias_object->update();
		}
		$history = $this->getHistoryObject();
		$history->setIliasId($this->ilias_object->getRefId());
		$history->setIliasIdType(self::ILIAS_ID_TYPE_REF_ID);
		$history->update();
	}


	public function updateCourse() {
		$update = false;
		if ($this->props()->get(hubCourseFields::F_MOVE)) { //} AND $this->getNode() != $tree->getParentId($ref_id)) {
			global $tree, $rbacadmin;
			$this->initObject();
			$ref_id = $this->ilias_object->getRefId();
			$old_parent = $tree->getParentId($ref_id);
			$tree->moveTree($ref_id, $this->getDependecesNode());
			$rbacadmin->adjustMovedObjectPermissions($ref_id, $old_parent);
			$update = true;
		}
		if ($this->props()->get(hubCourseFields::F_UPDATE_TITLE)) {
			$this->initObject();
			$this->ilias_object->setTitle($this->getTitlePrefix() . $this->getTitle() . $this->getTitleExtension());
			$this->ilias_object->setDescription($this->getDescription());
			$update = true;
		}
		if ($this->props()->get(hubCourseFields::F_UPDATE_DESCRIPTION)) {
			$this->initObject();
			$this->ilias_object->setDescription($this->getDescription());
			$update = true;
		}
		if ($this->props()->get(hubCourseFields::F_UPDATE_ICON)) {
			$this->initObject();
			$this->updateIcon();
			$update = true;
		}
		if ($this->props()->get(hubCourseFields::F_REACTIVATE)) {
			$this->initObject();
			$this->ilias_object->setActivationType(IL_CRS_ACTIVATION_UNLIMITED);
			$update = true;
		}
		if ($update) {
			$this->updateAdditionalFields();
			$this->ilias_object->update();
		}
		$history = $this->getHistoryObject();
		$history->setAlreadyDeleted(false);
		$history->setDeleted(false);
	}


	protected function updateAdditionalFields() {
		if (! $this->ilias_object) {
			return false;
		}
		$this->ilias_object->setContactResponsibility($this->getResponsible());
		$this->ilias_object->setContactEmail($this->getResponsibleEmail());
		$this->ilias_object->setOwner($this->getOwner());
	}


	protected function deleteCourse() {
		if ($this->props()->get(hubCourseFields::F_DELETE)) {
			$hist = $this->getHistoryObject();
			$this->initObject();
			switch ($this->props()->get(hubCourseFields::F_DELETE)) {
				case self::DELETE_MODE_INACTIVE:
					hubLog::getInstance()->write('Set Course inactive: ' . $this->ilias_object->getId(), hubLog::L_DEBUG);
					$this->ilias_object->setActivationType(IL_CRS_ACTIVATION_OFFLINE);
					if ($this->props()->get(hubCourseFields::F_DELETED_ICON)) {
						$icon = $this->props()->getIconPath('_deleted');
						if ($icon) {
							$this->ilias_object->saveIcons($icon, $icon, $icon);
						}
					}
					$this->ilias_object->update();
					break;
				case self::DELETE_MODE_DELETE:
					$this->ilias_object->delete();
					$hist->setIliasId(NULL);
					break;
			}
			$hist->setAlreadyDeleted(true);
			$hist->setDeleted(true);
			$hist->update();
		}
	}


	/**
	 * @return int
	 */
	private function getNode() {
		global $tree;
		$key = hubCourseFields::F_NODE_NOPARENT;
		$base_node_ilias = ($this->props()->get($key) ? $this->props()->get($key) : 1);
		if ($this->getParentIdType() == self::PARENT_ID_TYPE_EXTERNAL_ID) {
			if ($this->props()->get(hubCourseFields::ORIGIN_LINK)) {
				/**
				 * @var $obj hubCategory
				 */
				$obj = hubCategory::find($this->getParentId());
				$ilias_id = $obj->getHistoryObject()->getIliasId();
				if ($ilias_id) {
					return $ilias_id;
				}
			} else {
				// FSX?
			}
		} elseif ($this->getParentIdType() == self::PARENT_ID_TYPE_REF_ID) {
			if (! $tree->isInTree($this->getParentId())) {
				return $base_node_ilias;
			} else {
				return $this->getParentId();
			}
		}

		return $base_node_ilias;
	}


	/**
	 * @return bool
	 */
	private function hasDependences() {
		return $this->getFirstDependence() != NULL OR $this->getSecondDependence() != NULL OR $this->getThirdDependence() != NULL;
	}


	/**
	 * @return int
	 */
	private function getDependecesNode() {
		$node_id = $this->getNode();
		if ($this->hasDependences()) {
			$node_id = $this->buildDependeceCategory($this->getFirstDependence(), $node_id, 1);
			$node_id = $this->buildDependeceCategory($this->getSecondDependence(), $node_id, 2);
			$node_id = $this->buildDependeceCategory($this->getThirdDependence(), $node_id, 3);

			return $node_id;
		} else {
			return $node_id;
		}
	}


	/**
	 * @param ilObjCategory $ilObjCategory
	 * @param               $deph
	 */
	protected function updateImportIdForDependence(ilObjCategory $ilObjCategory, $deph) {
		$a_import_id = 'srhub_' . $this->getSrHubOriginId() . '_dep_' . $deph . '_' . $this->getParentId();
		$ilObjCategory->setImportId($a_import_id);
		$ilObjCategory->update();
	}


	/**
	 * @param $deph
	 *
	 * @return bool
	 */
	protected function lookupDependenceCategory($deph) {
		global $ilDB;
		/**
		 * @var $ilDB ilDB
		 */
		$key = 'srhub_' . $this->getSrHubOriginId() . '_dep_' . $deph . '_' . $this->getParentId();
		$query = 'SELECT ref_id
				FROM object_data dat
				JOIN object_reference ref ON ref.obj_id = dat.obj_id
				WHERE dat.import_id = ' . $ilDB->quote($key, 'text');
		$res = $ilDB->query($query);
		while ($row = $ilDB->fetchObject($res)) {
			return $row->ref_id;
		}

		return false;
	}


	/**
	 * @param $title
	 * @param $parent_id
	 * @param $deph
	 *
	 * @return int
	 */
	private function buildDependeceCategory($title, $parent_id, $deph) {
		/**
		 * @var $tree      ilTree
		 * @var $rbacadmin ilRbacAdmin
		 */
		if ($title == NULL) {
			return $parent_id;
		}
		global $tree;
		foreach ($tree->getChildsByType($parent_id, 'cat') as $child) {
			if ($child['title'] == $title) {
				$cat = new ilObjCategory($child['ref_id']);
				$this->updateImportIdForDependence($cat, $deph);

				return $child['ref_id'];
			}
		}
		$cat = new ilObjCategory();
		$cat->setTitle($title);
		$cat->create();
		$this->updateImportIdForDependence($cat, $deph);
		$cat->addTranslation($title, '', 'DE', true);
		$cat->createReference();
		$cat->putInTree($parent_id);
		$cat->setPermissions($parent_id);

		return $cat->getRefId();
	}


	protected function initObject() {
		if (! isset($this->ilias_object)) {
			$this->ilias_object = new ilObjCourse($this->getHistoryObject()->getIliasId());
		}
	}



	//
	// Fields
	//
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           2048
	 */
	protected $type = '';
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           128
	 */
	protected $period = '';
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           2048
	 */
	protected $learning_target = '';
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           2048
	 */
	protected $responsible = '';
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           128
	 */
	protected $language = '';
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           128
	 */
	protected $first_dependence = '';
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           128
	 */
	protected $second_dependence = '';
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           128
	 */
	protected $third_dependence = '';
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           128
	 */
	protected $title_prefix = '';
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           128
	 */
	protected $title_extension = '';
	/**
	 * @var array
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $administrators = array();
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           128
	 */
	protected $responsible_email = '';
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           128
	 */
	protected $notification_email = '';
	/**
	 * @var int
	 *
	 * @db_has_field        true
	 * @db_fieldtype        integer
	 * @db_length           8
	 */
	protected $owner = 6;


	//
	// Setter & Getter
	//
	/**
	 * @param string $language
	 */
	public function setLanguage($language) {
		$this->language = $language;
	}


	/**
	 * @return string
	 */
	public function getLanguage() {
		return $this->language;
	}


	/**
	 * @param string $learning_target
	 */
	public function setLearningTarget($learning_target) {
		$this->learning_target = $learning_target;
	}


	/**
	 * @return string
	 */
	public function getLearningTarget() {
		return $this->learning_target;
	}


	/**
	 * @param string $period
	 */
	public function setPeriod($period) {
		$this->period = $period;
	}


	/**
	 * @return string
	 */
	public function getPeriod() {
		return $this->period;
	}


	/**
	 * @param string $responsible
	 */
	public function setResponsible($responsible) {
		$this->responsible = $responsible;
	}


	/**
	 * @return string
	 */
	public function getResponsible() {
		return $this->responsible;
	}


	/**
	 * @param string $type
	 */
	public function setType($type) {
		$this->type = $type;
	}


	/**
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}


	/**
	 * @param string $first_dependence
	 */
	public function setFirstDependence($first_dependence) {
		$this->first_dependence = $first_dependence;
	}


	/**
	 * @return string
	 */
	public function getFirstDependence() {
		return $this->first_dependence;
	}


	/**
	 * @param string $second_dependence
	 */
	public function setSecondDependence($second_dependence) {
		$this->second_dependence = $second_dependence;
	}


	/**
	 * @return string
	 */
	public function getSecondDependence() {
		return $this->second_dependence;
	}


	/**
	 * @param string $third_dependence
	 */
	public function setThirdDependence($third_dependence) {
		$this->third_dependence = $third_dependence;
	}


	/**
	 * @return string
	 */
	public function getThirdDependence() {
		return $this->third_dependence;
	}


	/**
	 * @param string $title_extension
	 */
	public function setTitleExtension($title_extension) {
		$this->title_extension = $title_extension;
	}


	/**
	 * @return string
	 */
	public function getTitleExtension() {
		return $this->title_extension;
	}


	/**
	 * @param string $title_prefix
	 */
	public function setTitlePrefix($title_prefix) {
		$this->title_prefix = $title_prefix;
	}


	/**
	 * @return string
	 */
	public function getTitlePrefix() {
		return $this->title_prefix;
	}


	/**
	 * @param string $responsible_email
	 */
	public function setResponsibleEmail($responsible_email) {
		$this->responsible_email = $responsible_email;
	}


	/**
	 * @return string
	 */
	public function getResponsibleEmail() {
		return $this->responsible_email;
	}


	/**
	 * @param int $owner
	 */
	public function setOwner($owner) {
		$this->owner = $owner;
	}


	/**
	 * @return int
	 */
	public function getOwner() {
		return $this->owner;
	}


	/**
	 * @param string $notification_email
	 */
	public function setNotificationEmail($notification_email) {
		$this->notification_email = $notification_email;
	}


	/**
	 * @return string
	 */
	public function getNotificationEmail() {
		return $this->notification_email;
	}
}

?>