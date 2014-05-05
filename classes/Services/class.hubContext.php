<?php
require_once('./Services/Context/classes/class.ilContext.php');
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Service context (factory) class
 *
 * @author  Jörg Lützenkirchen <luetzenkirchen@leifos.com>
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.1.02
 *
 * @ingroup ServicesContext
 */
class hubContext extends ilContext {

	const CONTEXT_HUB = 12;


	/**
	 * @param int $a_type
	 *
	 * @return bool
	 */
	public static function init($a_type) {
		$class_name = self::getClassForType($a_type);
		if ($class_name) {
			include_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Services/class.' . $class_name . '.php');
			self::$class_name = $class_name;
			self::$type = $a_type;

			return true;
		}

		return false;
	}


	/**
	 * @param int $a_type
	 *
	 * @return string
	 */
	protected function getClassForType($a_type) {
		switch ($a_type) {
			case self::CONTEXT_HUB:
				return 'hubContextHub';
		}
	}
}

?>