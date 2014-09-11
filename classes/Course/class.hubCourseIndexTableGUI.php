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
class hubCourseIndexTableGUI extends arIndexTableGUI
{

    protected function initToolbar()
    {
    }

    protected function addActions()
    {
        $this->addAction('view', $this->txt('details', false), get_class($this->parent_obj), 'view');
    }

    protected function customizeFields()
    {
        $field = $this->getField("title");
        $field->setVisible(true);
        $field->setSortable(true);
        $field->setHasFilter(true);
        $field->setPosition(10);

        $field = $this->getField("parent_id");
        $field->setVisible(true);
        $field->setHasFilter(true);
        $field->setPosition(20);

        $field = $this->getField("creation_date");
        $field->setVisible(true);
        $field->setSortable(true);
        $field->setPosition(30);

        $field = new arIndexTableField("status", "status", "text", 40, true, false, false);
        $this->addField($field);
    }

    protected function setArFieldData(arIndexTableField $field, $item, $value)
    {
        switch ($field->getName())
        {
            case 'title':
                return '<a target=\'_blank\' href=\'' . ilLink::_getLink($item['ilias_id']) . '\'>' . $item['title_prefix'] . $value . '</a>';
                break;
            case 'parent_id':
                return '<a target=\'_blank\' href=\'' . ilLink::_getLink($item['parent_id']) . '\'>' . $item['obj_title'] . '</a>';
                break;
            default:
                return parent::setArFieldData($field, $item, $value);
                break;
        }
    }

    protected function setCustomFieldData(arIndexTableField $field, $item)
    {
        $hubSyncHistory = hubSyncHistory::find($item['ext_id']);
        return $this->txt('common_status_' . $hubSyncHistory->getTemporaryStatus());
    }

    protected function beforeGetData()
    {
        $this->active_record_list->innerjoin("sr_hub_sync_history", "sr_hub_course.ext_id", "ext_id", array("ilias_id"));
        $this->active_record_list->innerjoin("object_reference", "parent_id", "ref_id", array("obj_id"));
        $this->active_record_list->innerjoin("object_data", "object_reference.obj_id", "obj_id", array("title AS obj_title"));
    }

    protected function addCustomFilterWhere($type, $name, $value)
    {
        if ($name == "parent_id")
        {
            $this->active_record_list->where("object_data.title like '%" . $value . "%'");
            return true;
        }
        return false;
    }
}

?>