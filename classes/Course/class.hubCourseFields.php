<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/OriginProperties/class.hubOriginObjectPropertiesFields.php');

/**
 * Class hubCourseFields
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.1.04
 */
class hubCourseFields extends hubOriginObjectPropertiesFields {

	protected static $notification_placeholder = array(
		'title',
		'description',
		'responsible',
		'notification_email',
		'shortlink',
	);
	const F_NODE_NOPARENT = 'node_noparent';
	const F_ACTIVATE = 'activate';
	const F_CREATE_ICON = 'create_icon';
	const F_MOVE = 'move';
	const F_UPDATE_TITLE = 'update_title';
	const F_UPDATE_DESCRIPTION = 'update_description';
	const F_UPDATE_ICON = 'update_icon';
	const F_REACTIVATE = 'reactivate';
	const F_DELETE = 'delete';
	const F_DELETED_ICON = 'deleted_icon';
	const F_SEND_NOTIFICATION = 'send_notification';
	const F_NOT_BODY = 'notification_body';
	const F_NOT_SUBJECT = 'notification_subject';


	/**
	 * @return string
	 */
	public static function getPlaceHolderStrings() {
		$return = '[';
		$return .= implode('], [', self::$notification_placeholder);
		$return .= ']';

		return strtoupper($return);
	}


	/**
	 * @param hubCourse $hubCourse
	 *
	 * @return string
	 */
	public static function getReplacedText(hubCourse $hubCourse) {
		$body = $hubCourse->props()->get(self::F_NOT_BODY);
		$hubCourse_array = $hubCourse->__asArray();
		foreach (self::$notification_placeholder as $ph) {
			$body = str_ireplace('[' . $ph . ']', $hubCourse_array[$ph], $body);
		}

		return $body;
	}
}

?>
