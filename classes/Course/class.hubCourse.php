<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/class.hubRepositoryObject.php');
require_once('./Modules/Course/classes/class.ilObjCourse.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Course/class.hubCourseFields.php');

/**
 * Class hubCourse
 *
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.1.04
 */
class hubCourse extends hubRepositoryObject {

	const TABLE_NAME = "sr_hub_course";
	/**
	 * @var ilObjCourse
	 */
	public $ilias_object;
	/**
	 * @var int
	 */
	public static $id_type = self::ILIAS_ID_TYPE_REF_ID;


	/**
	 * @return bool
	 */
	public static function buildILIASObjects() {
		$active_origins = hubOrigin::getOriginsForUsage(hub::OBJECTTYPE_COURSE);
		$active_origin_ids = array();
		foreach ($active_origins as $origin) {
			$active_origin_ids[] = $origin->getId();
		}
		/**
		 * @var hubCourse $hubCourse
		 * @var hubOrigin $hubOrigin
		 */
		foreach (self::get() as $hubCourse) {
			if (!hubSyncHistory::isLoaded($hubCourse->getSrHubOriginId()) || !in_array($hubCourse->getSrHubOriginId(), $active_origin_ids)) {
				continue;
			}
			$id = 'obj_origin_' . $hubCourse->getSrHubOriginId();
			hubDurationLogger2::getInstance($id)->resume();
			$hubOrigin = hubOrigin::getClassnameForOriginId($hubCourse->getSrHubOriginId());
			$hubOriginObj = $hubOrigin::find($hubCourse->getSrHubOriginId());
			$full_title = $hubCourse->getTitlePrefix() . $hubCourse->getTitle() . $hubCourse->getTitleExtension();
			$history = $hubCourse->getHistoryObject();
			switch ($history->getStatus()) {
				case hubSyncHistory::STATUS_NEW:
					if (!hubSyncCron::getDryRun()) {
						$hubCourse->createCourse();
						$hubOriginObj->afterObjectCreation($hubCourse);
					}
					hubCounter::incrementCreated($hubCourse->getSrHubOriginId());
					hubOriginNotification::addMessage($hubCourse->getSrHubOriginId(), $full_title, 'Courses created:');
					break;
				case hubSyncHistory::STATUS_UPDATED:
					if (!hubSyncCron::getDryRun()) {
						$hubCourse->updateCourse();
						$hubOriginObj->afterObjectUpdate($hubCourse);
					}
					hubCounter::incrementUpdated($hubCourse->getSrHubOriginId());
					break;
				case hubSyncHistory::STATUS_DELETED:
					global $tree;
					$path = $tree->getPathId($history->getIliasId());
					if (!hubSyncCron::getDryRun()) {
						$hubCourse->deleteCourse();
						$hubOriginObj->afterObjectDeletion($hubCourse);
					}
					hubCounter::incrementDeleted($hubCourse->getSrHubOriginId());
					hubOriginNotification::addMessage($hubCourse->getSrHubOriginId(), $full_title . ' :: '
						. implode('/', $path), 'Courses deleted with ref_id path:');
					break;
				case hubSyncHistory::STATUS_ALREADY_DELETED:
					hubCounter::incrementIgnored($hubCourse->getSrHubOriginId());
					//					hubOriginNotification::addMessage($hubCourse->getSrHubOriginId(), $full_title, 'Courses ignored:');
					break;
				case hubSyncHistory::STATUS_NEWLY_DELIVERED:
					hubCounter::incrementNewlyDelivered($hubCourse->getSrHubOriginId());
					hubOriginNotification::addMessage($hubCourse->getSrHubOriginId(), $full_title, 'Courses newly delivered:');
					if (!hubSyncCron::getDryRun()) {
						if (ilObjCourse::_lookupDeletedDate($hubCourse->getHistoryObject()->getIliasId())) {
							$hubCourse->createCourse();
						} else {
							$hubCourse->reactivateCourse();
						}
					}
					break;
			}

			if (!hubSyncCron::getDryRun()) {
				$hubOriginObj->afterObjectInit($hubCourse);
			}

			$history->updatePickupDate();
			$hubOrigin::afterObjectModification($hubCourse);

			hubDurationLogger2::getInstance($id)->pause();
		}

		return true;
	}


