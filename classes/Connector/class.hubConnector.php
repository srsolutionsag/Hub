<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/class.hub.php');
hub::loadActiveRecord();
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Configuration/class.hubConfig.php');
require_once('./Services/Database/classes/class.ilDBWrapperFactory.php');

/**
 * Class hubConnector
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.1.04
 */
class hubConnector extends arConnectorDB {

	/**
	 * @var
	 */
	protected static $db_cache;


	/**
	 * @return ilDB
	 */

	public function returnDB() {
		/*if (parent::returnDB()->tableExists(hubConfig::returnDbTableName())) {
			if (hubConfig::get(hubConfig::F_DB)) {
				if (! isset(self::$db_cache)) {
					$database = ilDBWrapperFactory::getWrapper('mysql');
					$database->setDBHost(hubConfig::get(hubConfig::F_DB_HOST));
					$database->setDBName(hubConfig::get(hubConfig::F_DB_NAME));
					$database->setDBUser(hubConfig::get(hubConfig::F_DB_USER));
					$database->setDBPassword(hubConfig::get(hubConfig::F_DB_PASSWORD));
					$a_port = hubConfig::get(hubConfig::F_DB_PORT) ? hubConfig::get(hubConfig::F_DB_PORT) : 3306;
					$database->setDBPort($a_port);
					$database->connect();
					self::$db_cache = $database;
				}

				return self::$db_cache;
			} else {

				return parent::returnDB();
			}
		}*/

		return parent::returnDB();
	}
}

?>
