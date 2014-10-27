<?php
require_once('./Customizing/global/plugins/Libraries/ActiveRecord/Views/Index/class.arIndexTableGUI.php');
require_once('./Customizing/global/plugins/Libraries/ActiveRecord/Views/Index/class.arIndexTableGUI.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Sync/class.hubSyncHistory.php');
require_once('./Services/Link/classes/class.ilLink.php');
/**
 * TableGUI hubMembershipIndexTableGUI
 *
 * @author            Timon Amstutz <timon.amstutz@ilub.unibe.ch>
 * @version 1.1.04
 *
 */
class hubMembershipIndexTableGUI extends arIndexTableGUI {
    protected function beforeGetData(){
        $this->setDefaultOrderField("usr_id");
    }

    protected function addActions(){
    }

    public function customizeFields()
    {
        $field = $this->getField("usr_id");
        $field->setTxt("view_field_".$field->getName());
        $field->setVisible(true);
        $field->setSortable(false);
        $field->setHasFilter(false);
        $field->setPosition(10);

        $field = $this->getField("container_id");
        $field->setTxt("view_field_".$field->getName());
        $field->setVisible(true);
        $field->setSortable(false);
        $field->setHasFilter(false);
        $field->setPosition(15);

        $field = $this->getField("ext_id");
        $field->setTxt("view_field_".$field->getName());
        $field->setVisible(true);
        $field->setHasFilter(true);
        $field->setSortable(false);
        $field->setPosition(20);

        $field = $this->getField("creation_date");
        $field->setTxt("view_field_".$field->getName());
        $field->setVisible(true);
        $field->setSortable(false);
        $field->setPosition(30);

    }
}