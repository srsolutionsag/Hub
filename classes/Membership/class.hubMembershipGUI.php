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
    /**
     * @param $id
     */
    function view($id) {
        $display_gui_class = $this->record_type . "DisplayGUI";
        /**
         * @var arDisplayGUI $display_gui
         */
        $display_gui = new $display_gui_class($this, $this->ar->find($id));
        $this->tpl->setContent($display_gui->getHtml());
        if(hubConfig::is50()){
            $this->tpl->show();
        }
    }
}