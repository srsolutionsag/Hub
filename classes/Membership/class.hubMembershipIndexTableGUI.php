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

    protected function initToolbar(){
    }

    protected function beforeGetData(){
        $this->setDefaultOrderField("usr_id");
    }

    protected function addActions(){
    }

    public function customizeFields()
    {
        $field = $this->getField("ext_id");
        $field->setTxt("view_field_".$field->getName());
        $field->setVisible(true);
        $field->setHasFilter(true);
        $field->setSortable(false);
        $field->setPosition(0);

        $field = $this->getField("usr_id");
        $field->setTxt("view_field_user_name");
        $field->setVisible(true);
        $field->setSortable(false);
        $field->setHasFilter(false);
        $field->setPosition(10);

        $field = $this->getField("container_id");
        $field->setTxt("view_field_ilias_object");
        $field->setVisible(true);
        $field->setSortable(false);
        $field->setHasFilter(false);
        $field->setPosition(15);

        $field = $this->getField("creation_date");
        $field->setTxt("view_field_".$field->getName());
        $field->setVisible(true);
        $field->setSortable(false);
        $field->setPosition(30);

    }

    /**
     * @param arIndexTableField $field
     * @param array $item
     * @param mixed $value
     * @return string
     **/

    protected function setArFieldData(arIndexTableField $field, $item, $value)
    {

        /**
         * @var hubMembership $hubMembership
         **/

        switch ($field->getName())
        {
            case 'usr_id':
                $hubMembership      = hubMembership::find($item['ext_id']);
                $user = new ilObjUser($hubMembership->getUsrId());
                return $user->getPublicName();
                break;
            case 'container_id':
                $hubMembership = hubMembership::find($item['ext_id']);
                return '<a target=\'_blank\' href=\'' . ilLink::_getLink($hubMembership->getContainerId()) . '\'>' . ilObject2::_lookupTitle(ilObject2::_lookupObjId($hubMembership->getContainerId())) . '</a>';
                break;
            default:
                return parent::setArFieldData($field, $item, $value);
                break;
        }
    }

}