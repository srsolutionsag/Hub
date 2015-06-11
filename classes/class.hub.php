<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Configuration/class.hubConfig.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Log/class.hubLog.php');

/**
 * Class hub
 *
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.1.04
 *
 * @revision $r$
 */
class hub {

	/**
	 * @var array
	 */
	protected static $object_types = array(
		self::OBJECTTYPE_USER => 'hubUser',
		self::OBJECTTYPE_MEMBERSHIP => 'hubMembership',
		self::OBJECTTYPE_COURSE => 'hubCourse',
		self::OBJECTTYPE_CATEGORY => 'hubCategory',
	);
	const OBJECTTYPE_USER = 1;
	const OBJECTTYPE_MEMBERSHIP = 2;
	const OBJECTTYPE_COURSE = 3;
	const OBJECTTYPE_CATEGORY = 4;
	/**
	 * @var array
	 */
	protected static $support_icons = array( self::OBJECTTYPE_COURSE, self::OBJECTTYPE_CATEGORY );


	/**
	 * @param $type_id
	 *
	 * @return bool
	 */
	public static function supportsIcons($type_id) {
		return in_array($type_id, self::$support_icons);
	}


	/**
	 * @return array
	 */
	public static function getObjectTypeClassNames() {
		return self::$object_types;
	}


	/**
	 * @param $object_type_id
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public static function getObjectClassname($object_type_id) {
		if (! in_array($object_type_id, array_keys(self::$object_types)) AND $object_type_id != 0) {
			throw new Exception('$object_type_id ' . $object_type_id . ' does not exists');
		}

		return self::$object_types[$object_type_id];
	}


	public static function includeOriginTypes() {
	}


	/**
	 * @return string
	 */
	public static function getPath() {
		$real_path = self::getRootPath() . 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/';
		$real_path = rtrim($real_path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

		return $real_path;
	}


	/**
	 * @return string
	 */
	public static function getRootPath() {
		$override_file = dirname(__FILE__) . '/Configuration/root';
		if (is_file($override_file)) {
			$path = file_get_contents($override_file);
			$path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

			return $path;
		}

		$path = realpath(dirname(__FILE__) . '/../../../../../../../..');
		$path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

		return $path;
	}


	/**
	 * @param string $message
	 * @param bool   $keep
	 */
	public static function sendFailure($message, $keep = false) {
		if (self::isCli()) {
			echo $message;
		} else {
			ilUtil::sendFailure($message, $keep);
		}
	}


	/**
	 * @description ATTENTION: Does not yet work...
	 */
	public static function initErrorCallback() {
		//		ini_set('display_errors', false);
		register_shutdown_function('hub::fatalErrorHandler');
		register_shutdown_function('hub::fatalErrorHandler');
	}


	/**
	 * @throws hubOriginException
	 */
	public static function fatalErrorHandler() {
		register_shutdown_function('hub::fatalErrorHandler');
		$e = (object)error_get_last();
		if (($e->type === E_ERROR) || ($e->type === E_USER_ERROR)) {
			// echo $error_message = 'hub FatalError:' . $e->message . ' in ' . $e->file . ' (Line ' . $e->line . ')';
			// hubLog::getInstance()->write($error_message);

			throw new hubOriginException(hubOriginException::OTHER, new hubOrigin(), true);
		}
		exit;
	}


	public static function restoreErrorCallback() {
		restore_error_handler();
	}


	/**
	 * @return bool
	 */
	public static function isCli() {
		return (php_sapi_name() === 'cli');
	}


	const CONTEXT_CRON = 1;
	const CONTEXT_WEB = 2;
	const CONTEXT_CRON_H = 3;


	/**
	 * @param int $context
	 */
	public static function initILIAS($context = self::CONTEXT_CRON) {
		chdir(self::getRootPath());
		require_once('./Services/Context/classes/class.ilContext.php');
		require_once('./Services/Authentication/classes/class.ilAuthFactory.php');
		switch ($context) {
			case self::CONTEXT_CRON:
				$il_context = ilContext::CONTEXT_CRON;
				$il_context_auth = ilAuthFactory::CONTEXT_CRON;
				$_COOKIE['ilClientId'] = $_SERVER['argv'][3];
				$_POST['username'] = $_SERVER['argv'][1];
				$_POST['password'] = $_SERVER['argv'][2];
				break;
			case self::CONTEXT_WEB:
				$il_context = ilContext::CONTEXT_WEB;
				$il_context_auth = ilAuthFactory::CONTEXT_WEB;
				$_POST['username'] = 'anonymous';
				$_POST['password'] = 'anonymous';
				break;
			case self::CONTEXT_CRON_H:
				$il_context = ilContext::CONTEXT_ICAL;
				$il_context_auth = ilAuthFactory::CONTEXT_CALENDAR;
				$_COOKIE['ilClientId'] = $_SERVER['argv'][3];
				$_POST['username'] = $_SERVER['argv'][1];
				$_POST['password'] = $_SERVER['argv'][2];
		}

		if (hubConfig::is44() OR hubConfig::is45()) {
			ilContext::init($il_context);
			ilAuthFactory::setContext($il_context_auth);
		} else {
			ilAuthFactory::setContext($il_context_auth);
		}
		require_once('./include/inc.header.php');
	}

    const ILIAS_44 = 44;
    const ILIAS_50 = 50;

    /**
     * @return int
     */
    public static function getILIASVersion() {
        require_once './Services/Component/classes/class.ilComponent.php';
        if (ilComponent::isVersionGreaterString(ILIAS_VERSION_NUMERIC, '4.9.999')) {
            return self::ILIAS_50;
        }
        if (ilComponent::isVersionGreaterString(ILIAS_VERSION_NUMERIC, '4.3.999')) {
            return self::ILIAS_44;
        }

        return 0;
    }

    /**
     * @return bool
     */
    public static function is50() {
        return self::getILIASVersion() >= self::ILIAS_50;
    }

    /**
     * @throws ilPluginException
     */
    public static function loadActiveRecord() {
        require_once(hub::pathToActiveRecord().'/class.ActiveRecord.php');
        if (is_file('./Customizing/global/plugins/Libraries/ActiveRecord/class.ActiveRecord.php')) {
            require_once('./Customizing/global/plugins/Libraries/ActiveRecord/class.ActiveRecord.php');
        } elseif (hub::is50()) {
            require_once('./Services/ActiveRecord/class.ActiveRecord.php');
        } else {
            throw new ilPluginException('Please install ActiveRecord');
        }
    }

    public static function pathToActiveRecord(){
        if (is_file('./Customizing/global/plugins/Libraries/ActiveRecord/class.ActiveRecord.php')) {
            return './Customizing/global/plugins/Libraries/ActiveRecord';
        } elseif (hub::is50()) {
            return './Services/ActiveRecord';
        } else {
            throw new ilPluginException('Please install ActiveRecord');
        }
    }


}