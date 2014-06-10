<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/class.hubRepositoryObject.php');
require_once('./Modules/Category/classes/class.ilObjCategory.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Category/class.hubCategoryFields.php');
require_once('./Services/Container/classes/class.ilContainerSorting.php');
require_once('./Services/Repository/classes/class.ilRepUtil.php');

/**
 * Class hubCategory
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.1.03
 *
 */
class hubCategory extends hubRepositoryObject {

	const ORDER_TYPE_TITLE = 0;
	const ORDER_TYPE_MANUALLY = 1;
	/**
	 * @var bool
	 */
	protected $ar_safe_read = false;
	/**
	 * @var int
	 *
	 * @db_has_field        true
	 * @db_fieldtype        integer
	 * @db_length           1
	 */
	protected $show_infopage = true;
	/**
	 * @var int
	 *
	 * @db_has_field        true
	 * @db_fieldtype        integer
	 * @db_length           1
	 */
	protected $show_news = true;
	/**
	 * @var int
	 *
	 * @db_has_field        true
	 * @db_fieldtype        integer
	 * @db_length           8
	 */
	protected $position = 0;
	/**
	 * @var int
	 *
	 * @db_has_field        true
	 * @db_fieldtype        integer
	 * @db_length           1
	 */
	protected $order_type = self::ORDER_TYPE_TITLE;
	/**
	 * @var int
	 */
	public static $id_type = self::ILIAS_ID_TYPE_REF_ID;
	/**
	 * @var ilObjCategory
	 */
	public $ilias_object;


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
			self::buildForParentId($hubOrigin->props()->get(hubCategoryFields::BASE_NODE_EXTERNAL));
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

