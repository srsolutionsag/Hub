<?php

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */
require_once('./Services/Context/classes/class.ilContextBase.php');

/**
 * Service context for hub
 *
 * @author  Jörg Lützenkirchen <luetzenkirchen@leifos.com>
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.1.03
 *
 * @ingroup ServicesContext
 */
class hubContextHub extends ilContextBase {

	/**
	 * @return bool
	 */
	public static function supportsRedirects() {
		return false;
	}


	/**
	 * @return bool
	 */
	public static function hasUser() {
		return false;
	}


	/**
	 * @return bool
	 */
	public static function usesHTTP() {
		return true;
	}


	/**
	 * @return bool
	 */
	public static function hasHTML() {
		return false;
	}


	/**
	 * @return bool
	 */
	public static function usesTemplate() {
		return false;
	}


	/**
	 * @return bool
	 */
	public static function initClient() {
		return true;
	}


	/**
	 * @return bool
	 */
	public static function doAuthentication() {
		return false;
	}
}

?>