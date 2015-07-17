<?php
require_once(hub::pathToActiveRecord().'/Views/Index/class.arIndexTableGUI.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Sync/class.hubSyncHistory.php');
require_once('./Services/Link/classes/class.ilLink.php');

/**
 * TableGUI hubMembershipIndexTableGUI
 *
 * @author            Timon Amstutz <timon.amstutz@ilub.unibe.ch>
 * @version           1.1.04
 *
 */
class hubUserIndexTableGUI extends arIndexTableGUI {

    protected function initActions()
    {
        $this->addAction(new arIndexTableAction('view', $this->txt('details', false), get_class($this->parent_obj), 'view'));
    }

    public function customizeFields()
    {
        $this->getFields()->setTxtPrefix("view_field_");

        $field = $this->getField("firstname");
        $field->setVisibleDefault(true);
        $field->setSortable(true);
        $field->setHasFilter(true);
        $field->setPosition(10);

        $field = $this->getField("lastname");
        $field->setVisibleDefault(true);
        $field->setSortable(true);
        $field->setHasFilter(true);
        $field->setPosition(20);

        $field = $this->getField("email");
        $field->setVisibleDefault(true);
        $field->setSortable(true);
        $field->setPosition(40);

        $field = $this->getField("gender");
        $field->setVisibleDefault(false);
        $field->setSortable(true);
        $field->setHasFilter(false);
        $field->setPosition(50);


        $field = $this->getField("external_account");
        $field->setVisibleDefault(true);
        $field->setSortable(true);
        $field->setHasFilter(true);
        $field->setPosition(60);

        $field= new arIndexTableField("status","view_field_status", 70,true,false,false,false);
        $this->addField($field);
    }

    /**
     * @param arIndexTableField $field
     * @param $item
     * @return string
     */
    protected function setCustomFieldData(arIndexTableField $field, $item){
        $hubSyncHistory = hubSyncHistory::find($item['ext_id']);
        return $this->txt('common_status_' . $hubSyncHistory->getTemporaryStatus());
    }



}