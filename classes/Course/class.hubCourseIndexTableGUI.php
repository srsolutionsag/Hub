<?php
require_once('./Customizing/global/plugins/Libraries/ActiveRecord/Views/Index/class.arIndexTableGUI.php');
require_once('./Customizing/global/plugins/Libraries/ActiveRecord/Views/Index/class.arIndexTableGUI.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Sync/class.hubSyncHistory.php');
require_once('./Services/Link/classes/class.ilLink.php');
/**
 * TableGUI hubCourseIndexTableGUI
 *
 * @author            Timon Amstutz <timon.amstutz@ilub.unibe.ch>
 * @version 1.1.04
 *
 */
class hubCourseIndexTableGUI extends arIndexTableGUI {

    protected function initToolbar()
    {
    }

    protected function initFieldsToHide()
    {
        $this->setFieldsToHide(
            array("type", "period", "learning_target", "important_information", "responsible","language","first_dependence","second_dependence","third_dependence","title_prefix","title_extension",
                "administrators","responsible_email","notification_email","owner","description","parent_id_type","delivery_date_micro","sr_hub_origin_id","ext_status","shortlink","ext_id","id","obj_id", "obj_title"
            ));
    }

    protected function initFieldsToSort()
    {
        $this->setFieldsToSort( array("title","creation_date"));
    }

    protected function initFieldsToFilter()
    {
        $this->setFieldsToFilter(array("title","parent_id"));
    }

    protected function addActions()
    {
        $this->addAction('view', $this->txt('details',false), get_class($this->parent_obj), 'view');
    }

    protected function addCustomFilterWhere($type, $name, $value)
    {
        if($name == "parent_id")
        {
            $this->active_record_list->innerjoin("object_reference","parent_id","ref_id",array("obj_id"));
            $this->active_record_list->innerjoin("object_data","object_reference.obj_id","obj_id", array("title AS obj_title"));
            $this->active_record_list->where("object_data.title like '%" . $value . "%'");
            return true;
        }
        return false;
    }

    protected function setTextData(arField $field, $value, $a_set)
    {
        if ($field->getName() == 'title')
        {
            $hubCourse      = hubCourse::find($a_set['ext_id']);
            $hubSyncHistory = hubSyncHistory::find($a_set['ext_id']);
            $this->tpl->setVariable('ENTRY_CONTENT', '<a target=\'_blank\' href=\'' . ilLink::_getLink($hubSyncHistory->getIliasId()) . '\'>' . $hubCourse->getTitlePrefix()
                . $value . '</a>');

        }
        else if ($field->getName() == 'parent_id')
        {
            $hubCourse      = hubCourse::find($a_set['ext_id']);
            $hubSyncHistory = hubSyncHistory::find($a_set['ext_id']);

            $this->tpl->setVariable('ENTRY_CONTENT', '<a target=\'_blank\' href=\'' . ilLink::_getLink($hubCourse->getParentId()) . '\'>'
                . ilObject2::_lookupTitle(ilObject2::_lookupObjId($hubCourse->getParentId())) . '</a>');
            $this->tpl->parseCurrentBlock();
            $this->tpl->setVariable('ENTRY_CONTENT', $this->txt('common_status_' . $hubSyncHistory->getTemporaryStatus()));

        }
        else
        {
            parent::setTextData($field, $value, $a_set);
        }

    }
    protected function addCustomColumn($key){
        if($key == "creation_date")
        {
            $this->addColumn($this->txt("status",false));
        }
        return false;
    }

}

?>