		foreach (self::where(array( 'parent_id' => $parent_id ))->get() as $hubCategory) {
			if (! hubSyncHistory::isLoaded($hubCategory->getSrHubOriginId())) {
				continue;
			}
			$existing_ref_id = 0;
			switch ($hubCategory->props()->get(hubCategoryFields::SYNCFIELD)) {
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
			switch ($hubCategory->getHistoryObject()->getStatus()) {
				case hubSyncHistory::STATUS_NEW:
					if (! hubSyncCron::getDryRun()) {
						$hubCategory->createCategory();
					}
					hubCounter::incrementCreated($hubCategory->getSrHubOriginId());
					hubOriginNotification::addMessage($hubCategory->getSrHubOriginId(), $hubCategory->getTitle(), 'Category created:');
					break;
				case hubSyncHistory::STATUS_UPDATED:
					if (! hubSyncCron::getDryRun()) {
						$hubCategory->updateCategory();
					}
					hubCounter::incrementUpdated($hubCategory->getSrHubOriginId());
					break;
				case hubSyncHistory::STATUS_DELETED:
					if (! hubSyncCron::getDryRun()) {
						$hubCategory->deleteCategory();
					}
					hubCounter::incrementDeleted($hubCategory->getSrHubOriginId());
					hubOriginNotification::addMessage($hubCategory->getSrHubOriginId(), $hubCategory->getTitle(), 'Category deleted:');
					break;
				case hubSyncHistory::STATUS_ALREADY_DELETED:
					hubCounter::incrementIgnored($hubCategory->getSrHubOriginId());
					hubOriginNotification::addMessage($hubCategory->getSrHubOriginId(), $hubCategory->getTitle(), 'Category ignored:');
					break;
				case hubSyncHistory::STATUS_NEWLY_DELIVERED:
					hubCounter::incrementNewlyDelivered($hubCategory->getSrHubOriginId());
					hubOriginNotification::addMessage($hubCategory->getSrHubOriginId(), $hubCategory->getTitle(), 'Category newly delivered:');
					if (! hubSyncCron::getDryRun()) {
						$hubCategory->updateCategory();
					}
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


	/**
	 * FSX TODO
	 */
	protected function updateSorting() {
		/**
		 * @var $sorting ilContainerSorting
		 */
		$sorting = ilContainerSorting::_getInstance($this->ilias_object->getId());
		$sorting->sortItems(array());
	}


	protected function updateCategory() {
		$update = false;
		$this->moveObject();
		if ($this->props()->get(hubCategoryFields::UPDATE_TITLE)) {
			$this->initObject();
			$this->ilias_object->setTitle($this->getTitle());
			$update = true;
		}
		if ($this->props()->get(hubCategoryFields::UPDATE_DESCRIPTION)) {
			$this->initObject();
			$this->ilias_object->setDescription($this->getDescription());
			$update = true;
		}
		if ($this->props()->get(hubCategoryFields::UPDATE_ICON)) {
			$this->initObject();
			$this->updateIcon();
		}
		if ($update) {
			$this->ilias_object->setOrderType($this->getOrderType());
			$this->ilias_object->setImportId($this->returnImportId());
			$this->ilias_object->update();
		}
		$history = $this->getHistoryObject();
		$history->setAlreadyDeleted(false);
		$history->setDeleted(false);
	}


	protected function deleteCategory() {
		if ($this->props()->get(hubCategoryFields::DELETE)) {
			$hist = $this->getHistoryObject();
			$this->ilias_object = new ilObjCategory($this->getHistoryObject()->getIliasId());
			switch ($this->props()->get(hubCategoryFields::DELETE)) {
				case self::DELETE_MODE_INACTIVE:
					$ilHubPlugin = new ilHubPlugin();
					$this->ilias_object->setTitle($this->getTitle() . ' ' . $ilHubPlugin->txt('com_prop_mark_deleted_text'));
					if ($this->props()->get(hubCategoryFields::DELETED_ICON)) {
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
				case self::DELETE_MODE_ARCHIVE:
					if ($this->props()->get(hubCategoryFields::ARCHIVE_NODE)) {
						global $tree, $rbacadmin;
						$ref_id = $this->ilias_object->getRefId();
						$old_parent = $tree->getParentId($ref_id);
						$tree->moveTree($ref_id, $this->props()->get(hubCategoryFields::ARCHIVE_NODE));
						$rbacadmin->adjustMovedObjectPermissions($ref_id, $old_parent);
					}
					break;
			}
			$hist->setDeleted(true);
			$hist->setAlreadyDeleted(true);
			$hist->update();
		}
	}


	private function createCategory() {
		$this->ilias_object = new ilObjCategory();
		$this->ilias_object->setOrderType($this->getOrderType());
		$this->ilias_object->setTitle($this->getTitle());
		$this->ilias_object->setDescription($this->getDescription());
		$this->ilias_object->setImportId($this->returnImportId());
		$this->ilias_object->setOwner(6);
		$this->ilias_object->create();
		$this->ilias_object->createReference();
		$node = $this->getNode();
		$this->ilias_object->putInTree($node);
		$this->ilias_object->setPermissions($node);
		if ($this->props()->get(hubCategoryFields::CREATE_ICON)) {
			$this->updateIcon();
		}
		$history = $this->getHistoryObject();
		$history->setIliasId($this->ilias_object->getRefId());
		$history->setIliasIdType(self::$id_type);
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
		$base_node_prop = $this->props()->get(hubCategoryFields::BASE_NODE_ILIAS);
		$base_node_ilias = ($base_node_prop ? $base_node_prop : 1);
		$base_node_external = $this->props()->get(hubCategoryFields::BASE_NODE_EXTERNAL);

		if ($this->getParentIdType() == self::PARENT_ID_TYPE_EXTERNAL_ID) {
			if ($this->getExtId() == $base_node_external) {
				return (int)$base_node_ilias;
			} else {
				$parent_id = ilObject::_getAllReferences(ilObject::_lookupObjIdByImportId($this->returnParentImportId()));
				$node = (int)array_shift(array_keys($parent_id));
				if ($tree->isInTree($node)) {
					return (int)$node;
				} else {
					return (int)$base_node_ilias;
				}
			}
		} elseif ($this->getParentIdType() == self::PARENT_ID_TYPE_REF_ID) {
			if (! $tree->isInTree($this->getParentId())) {
				return $base_node_ilias;
			} else {
				return (int)$this->getParentId();
			}
		}
	}


	protected function moveObject() {
		if ($this->props()->get(hubCategoryFields::MOVE)) {
			global $tree, $rbacadmin;
			$this->initObject();
			$dependecesNode = $this->getNode();
			if ($tree->isDeleted($this->ilias_object->getRefId())) {
				hubLog::getInstance()->write('Category restored: ' . $this->getExtId());
				$ilRepUtil = new ilRepUtil();
				$ilRepUtil->restoreObjects($dependecesNode, array( $this->ilias_object->getRefId() ));
			}
			try {
				$ref_id = $this->ilias_object->getRefId();
				$old_parent = $tree->getParentId($ref_id);
				if ($old_parent != $dependecesNode) {
					$str = 'Moving Category ' . $this->getExtId() . ' from ' . $old_parent . ' to ' . $dependecesNode;
					$tree->moveTree($ref_id, $dependecesNode);
					$rbacadmin->adjustMovedObjectPermissions($ref_id, $old_parent);
					hubLog::getInstance()->write($str);
					hubOriginNotification::addMessage($this->getSrHubOriginId(), $str, 'Moved:');
				}
			} catch (InvalidArgumentException $e) {
				$str1 = 'Error moving Category in Tree: ' . $this->getExtId();

				hubLog::getInstance()->write($str1);
			}
		}
	}


	protected function initObject() {
		if (! isset($this->ilias_object)) {
			$this->ilias_object = ilObjectFactory::getInstanceByRefId($this->getHistoryObject()->getIliasId());
		}
	}


	/**
	 * @param int $show_infopage
	 */
	public function setShowInfopage($show_infopage) {
		$this->show_infopage = $show_infopage;
	}


	/**
	 * @return int
	 */
	public function getShowInfopage() {
		return $this->show_infopage;
	}


	/**
	 * @param int $show_news
	 */
	public function setShowNews($show_news) {
		$this->show_news = $show_news;
	}


	/**
	 * @return int
	 */
	public function getShowNews() {
		return $this->show_news;
	}


	/**
	 * @param int $position
	 */
	public function setPosition($position) {
		$this->position = $position;
	}


	/**
	 * @return int
	 */
	public function getPosition() {
		return $this->position;
	}


	/**
	 * @param int $order_type
	 */
	public function setOrderType($order_type) {
		$this->order_type = $order_type;
	}


	/**
	 * @return int
	 */
	public function getOrderType() {
		return $this->order_type;
	}
}

?>