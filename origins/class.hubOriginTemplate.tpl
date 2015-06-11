<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Origin/class.hubOrigin.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Origin/int.hubOriginInterface.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/User/class.%1$s.php');
/**
 * Class %2$s
 *
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
* @version 1.0.0** @revision $r$
*/
class %2$s extends hubOrigin implements hubOriginInterface {

    /**
    * @return bool
    * @description Connect to your Service, return bool status
    */
    public function connect() {
        // Get Data from $this->conf->getXYZ
        // TODO: Implement connect() method.
    }

    /**
    * @return bool
    * @description read your Data an save in Class
    */
    public function parseData() {
        // TODO: Implement parseData() method.
    }

    /**
    * @return int
    * @description read Checksum of your Data and return int Count
    */
    public function getChecksum() {
        // TODO: Implement getChecksum() method.
    }

    /**
    * @return array
    * @description return array of Data
    */
    public function getData() {
        // TODO: Implement getData() method.
    }

    /**
    * @return bool
    */
    public function buildEntries() {
        // TODO: Implement buildEntries() method.
        foreach ($this->data as $dat) {
            $entry = new hubCategory($dat['ext_id']);
            $entry->setTitle($dat['title']);
            $entry->setDescription($dat['description']);
            $entry->setExtId($dat['ext_id']);
            // [...] Set all Fields
            $entry->create($this);
        }
        return true;
    }
}?>