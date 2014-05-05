<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Log/class.hubLog.php');

/**
 * Class hubOriginNotification
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.1.02
 */
class hubOriginNotification {

	const COMMON = 'common';
	/**
	 * @var array
	 */
	protected static $messages = array();


	/**
	 * @param      $sr_hub_origin_id
	 * @param      $text
	 * @param null $header
	 */
	public static function addMessage($sr_hub_origin_id, $text, $header = NULL) {
		if (! $header) {
			$header = self::COMMON;
		}
		self::$messages[$sr_hub_origin_id][$header][] = $text;
	}


	/**
	 * @param hubOrigin $origin
	 */
	public static function send(hubOrigin $origin) {
		$sr_hub_origin_id = $origin->getId();
		$origin->loadConf();
		$mail = $origin->conf()->getSummaryEmail();
		if ($mail AND $origin->getActive()) {
			$str = self::buildSubject($origin);
			mail($mail, $str, self::buildMessage($sr_hub_origin_id));
		}
		//self::$messages[$sr_hub_origin_id] = array();
	}


	/**
	 * @param $sr_hub_origin_id
	 *
	 * @return string
	 */
	protected function buildMessage($sr_hub_origin_id) {
		$message = 'Common Messages:' . PHP_EOL;
		$message .= implode(PHP_EOL, self::$messages[$sr_hub_origin_id][self::COMMON]);
		$message .= PHP_EOL . PHP_EOL . PHP_EOL;
		foreach (self::$messages[$sr_hub_origin_id] as $header => $messages) {
			if ($header != self::COMMON) {
				$message .= $header . PHP_EOL . PHP_EOL;
				$message .= implode(PHP_EOL, $messages);
				$message .= PHP_EOL . PHP_EOL . PHP_EOL;
			}
		}

		return $message;
	}


	/**
	 * @return string
	 */
	public static function getSummaryString() {
		$string = '';
		foreach (array_keys(self::$messages) as $sr_hub_origin_id) {
			$string .= self::buildSubject(hubOrigin::find($sr_hub_origin_id)) . PHP_EOL;
			$string .= self::buildMessage($sr_hub_origin_id);
		}

		return nl2br($string);
	}


	/**
	 * @param hubOrigin $origin
	 *
	 * @return string
	 */
	protected static function buildSubject(hubOrigin $origin) {
		$str = 'hubSyncSummary ' . $origin->getTitle() . ' (' . date('d.m.Y - H:i:s') . ')';

		return $str;
	}
}

?>