	public function createCourse() {
		$this->ilias_object = new ilObjCourse();
		$this->ilias_object->setTitle($this->getTitlePrefix() . $this->getTitle() . $this->getTitleExtension());
		$this->ilias_object->setDescription($this->getDescription());
		$this->ilias_object->setImportId($this->returnImportId());
		$this->updateAdditionalFields();
		$this->ilias_object->create();
		$this->ilias_object->setImportantInformation($this->getImportantInformation());
		$this->ilias_object->createReference();
		$node = $this->getDependecesNode();
		$this->ilias_object->putInTree($node);
		$this->ilias_object->setPermissions($node);
		$this->ilias_object->setSubscriptionLimitationType($this->getSubLimitationType());
		$this->ilias_object->updateSettings();
		if ($this->props()->get(hubCourseFields::F_ACTIVATE)) {
			$this->ilias_object->setOfflineStatus(false);
			$this->ilias_object->setActivationType(IL_CRS_ACTIVATION_UNLIMITED);
			$this->ilias_object->update();
		}
		if ($this->props()->get(hubCourseFields::F_CREATE_ICON)) {
			$this->updateIcon($this->ilias_object);
			$this->ilias_object->update();
		}

		// Notification
		if ($this->props()->get(hubCourseFields::F_SEND_NOTIFICATION)) {
			global $ilSetting;
			$mail = new ilMimeMail();
			$mail->autoCheck(false);
			if ($this->props()->get(hubCourseFields::F_NOT_FROM)) {
				$mail->From($this->props()->get(hubCourseFields::F_NOT_FROM));
			} else {
				$mail->From($ilSetting->get('admin_email'));
			}

			$mail->To($this->getNotificationEmail());
			$body = hubCourseFields::getReplacedText($this);
			$mail->Subject($this->props()->get(hubCourseFields::F_NOT_SUBJECT));
			$mail->Body($body);
			$mail->Send();
		}

		$history = $this->getHistoryObject();
		$history->setIliasId($this->ilias_object->getRefId());
		$history->setIliasIdType(self::ILIAS_ID_TYPE_REF_ID);
		$history->update();
	}


	public function updateCourse() {
		$update = false;
		$this->moveObject();
		if ($this->props()->get(hubCourseFields::F_UPDATE_TITLE)) {
			$this->initObject();
			$this->ilias_object->setTitle($this->getTitlePrefix() . $this->getTitle() . $this->getTitleExtension());
			$update = true;
		}
		if ($this->props()->get(hubCourseFields::F_UPDATE_DESCRIPTION)) {
			$this->initObject();
			$this->ilias_object->setDescription($this->getDescription());
			$update = true;
		}
		if ($this->props()->get(hubCourseFields::F_UPDATE_RESPONSIBLE)) {
			$this->initObject();
			$this->ilias_object->setContactResponsibility($this->getResponsible());
			$this->ilias_object->setContactEmail($this->getResponsibleEmail());
			$update = true;
		}
		if ($this->props()->get(hubCourseFields::F_UPDATE_ICON)) {
			$this->initObject();
			$this->updateIcon($this->ilias_object);
			$update = true;
		}
		if ($this->props()->get(hubCourseFields::F_REACTIVATE)) {
			$this->initObject();
			$this->ilias_object->setOfflineStatus(false);
			$this->ilias_object->setActivationType(IL_CRS_ACTIVATION_UNLIMITED);
			$update = true;
		}
		if ($this->getImportantInformation() !== $this->ilias_object->getImportantInformation()) {
			$this->ilias_object->setImportantInformation($this->getImportantInformation());
			$update = true;
		}
		if ($update) {
			$this->ilias_object->setOwner($this->getOwner());
			$this->ilias_object->update();
		}

		$history = $this->getHistoryObject();
		$history->setAlreadyDeleted(false);
		$history->setDeleted(false);
	}


