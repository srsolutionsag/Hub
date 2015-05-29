<?php
require_once(hub::pathToActiveRecord().'/Views/class.arGUI.php');
require_once('class.hubCourse.php');
require_once('class.hubCourseIndexTableGUI.php');
require_once('class.hubCourseDisplayGUI.php');

/**
 * GUI-Class hubCourseGUI
 *
 * @author            Timon Amstutz <timon.amstutz@ilub.unibe.ch>
 * @version           1.1.04
 *
 */
class hubCourseGUI  extends arGUI {
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