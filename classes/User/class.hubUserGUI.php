<?php
require_once(hub::pathToActiveRecord().'/Views/class.arGUI.php');
require_once('class.hubUser.php');
require_once('class.hubUserIndexTableGUI.php');
require_once('class.hubUserDisplayGUI.php');

/**
 * GUI-Class hubUserGUI
 *
 * @author            Timon Amstutz <timon.amstutz@ilub.unibe.ch>
 * @version           1.1.04
 *
 */
class hubUserGUI  extends arGUI {
    function executeCommand() {
        parent::executeCommand();
        if(hubConfig::is50()){
            $this->tpl->show();
        }
    }
}