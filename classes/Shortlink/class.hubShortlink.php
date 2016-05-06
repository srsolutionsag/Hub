<?php

/**
 * Class hubShortlink
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @author  Timon Amstutz <timon.amstutz@ilub.unibe.ch>
 *
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
	 * @var ilObjCourse
	 */
	protected $il_object = null;
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
	 * @var hubObject
	 */
	protected $hub_object;
	/**
	 * @var string
	 */
	protected $server = "";


	/**
	 * @param $ext_id
	 */
	public static function redirect($ext_id) {
		new self($ext_id);
	}


	/**
	 * @param $ext_id
	 */
	protected function __construct($ext_id, $init_ilias = true) {
		require_once(dirname(__FILE__) . '/../class.hub.php');
		if ($init_ilias) {
			hub::initILIAS(hub::CONTEXT_WEB);
		}
		self::includes();
		$this->setExtId($ext_id);
		$this->setServer();
		$this->doRedirect();
	}


	protected function doRedirect() {
		/**
		 * @var ilObjUser $ilUser
		 */
		global $ilUser;

		if ($this->checkRedirectBase()) {
			$this->redirectToBase();
		}

		if ($this->getOriginObjectProperties()->get(hubOriginObjectPropertiesFields::F_FORCE_LOGIN) && $ilUser->getLogin() == "anonymous") {
			$this->redirectToLogin();
		}

		if ($this->checkRedirectParent()) {
			$this->redirectToParent();
		} else {
			$this->redirectToObject();
		}
	}


	/**
	 * @return bool
	 */
	public function checkRedirectBase() {
		if (!$this->initHubObject() || !$this->getSyncHistory() || !$this->getSyncHistory()->getSrHubOriginId()) {
			ilUtil::sendInfo(hubConfig::get(hubConfig::F_MSG_SHORTLINK_NOT_FOUND), true);

			return true;
		}

		if (!$this->getOriginObjectProperties()->get(hubOriginObjectPropertiesFields::F_SHORTLINK)) {
			ilUtil::sendInfo(hubConfig::get(hubConfig::F_MSG_SHORTLINK_NOT_ACTIVE), true);

			return true;
		}

		if (!$this->getSyncHistory()->getIliasId()) {
			ilUtil::sendInfo(hubConfig::get(hubConfig::F_MSG_SHORTLINK_NO_ILIAS_ID), true);

			return true;
		}

		return false;
	}


	/**
	 * @return bool
	 */
	public function checkRedirectParent() {
		/**
		 * @var ilObjUser $ilUser
		 */
		global $ilUser;

		$this->initObjectData($this->getSyncHistory()->getIliasId());
		if ($this->getOriginObjectProperties()->get(hubOriginObjectPropertiesFields::F_SL_CHECK_ONLINE)) {
			if ($this->getIlObject()->getOfflineStatus() && !$this->getIlObject()->getMemberObject()->isAdmin($ilUser->getId())) {
				ilUtil::sendInfo($this->getOriginObjectProperties()->get(hubOriginObjectPropertiesFields::F_MSG_NOT_ONLINE), true);

				return true;
			}
		}

		return false;
	}


	/**
	 * @return bool
	 */
	protected function initHubObject() {
		foreach (hub::getObjectTypeClassNames() as $class) {
			if ($class::where(array( 'shortlink' => $this->getExtId() ))->debug()->hasSets()) {
				$this->setHubObject($class::where(array( 'shortlink' => $this->getExtId() ))->first());
				break;
			}
		}
		if (!$this->getHubObject()) {
			return false;
		}

		return true;
	}


	/**
	 * @return hubSyncHistory
	 */
	protected function getSyncHistory() {
		return hubSyncHistory::getInstance($this->getHubObject());
	}


	/**
	 * @return hubOriginObjectProperties
	 */
	protected function getOriginObjectProperties() {
		return hubOriginObjectProperties::getInstance($this->getHubObject()->getSrHubOriginId());
	}


	/**
	 * @param string $server
	 */
	public function setServer() {
		$this->server = ($_SERVER['HTTPS'] == 'on' ? 'http://' : 'http://') . $_SERVER['SERVER_NAME'];
	}


	/**
	 * @return string
	 */
	public function getServer() {
		return $this->server;
	}


	protected function redirectToObject() {
		$link = $this->getServer() . '/goto_' . urlencode(CLIENT_ID) . '_' . $this->getType() . '_' . $this->getRefId() . '.html';
		ilUtil::redirect($link);
	}


	protected function redirectToParent() {
		$link = $this->getServer() . '/goto_' . urlencode(CLIENT_ID) . '_' . $this->getParentType() . '_' . $this->getParentId() . '.html';
		ilUtil::redirect($link);
	}


	protected function redirectToBase() {
		/**
		 * @var ilObjUser $ilUser
		 */
		global $ilUser;

		if ($ilUser->getLogin() == "anonymous") {
			/**
			 * This is done to show the proper message for a user beeing redirected to base. ilUtil::sendInfo works session
			 * based, therefore a login is required to display the message properly.
			 */
			$this->redirectToLogin();
		}
		$link = $this->getServer() . '/index.php';
		ilUtil::redirect($link);
	}


	protected function redirectToLogin() {
		$link = $this->getServer() . '/login.php?target=uihk_hub_' . $this->getExtId();
		ilUtil::redirect($link);
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


	/**
	 * @param \hubObject $hub_object
	 */
	public function setHubObject($hub_object) {
		$this->hub_object = $hub_object;
	}


	/**
	 * @return \hubObject
	 */
	public function getHubObject() {
		return $this->hub_object;
	}


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


	protected static function includes() {
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