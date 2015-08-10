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
    function executeCommand() {
        parent::executeCommand();
        if(hubConfig::is50()){
            $this->tpl->show();
        }
    }
}