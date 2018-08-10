<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Log/class.hubLog.php');

/**
 * Class hubOriginException
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.1.04
 */
class hubOriginException extends Exception {

	const OTHER = - 1;
	const CONNECTION_FAILED = 1;
	const PARSE_DATA_FAILED = 2;
	const NO_DATASETS = 3;
	const CHECKSUM_MISMATCH = 4;
	const BUILD_ENTRIES_FAILED = 5;
	const BUILD_ILIAS_OBJECTS_FAILED = 6;
	const TOO_MANY_LOST_DATASETS = 7;
	const INIT_STATUS_FAILED = 8;
	const PHP_FATAL_ERROR = 9;
	/**
	 * @var array
	 */
	protected static $msgs = array(
		self::OTHER => '',
		self::CONNECTION_FAILED => 'Es konnte keine Verbindung zum Fremdsystem hergestellt werden oder die Datei konnte nicht gelesen werden.',
		self::PARSE_DATA_FAILED => 'Daten konnten nicht erfolgreich gelesen werden.',
		self::NO_DATASETS => 'Daten vom Fremdsystem konnten nicht abgerufen werden.',
		self::CHECKSUM_MISMATCH => 'Die Anzahl Datensätze stimmt nicht mit der Checksumme überein.',
		self::BUILD_ENTRIES_FAILED => 'Die hubObjects konnten nicht erfolgreich erstellt werden.',
		self::BUILD_ILIAS_OBJECTS_FAILED => 'Die ILIAS-Objecte konnten nicht erfolgreich modifiziert werden.',
		self::TOO_MANY_LOST_DATASETS => 'Die Anzahl gelieferter Datensätze beträgt weniger als der vorgegebene Prozentsatz der bestehenden Datensätze',
		self::INIT_STATUS_FAILED => 'Der Status der hubObjekte konnte nicht erfolgreich gelesen werden.',
		self::PHP_FATAL_ERROR => 'PHP FATAL ERROR',
	);


	/**
	 * @param int       $code
	 * @param hubOrigin $origin
	 * @param bool      $send_mail
	 * @param null      $optional_message
	 */
	public function __construct($code = self::OTHER, hubOrigin $origin, $send_mail = false, $optional_message = NULL) {
		$message = 'hubOrigin-Class: ' . $origin->getClassName() . ' (sr_hub_origin_id ' . $origin->getId() . '): ';
		$message .= PHP_EOL;
		$message .= 'Error: ' . self::$msgs[$code];
		$message .= PHP_EOL;
		if ($optional_message) {
			$message .= PHP_EOL;
			$message .= $optional_message;
			$message .= PHP_EOL;
			$message .= PHP_EOL;
		}
		if ($send_mail) {
			$origin->loadConf();
			mail($origin->conf()->getNotificationEmail(), 'hubSyncFailure', $message);
		}
		hubLog::getInstance()->write('FAILURE: ' . $message, hubLog::L_PROD);
		$message = nl2br($message);
		parent::__construct($message, $code);
	}


	/**
	 * @return string
	 */
	public function __toString() {
		return __CLASS__ . ": [Error-Code {$this->code}]: {$this->message}\n";
	}
}
