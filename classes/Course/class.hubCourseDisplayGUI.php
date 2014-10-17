<?php
require_once('./Customizing/global/plugins/Libraries/ActiveRecord/Views/Display/class.arDisplayGUI.php');

/**
 * GUI-Class ActiveRecordDisplayGUI
 *
 * @author            Timon Amstutz <timon.amstutz@ilub.unibe.ch>
 * @version           $Id:
 *
 */
class hubCourseDisplayGUI extends arDisplayGUI
{
    protected function initFieldsToHide()
    {
        $this->setFieldsToHide(array("id"));
    }
}