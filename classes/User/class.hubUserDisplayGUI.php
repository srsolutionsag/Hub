<?php
require_once(hub::pathToActiveRecord().'/Views/Display/class.arDisplayGUI.php');
require_once('./Services/User/classes/class.ilObjUser.php');

/**
 * GUI-Class hubUserDisplayGUI
 *
 * @author            Timon Amstutz <timon.amstutz@ilub.unibe.ch>
 * @version           $Id:
 *
 */
class hubUserDisplayGUI extends arDisplayGUI {
    /**
     * @var hubUser $ar
     */
    protected $ar;


    public function setTitle() {
        $this->title = $this->ar->getFirstname(). " ".$this->ar->getLastname();
    }

    public function customizeFields()
    {
        $this->getFields()->setTxtPrefix("view_field_");

        foreach($this->getFieldsAsArray() as $field){
                $field->setVisible(false);
        }

        $field = $this->getField("email");
        $field->setVisible(true);
        $field->setPosition(-20);

        $field = $this->getField("gender");
        $field->setVisible(true);
        $field->setPosition(-10);

        $field = $this->getField("external_account");
        $field->setVisible(true);

        $field->setPosition(10);

        $field = $this->getField("creation_date");
        $field->setVisible(true);

        $field->setPosition(0);

        $field = $this->getField("delivery_date_micro");
        $field->setVisible(true);

        $field->setPosition(10);


        $field= new arDisplayField("status","view_field_status", 20,true,true);
        $this->addField($field);

    }

    /**
     * @param arIndexTableField $field
     * @param $item
     * @return string
     */
    protected function setCustomFieldData(arDisplayField $field){
        $hubSyncHistory = hubSyncHistory::find($this->ar->getExtId());
        return $this->txt('common_status_' . $hubSyncHistory->getTemporaryStatus());
    }
}