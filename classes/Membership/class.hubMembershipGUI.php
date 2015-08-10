<?php
require_once(hub::pathToActiveRecord().'/Views/class.arGUI.php');
require_once('class.hubMembership.php');
require_once('class.hubMembershipIndexTableGUI.php');
require_once('class.hubMembershipDisplayGUI.php');

/**
 * GUI-Class hubMembershipGUI
 *
 * @author            Timon Amstutz <timon.amstutz@ilub.unibe.ch>
 * @version           1.1.04
 *
 */
class hubMembershipGUI extends arGUI
{
    function executeCommand() {
        parent::executeCommand();
        if(hubConfig::is50()){
            $this->tpl->show();
        }
    }
}