	protected function updateAdditionalFields() {
		if (!$this->ilias_object) {
			return false;
		}
		$this->ilias_object->setContactResponsibility($this->getResponsible());
		$this->ilias_object->setContactEmail($this->getResponsibleEmail());
		$this->ilias_object->setOwner($this->getOwner());
	}


	protected function deleteCourse() {
		if ($this->props()->get(hubCourseFields::F_DELETE)) {
			$hist = $this->getHistoryObject();

			if (!ilObject2::_exists($hist->getIliasId(), true)) {
				hubLog::getInstance()->write('Delete Course: ref_id does not exist, ref_id=' . $hist->getIliasId(), hubLog::L_DEBUG);
				$hist->setAlreadyDeleted(true);
				$hist->setDeleted(true);
				$hist->update();

				return;
			}

			$this->initObject();

			switch ($this->props()->get(hubCourseFields::F_DELETE)) {
				case self::DELETE_MODE_INACTIVE:
					$this->setCourseInactive();
					break;
				case self::DELETE_MODE_DELETE:
					if ($this->ilias_object) {
						hubLog::getInstance()->write('Delete Course: ' . $this->ilias_object->getId(), hubLog::L_DEBUG);
						$this->ilias_object->delete();
					}
					break;
				case self::DELETE_MODE_TRASH:
					global $tree;
					/**
					 * @var ilTree $tree
					 */
					$tree->saveSubTree($this->ilias_object->getRefId(), true);
					break;
				case self::DELETE_MODE_DELETE_OR_INACTIVE:
					if ($this->hasActivities()) {
						hubLog::getInstance()->write('Set Course inactive due to no activities: ' . $this->ilias_object->getId(), hubLog::L_DEBUG);
						$this->setCourseInactive();
					} else {
						hubLog::getInstance()->write('Delete Course: ' . $this->ilias_object->getId(), hubLog::L_DEBUG);
						$this->ilias_object->delete();
					}
					break;
			}
			$hist->setAlreadyDeleted(true);
			$hist->setDeleted(true);
			$hist->update();
		}
	}


	/**
	 * @return bool true if a member-user did anything in this course
	 */
	private function hasActivities() {
		global $ilDB;

		$str = "SELECT 
				    wre.*, dat.*, rbac_ua.*
				FROM
				    catch_write_events AS wre
				        JOIN
				    obj_members AS mem ON mem.obj_id = wre.obj_id
				        AND mem.usr_id = wre.usr_id
				        
				        JOIN object_reference AS ref ON ref.obj_id = wre.obj_id
				        
				        JOIN object_data AS dat ON dat.type = 'role' AND dat.title = CONCAT('il_crs_member_', ref.ref_id)
				        
				        JOIN rbac_ua ON rbac_ua.rol_id = dat.obj_id AND rbac_ua.usr_id = wre.usr_id
				        
				WHERE
				    wre.obj_id = " . $ilDB->quote(ilObject2::_lookupObjId($this->ilias_object->getRefId()), 'integer');

		$query = $ilDB->query($str);
		$has_sets = $ilDB->numRows($query);
		hubLog::getInstance()->write('catch_write_events: ' . $has_sets, hubLog::L_DEBUG);

		return (($has_sets > 0) ? true : false);
	}


