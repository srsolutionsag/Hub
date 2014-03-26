<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/class.srModelObjectRepositoryObject.php');
require_once('./Modules/Category/classes/class.ilObjCategory.php');

/**
 * Class hubCategory
 *
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 *
 * @revision $r$
 */
class hubCategory extends srModelObjectRepositoryObject {

	/**
	 * @return string
	 */
	static function returnDbTableName() {
		return 'sr_hub_category';
	}


	/**
	 * @return bool|mixed
	 */
	public static function buildILIASObjects() {
		/**
		 * @var $hubOrigin hubOrigin
		 */
		foreach (hubOrigin::getOriginsForUsage(hub::OBJECTTYPE_CATEGORY) as $hubOrigin) {
			self::buildForParentId($hubOrigin->props()->getBaseNodeExternal());
		}

		return true;
	}


	/**
	 * @param $parent_id
	 */
	private static function buildForParentId($parent_id = 0) {
		/**
		 * @var $hubCategory hubCategory
		 */
		hubCounter::logRunning();
		foreach (self::where(array( 'parent_id' => $parent_id ))->get() as $hubCategory) {
			if (! hubSyncHistory::isLoaded($hubCategory->getSrHubOriginId())) {
				continue;
			}
			$hubCategory->loadObjectProperties();
			$existing_ref_id = 0;
			switch ($hubCategory->object_properties->getSyncfield()) {
				case 'title':
					$existing_ref_id = $hubCategory->lookupRefIdByTitle();
					break;
			}
			if ($existing_ref_id > 1 AND $hubCategory->getHistoryObject()->getStatus() == hubSyncHistory::STATUS_NEW) {
				$history = $hubCategory->getHistoryObject();
				$history->setIliasId($existing_ref_id);
				$history->setIliasIdType(self::ILIAS_ID_TYPE_USER);
				$history->update();
			}
			$hubCategory->loadObjectProperties();
			switch ($hubCategory->getHistoryObject()->getStatus()) {
				case hubSyncHistory::STATUS_NEW:
					$hubCategory->createCategory();
					hubCounter::incrementCreated($hubCategory->getSrHubOriginId());
					hubOriginNotification::addMessage($hubCategory->getSrHubOriginId(), $hubCategory->getTitle(), 'Category created:');
					break;
				case hubSyncHistory::STATUS_UPDATED:
					$hubCategory->updateCategory();
					hubCounter::incrementUpdated($hubCategory->getSrHubOriginId());
					//					hubOriginNotification::addMessage($hubCategory->getSrHubOriginId(), $hubCategory->getTitle(), 'Category updated:');
					break;
				case hubSyncHistory::STATUS_DELETED:
					$hubCategory->deleteCategory();
					hubCounter::incrementDeleted($hubCategory->getSrHubOriginId());
					hubOriginNotification::addMessage($hubCategory->getSrHubOriginId(), $hubCategory->getTitle(), 'Category deleted:');
					break;
				case hubSyncHistory::STATUS_ALREADY_DELETED:
					hubCounter::incrementIgnored($hubCategory->getSrHubOriginId());
					hubOriginNotification::addMessage($hubCategory->getSrHubOriginId(), $hubCategory->getTitle(), 'Category ignored:');
					break;
			}
			$hubCategory->getHistoryObject()->updatePickupDate();
			$hubOrigin = hubOrigin::getClassnameForOriginId($hubCategory->getSrHubOriginId());
			$hubOrigin::afterObjectModification($hubCategory);
			if ($hubCategory->getExtId() !== 0 AND $hubCategory->getExtId() !== NULL AND $hubCategory->getExtId() !== ''
			) {
				self::buildForParentId($hubCategory->getExtId());
			}
		}
	}


	/**
	 * @return int
	 */
	protected function lookupRefIdByTitle() {
		return $this->lookupRefIdByField('title', $this->getTitle());
	}


	/**
	 * @param $fieldname
	 * @param $value
	 *
	 * @return int
	 */
	protected function lookupRefIdByField($fieldname, $value) {
		global $tree;
		/**
		 * @var $tree
		 */
		$node = $this->getNode();
		foreach ($tree->getChildsByType($node, 'cat') as $cat) {
			if ($cat[$fieldname] == $value) {
				return $cat['ref_id'];
			}
		}

		return 0;
	}


