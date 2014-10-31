<?php
require_once('./Customizing/global/plugins/Libraries/ActiveRecord/Views/Display/class.arDisplayGUI.php');
require_once('./Services/User/classes/class.ilObjUser.php');
/**
 * GUI-Class hubMembershipDisplayGUI
 *
 * @author            Timon Amstutz <timon.amstutz@ilub.unibe.ch>
 * @version           $Id:
 *
 */
class hubMembershipDisplayGUI extends arDisplayGUI
{
    /**
     * @var hubMembership $ar
     */
    protected $ar;

    public function setTitle(){
        $user = new ilObjUser($this->ar->getUsrId());
        $this->title =  $user->getPublicName();
    }

    public function customizeFields()
    {
        $field = $this->getField("usr_id");
        $field->setVisible(false);

        foreach($this->getFields() as $field){
            /**
             * @var arDisplayField $field
             */

            $getFunction = ActiveRecord::_toCamelCase("get".$field->getName());
            if($this->ar->$getFunction())
            {
                $field->setTxt("view_field_".$field->getName());
            }
            else{
                $field->setVisible(false);
            }
        }


        $field = $this->getField("ext_id");
        $field->setPosition(-20);

        $field = $this->getField("creation_date");
        $field->setPosition(10);

        $field = $this->getField("delivery_date_micro");
        $field->setPosition(20);

        $field= new arDisplayField("status","view_field_status", -10,true,true);
        $this->addField($field);
    }

    /**
     * @param arDisplayField $field
     * @param $value
     * @return bool|null|string
     */
    protected function setArFieldData(arDisplayField $field, $value)
    {
        /**
         * ilObject $ilObject
         */
        switch ($field->getName())
        {
            case 'container_id':
                return '<a target=\'_blank\' href=\'' . ilLink::_getLink($this->ar->getContainerId()) . '\'>' . ilObject2::_lookupTitle(ilObject2::_lookupObjId($this->ar->getContainerId())). '</a>';
                break;
            case 'sr_hub_origin_id':
                return hubOrigin::find($this->ar->getSrHubOriginId())->getTitle();
                break;
            case 'delivery_date_micro':
                return date("Y-m-d H:i:s", $value);
                break;
            default:
                return parent::setArFieldData($field, $value);
                break;
        }
    }

    /**
     * @param arDisplayField $field
     * @return string
     */
    protected function setCustomFieldData(arDisplayField $field){
        $hubSyncHistory = hubSyncHistory::find($this->ar->getExtId());
        return $this->txt('common_status_' . $hubSyncHistory->getTemporaryStatus());
    }
}