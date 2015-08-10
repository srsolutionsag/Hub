<?php
require_once(hub::pathToActiveRecord().'/Views/class.arGUI.php');
require_once('class.hubCategoryGUI.php');
require_once('class.hubCategoryIndexTableGUI.php');
require_once('class.hubCategoryDisplayGUI.php');

/**
 * GUI-Class hubCategoryGUI
 *
 * @author            Timon Amstutz <timon.amstutz@ilub.unibe.ch>
 * @version           1.1.04
 *
 */
class hubCategoryGUI extends arGUI
{
    function executeCommand() {
        parent::executeCommand();
        if(hubConfig::is50()){
            $this->tpl->show();
        }
    }
}