	protected function updateCategory() {
		$update = false;
		$this->ilias_object = ilObjectFactory::getInstanceByRefId($this->getHistoryObject()->getIliasId());
		if ($this->object_properties->getUpdateTitle()) {
			$this->ilias_object->setTitle($this->getTitle());
			$update = true;
		}
		if ($this->object_properties->getUpdateDescription()) {
			$this->ilias_object->setDescription($this->getDescription());
			$update = true;
		}
		if ($this->object_properties->getUpdateIcon()) {
			$this->updateIcon();
		}
		if ($this->object_properties->getMove()) {
			global $tree, $rbacadmin;
			$ref_id = $this->ilias_object->getRefId();
			$old_parent = $tree->getParentId($ref_id);
			$tree->moveTree($ref_id, $this->getNode());
			$rbacadmin->adjustMovedObjectPermissions($ref_id, $old_parent);
			$update = true;
		}
		if ($update) {
			$this->ilias_object->setImportId($this->returnImportId());
			$this->ilias_object->update();
		}
	}


	protected function deleteCategory() {
		if ($this->object_properties->getDelete()) {
			$this->ilias_object = new ilObjCategory($this->getHistoryObject()->getIliasId());
			switch ($this->object_properties->getDelete()) {
				case self::DELETE_MODE_INACTIVE:
					$this->ilias_object->setTitle($this->getTitle() . ' '
						. $this->pl->txt('com_prop_mark_deleted_text'));
					if ($this->object_properties->getDeletedIcon()) {
						$icon = $this->object_properties->getIconPath('_deleted');
						if ($icon) {
							$this->ilias_object->saveIcons($icon, $icon, $icon);
						}
					}
					$hist = $this->getHistoryObject();
					$hist->setDeleted(true);
					$hist->setAlreadyDeleted(true);
					$hist->update();
					$this->ilias_object->update();
					break;
				case self::DELETE_MODE_DELETE:
					$this->ilias_object->delete();
					$this->getHistoryObject()->delete();
					break;
				case self::DELETE_MODE_ARCHIVE:
					if ($this->object_properties->getArchiveNode()) {
						global $tree, $rbacadmin;
						$ref_id = $this->ilias_object->getRefId();
						$old_parent = $tree->getParentId($ref_id);
						$tree->moveTree($ref_id, $this->object_properties->getArchiveNode());
						$rbacadmin->adjustMovedObjectPermissions($ref_id, $old_parent);
						$hist = $this->getHistoryObject();
						$hist->setAlreadyDeleted(true);
						$hist->update();
					}
					break;
			}
		}
	}


	private function createCategory() {
		$this->ilias_object = new ilObjCategory();
		$this->ilias_object->setTitle($this->getTitle());
		$this->ilias_object->setDescription($this->getDescription());
		$this->ilias_object->setImportId($this->returnImportId());
		$this->ilias_object->setOwner(6);
		$this->ilias_object->create();
		$this->ilias_object->createReference();
		$node = $this->getNode();
		$this->ilias_object->putInTree($node);
		$this->ilias_object->setPermissions($node);
		if ($this->object_properties->getCreateIcon()) {
			$this->updateIcon();
		}
		$history = $this->getHistoryObject();
		$history->setIliasId($this->ilias_object->getRefId());
		$history->setIliasIdType(self::ILIAS_ID_TYPE_REF_ID);
		$history->update();
	}


	/**
	 * @return int
	 */
	public function getNode() {
		/**
		 * @var $tree ilTree
		 */
		global $tree;
		$base_node_ilias = ($this->object_properties->getBaseNodeIlias() ? $this->object_properties->getBaseNodeIlias() : 1);
		if ($this->getParentIdType() == self::PARENT_ID_TYPE_EXTERNAL_ID) {
			if ($this->getExtId() == $this->object_properties->getBaseNodeExternal()) {
				return $base_node_ilias;
			} else {
				$parent_id = ilObject::_getAllReferences(ilObject::_lookupObjIdByImportId($this->returnParentImportId()));
				$keys = array_keys($parent_id);
				$node = $keys [0];
				if ($node) {
					return $node;
				} else {
					return $base_node_ilias;
				}
			}
		} elseif ($this->getParentIdType() == self::PARENT_ID_TYPE_REF_ID) {
			if (! $tree->isInTree($this->getParentId())) {
				return $base_node_ilias;
			} else {
				return $this->getParentId();
			}
		}
	}
}

?>