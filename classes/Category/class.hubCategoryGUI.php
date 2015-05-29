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
        if(hub::is50()){
            $this->tpl->show();
        }
    }
}