	/**
	 * @return int
	 */
	private function getNode() {
		global $tree;
		$key = hubCourseFields::F_NODE_NOPARENT;
		$base_node_ilias = ($this->props()->get($key) ? $this->props()->get($key) : 1);
		if ($this->getParentIdType() == self::PARENT_ID_TYPE_EXTERNAL_ID) {
			if ($this->props()->get(hubCourseFields::F_ORIGIN_LINK)) {
				/**
				 * @var hubCategory $obj
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
			if (!$tree->isInTree($this->getParentId())) {
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
			//DEBUG LOG
			/*
			$warn = 'HUB DEBUG Period Dependences';
			$warn .= ' - node_id: ' . $node_id;
			$warn .= ' - First Dep: ' . $this->getFirstDependence();
			$warn .= ' - Second Dep: ' . $this->getSecondDependence();
			$warn .= ' - Third Dep: ' . $this->getThirdDependence();*/


			$node_id = $this->buildDependeceCategory($this->getFirstDependence(), $node_id, 1);
			$node_id = $this->buildDependeceCategory($this->getSecondDependence(), $node_id, 2);
			$node_id = $this->buildDependeceCategory($this->getThirdDependence(), $node_id, 3);

			//DEBUG LOG
			/*
			$warn .= ' - returned node id: ' . $node_id;
			hubLog::getInstance()->write($warn, hubLog::L_WARN);
			*/

			return $node_id;
		} else {
			return $node_id;
		}
	}


	/**
	 * @param ilObjCategory $ilObjCategory
	 * @param int           $deph
	 */
	protected function updateImportIdForDependence(ilObjCategory $ilObjCategory, $deph) {
		$a_import_id = 'srhub_' . $this->getSrHubOriginId() . '_dep_' . $deph . '_' . $this->getParentId();
		$ilObjCategory->setImportId($a_import_id);
		$ilObjCategory->update();
	}


	/**
	 * @param int $deph
	 *
	 * @deprecated
	 *
	 * @return bool
	 */
	protected function lookupDependenceCategory($deph) {
		global $ilDB;
		/**
		 * @var ilDB $ilDB
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
	 * @var array
	 */
	protected static $updated_dependency_nodes = array();


	/**
	 * @param string $title
	 * @param int    $parent_id
	 * @param int    $depth
	 *
	 * @return int
	 */
	private function buildDependeceCategory($title, $parent_id, $depth) {
		/**
		 * @var ilTree      $tree
		 * @var ilRbacAdmin $rbacadmin
		 */
		if ($title == NULL) {
			return $parent_id;
		}
		global $tree;
		switch ($depth) {
			case 1:
				$usage = hubIcon::USAGE_FIRST_DEPENDENCE;
				break;
			case 2:
				$usage = hubIcon::USAGE_SECOND_DEPENDENCE;
				break;
			case 3:
				$usage = hubIcon::USAGE_THIRD_DEPENDENCE;
				break;
		}
		foreach ($tree->getChildsByType($parent_id, 'cat') as $child) {
			if ($child['title'] == $title) {
				if (!in_array($child['ref_id'], self::$updated_dependency_nodes)) {
					$cat = new ilObjCategory($child['ref_id']);
					if ($this->props()->get(hubCourseFields::F_UPDATE_ICON)) {
						//$this->updateIcon($cat, $usage);
					}
					$this->updateImportIdForDependence($cat, $depth);
					self::$updated_dependency_nodes[] = $child['ref_id'];
				}

				return $child['ref_id'];
			}
		}
		$cat = new ilObjCategory();
		$cat->setTitle($title);
		$cat->create();
		$this->updateImportIdForDependence($cat, $depth);
		$cat->addTranslation($title, '', 'DE', true);
		$cat->createReference();
		$cat->putInTree($parent_id);
		$cat->setPermissions($parent_id);
		if ($this->props()->get(hubCourseFields::F_CREATE_ICON)) {
			$this->updateIcon($cat, $usage);
		}
		self::$updated_dependency_nodes[] = $cat->getRefId();

		return $cat->getRefId();
	}


	protected function initObject() {
		if (!isset($this->ilias_object)) {
			$this->ilias_object = new ilObjCourse($this->getHistoryObject()->getIliasId());
		}
	}


	protected function moveObject() {
		if ($this->props()->get(hubCourseFields::F_MOVE)) {
			global $tree, $rbacadmin;
			$this->initObject();
			$dependecesNode = $this->getDependecesNode();
			if ($tree->isDeleted($this->ilias_object->getRefId())) {
				hubLog::getInstance()->write('Course restored: ' . $this->getExtId());
				$ilRepUtil = new ilRepUtil();
				$ilRepUtil->restoreObjects($dependecesNode, array( $this->ilias_object->getRefId() ));
			}
			try {
				$ref_id = $this->ilias_object->getRefId();
				$old_parent = $tree->getParentId($ref_id);
				if ($old_parent != $dependecesNode) {
					$str = 'Moving Course ' . $this->getExtId() . ' from ' . $old_parent . ' to ' . $dependecesNode;
					$tree->moveTree($ref_id, $dependecesNode);
					$rbacadmin->adjustMovedObjectPermissions($ref_id, $old_parent);
					hubLog::getInstance()->write($str);
					hubOriginNotification::addMessage($this->getSrHubOriginId(), $str, 'Moved:');
				}
			} catch (InvalidArgumentException $e) {
				$str1 = 'Error moving Course in Tree: ' . $this->getExtId();

				hubLog::getInstance()->write($str1);
			}
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
	protected $important_information = '';
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
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $administrators = '';
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
	/**
	 * @var int
	 *
	 * @db_has_field        true
	 * @db_fieldtype        integer
	 * @db_length           1
	 */
	protected $sub_limitation_type = 0;
	/**
	 * @var int
	 *
	 * @db_has_field        true
	 * @db_fieldtype        integer
	 * @db_length           1
	 */
	protected $view_mode = 0;
	/**
	 * @var int
	 *
	 * @db_has_field        true
	 * @db_fieldtype        integer
	 * @db_length           8
	 */
	protected $didactic_template_id = 0;
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


	/**
	 * @param string $important_information
	 */
	public function setImportantInformation($important_information) {
		$this->important_information = $important_information;
	}


	/**
	 * @return string
	 */
	public function getImportantInformation() {
		return $this->important_information;
	}


	/**
	 * @param int $sub_limitation_type
	 */
	public function setSubLimitationType($sub_limitation_type) {
		$this->sub_limitation_type = $sub_limitation_type;
	}


	/**
	 * @return int
	 */
	public function getSubLimitationType() {
		return $this->sub_limitation_type;
	}


	/**
	 * @param int $view_mode
	 */
	public function setViewMode($view_mode) {
		$this->view_mode = $view_mode;
	}


	/**
	 * @return int
	 */
	public function getViewMode() {
		return $this->view_mode;
	}


	/**
	 * @param int $didactic_template_id
	 */
	public function setDidacticTemplateId($didactic_template_id) {
		$this->didactic_template_id = $didactic_template_id;
	}


	/**
	 * @return int
	 */
	public function getDidacticTemplateId() {
		return $this->didactic_template_id;
	}


	/**
	 * set course offline
	 */
	protected function setCourseInactive() {
		hubLog::getInstance()->write('Set Course inactive: ' . $this->ilias_object->getId(), hubLog::L_DEBUG);
		$this->ilias_object->setOfflineStatus(true);
		if ($this->props()->get(hubCourseFields::F_DELETED_ICON)) {
			$icon = $this->props()->getIconPath('_deleted');
			if ($icon) {
				$this->ilias_object->saveIcons($icon, $icon, $icon);
			}
		}
		$this->ilias_object->update();
	}


	/**
	 * set course online and set deleted & already deleted to false
	 */
	public function reactivateCourse() {
		$this->ilias_object = new ilObjCourse($this->getHistoryObject()->getIliasId(), true);
		$this->ilias_object->setOfflineStatus(false);
		$this->ilias_object->update();

		$history = $this->getHistoryObject();
		$history->setAlreadyDeleted(false);
		$history->setDeleted(false);
		$history->update();
	}


	/**
	 * @param string $field_name
	 *
	 * @return mixed|string
	 */
	public function sleep($field_name) {
		switch ($field_name) {
			case 'administrators':
				return json_encode($this->administrators);
			default:
				return parent::sleep($field_name);
		}
	}


	/**
	 * @param string $field_name
	 * @param string $field_value
	 *
	 * @return mixed
	 */
	public function wakeUp($field_name, $field_value) {
		switch ($field_name) {
			case 'administrators':
				return json_decode($field_value, true);
			default:
				return parent::wakeUp($field_name, $field_value);
		}
	}
}
