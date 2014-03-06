<?php

/**
 * Class hubShortlink
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class hubShortlink {

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
	 * @param $ext_id
	 */
	public static function redirect($ext_id) {
		new self($ext_id);
	}


	/**
	 * @param $ext_id
	 */
	private function __construct($ext_id) {
		$this->initILIAS();
		$this->setExtId($ext_id);
		if ($this->checkShortlink()) {
			$this->redirectToObject();
		} else {
			$this->redirectToBase();
		}
	}


	private function redirectToObject() {
		ilUtil::redirect($this->getLink());
	}


	private function redirectToBase() {
		ilUtil::redirect('/login.php');
	}


	/**
	 * @return bool
	 */
	private function checkShortlink() {
		/**
		 * @var hubSyncHistory $hubSyncHistory
		 * @var hubOrigin      $hubOrigin
		 * @var hubCourse      $class
		 */
		foreach (hub::getObjectTypeClassNames() as $class) {
			if ($class::where(array( 'shortlink' => $this->getExtId() ))->hasSets()) {
				$ext_id = $class::where(array( 'shortlink' => $this->getExtId() ))->first()->getExtId();
				break;
			}
		}
		if (! $ext_id) {
			ilUtil::sendFailure('No Object for this Shortlink found.', true);
		}
		$hubSyncHistory = hubSyncHistory::find($ext_id);
		if ($hubSyncHistory->getSrHubOriginId()) {
			$this->setSrHubOriginId($hubSyncHistory->getSrHubOriginId());
			$hubOriginObjectProperties = hubOriginObjectProperties::getInstance($hubSyncHistory->getSrHubOriginId());
			if ($hubOriginObjectProperties->getShortlink() AND $hubSyncHistory->getIliasId()) {
				$hubOrigin = hubOrigin::find($this->getSrHubOriginId());
				switch ($hubOrigin->getUsageType()) {
					case hub::OBJECTTYPE_COURSE;
					case hub::OBJECTTYPE_CATEGORY;
						global $ilObjDataCache;
						$a_ref_id = $hubSyncHistory->getIliasId();
						$a_type = $ilObjDataCache->lookupType($ilObjDataCache->lookupObjId($a_ref_id));
						$server = ($_SERVER['HTTPS'] == 'on' ? 'http://' : 'http://') . $_SERVER['SERVER_NAME'];
						$link = $server . '/goto_' . urlencode(CLIENT_ID) . '_' . $a_type . '_' . $a_ref_id . '.html';
						$this->setLink($link);

						return true;
				}
			} else {
				// ilUtil::sendFailure('No Object for this Shortlink found.', true);
			}
		} else {
			// ilUtil::sendFailure('No Object for this Shortlink found.', true);
		}

		return false;
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


	//
	// Helpers
	//
	private function initILIAS() {
		switch (trim(shell_exec('hostname'))) {
			case 'ilias-webt1':
			case 'ilias-webn1':
			case 'ilias-webn2':
			case 'ilias-webn3':
				$path = '/var/www/ilias-4.3.x';
				break;
			default:
				$path = substr(__FILE__, 0, strpos(__FILE__, 'Customizing'));
				break;
		}
		chdir($path);
		require_once('./include/inc.ilias_version.php');
		require_once('./Services/Component/classes/class.ilComponent.php');
		if (ilComponent::isVersionGreaterString(ILIAS_VERSION_NUMERIC, '4.2.999')) {
			require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Services/class.hubContext.php');
			hubContext::init(hubContext::CONTEXT_HUB);
			require_once('./include/inc.header.php');
		} else {
			$_GET['baseClass'] = 'ilStartUpGUI';
			require_once('include/inc.get_pear.php');
			require_once('include/inc.header.php');
		}
		self::includes();
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