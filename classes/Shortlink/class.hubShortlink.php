<?php

/**
 * Class hubShortlink
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.1.04
 */
class hubShortlink {

	const REDIRECT_BASE = 1;
	const REDIRECT_OBJECT = 2;
	const REDIRECT_PARENT = 3;
	/**
	 * @var string
	 */
	protected $ext_id = '';
	/**
	 * @var int
	 */
	protected $sr_hub_origin_id;
	/**
	 * @var string
	 */
	protected $link = '';
	/**
	 * @var ilObjCourse
	 */
	protected $il_object = NULL;
	/**
	 * @var int
	 */
	protected $ref_id;
	/**
	 * @var int
	 */
	protected $obj_id;
	/**
	 * @var int
	 */
	protected $parent_id;
	/**
	 * @var string
	 */
	protected $type;
	/**
	 * @var string
	 */
	protected $parent_type;


	/**
	 * @param $ext_id
	 */
	public static function redirect($ext_id) {
		new self($ext_id);
	}


	/**
	 * @param $ext_id
	 */
	private function __construct($ext_id) {
		require_once(dirname(__FILE__) . '/../class.hub.php');
		hub::initILIAS(hub::CONTEXT_WEB);
		self::includes();
		$this->setExtId($ext_id);
		switch ($this->checkShortlink()) {
			case self::REDIRECT_BASE:
				$this->redirectToBase();
				break;
			case self::REDIRECT_PARENT:
				$this->redirectToParent();
				break;
			case self::REDIRECT_OBJECT:
				$this->redirectToObject();
				break;
		}
	}


	private function redirectToObject() {
		ilUtil::redirect($this->getLink());
	}


	private function redirectToParent() {
		ilUtil::redirect($this->getLink());
	}


	private function redirectToBase() {
		ilUtil::redirect('/login.php');
	}


	/**
	 * @return bool|int
	 */
	private function checkShortlink() {
		/**
		 * @var hubSyncHistory $hubSyncHistory
		 * @var hubOrigin      $hubOrigin
		 * @var hubCourse      $class
		 * @var hubCourse      $hubObject
		 * @var ilTree         $tree
		 */
		foreach (hub::getObjectTypeClassNames() as $class) {
			if ($class::where(array( 'shortlink' => $this->getExtId() ))->debug()->hasSets()) {
				$hubObject = $class::where(array( 'shortlink' => $this->getExtId() ))->first();
				break;
			}
		}
		if (! $hubObject) {
			ilUtil::sendFailure('No Object for this Shortlink found.', true);

			return self::REDIRECT_BASE;
		}
		$hubSyncHistory = hubSyncHistory::getInstance($hubObject);
		if ($hubSyncHistory->getSrHubOriginId()) {
			$this->setSrHubOriginId($hubSyncHistory->getSrHubOriginId());
			$hubOriginObjectProperties = hubOriginObjectProperties::getInstance($hubObject->getSrHubOriginId());
			if ($hubOriginObjectProperties->get(hubOriginObjectPropertiesFields::F_SHORTLINK) AND $hubSyncHistory->getIliasId()) {
				$hubOrigin = hubOrigin::find($this->getSrHubOriginId());
				switch ($hubOrigin->getUsageType()) {
					case hub::OBJECTTYPE_COURSE;
					case hub::OBJECTTYPE_CATEGORY;
						$server = ($_SERVER['HTTPS'] == 'on' ? 'http://' : 'http://') . $_SERVER['SERVER_NAME'];
						$this->initObjectData($hubSyncHistory->getIliasId());
						if ($hubOriginObjectProperties->get(hubOriginObjectPropertiesFields::F_SL_CHECK_ONLINE)) {
							if ($this->getIlObject()->getOfflineStatus()) {
								ilUtil::sendInfo($hubOriginObjectProperties->get(hubOriginObjectPropertiesFields::F_MSG_NOT_ONLINE), true);
								$link =
									$server . '/goto_' . urlencode(CLIENT_ID) . '_' . $this->getParentType() . '_' . $this->getParentId() . '.html';
								$this->setLink($link);

								return self::REDIRECT_PARENT;
							}
						}
						$link = $server . '/goto_' . urlencode(CLIENT_ID) . '_' . $this->getType() . '_' . $this->getRefId() . '.html';
						$this->setLink($link);

						return self::REDIRECT_OBJECT;
				}
			}
		}

		return self::REDIRECT_BASE;
	}


	/**
	 * @param $ref_id
	 */
	protected function initObjectData($ref_id) {
		global $tree;
		$this->setRefId($ref_id);
		$this->setParentId($tree->getParentId($ref_id));
		$this->setObjId(ilObject2::_lookupObjId($this->getRefId()));
		$this->setIlObject(ilObjectFactory::getInstanceByObjId($this->getObjId()));
		$this->setType($this->getIlObject()->getType());
		$this->setParentType(ilObject2::_lookupType($this->getParentId(), true));
	}


	//
	// Setter & Getter
	//
	/**
	 * @param mixed $ext_id
	 */
	public function setExtId($ext_id) {
		$this->ext_id = $ext_id;
	}


	/**
	 * @return mixed
	 */
	public function getExtId() {
		return $this->ext_id;
	}


	/**
	 * @param int $sh_hub_origin_id
	 */
	public function setSrHubOriginId($sh_hub_origin_id) {
		$this->sr_hub_origin_id = $sh_hub_origin_id;
	}


	/**
	 * @return int
	 */
	public function getSrHubOriginId() {
		return $this->sr_hub_origin_id;
	}


	/**
	 * @param string $link
	 */
	public function setLink($link) {
		$this->link = $link;
	}


	/**
	 * @return string
	 */
	public function getLink() {
		return $this->link;
	}


	/**
	 * @param \ilObjCourse $il_object
	 */
	public function setIlObject($il_object) {
		$this->il_object = $il_object;
	}


	/**
	 * @return \ilObjCourse
	 */
	public function getIlObject() {
		return $this->il_object;
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
	 * @param int $ref_id
	 */
	public function setRefId($ref_id) {
		$this->ref_id = $ref_id;
	}


	/**
	 * @return int
	 */
	public function getRefId() {
		return $this->ref_id;
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
	 * @param int $obj_id
	 */
	public function setObjId($obj_id) {
		$this->obj_id = $obj_id;
	}


	/**
	 * @return int
	 */
	public function getObjId() {
		return $this->obj_id;
	}


	/**
	 * @param string $parent_type
	 */
	public function setParentType($parent_type) {
		$this->parent_type = $parent_type;
	}


	/**
	 * @return string
	 */
	public function getParentType() {
		return $this->parent_type;
	}


	private static function includes() {
		require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Origin/class.hubOrigin.php');
		require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Sync/class.hubSyncHistory.php');
		require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/OriginProperties/class.hubOriginObjectProperties.php');
		require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Course/class.hubCourse.php');
		require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Membership/class.hubMembership.php');
		require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Category/class.hubCategory.php');
		require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/User/class.hubUser.php');
		@include_once('./Services/Link/classes/class.ilLink.php');
		@include_once('./classes/class.ilLink.php');
	}
}

?>