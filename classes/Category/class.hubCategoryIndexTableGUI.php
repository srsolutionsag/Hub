<?php
require_once('./Customizing/global/plugins/Libraries/ActiveRecord/Views/Index/class.arIndexTableGUI.php');
require_once('./Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php');
require_once('class.hubCategory.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Sync/class.hubSyncHistory.php');
include_once('./Services/Link/classes/class.ilLink.php');

/**
 * TableGUI hubCategoryTableGUI
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.1.04
 *
 */
class hubCategoryIndexTableGUI extends arIndexTableGUI {

    protected function initToolbar()
    {
    }

    protected function initFieldsToHide()
    {
        $this->setFieldsToHide(
            array("ext_id","show_infopage","show_news","position","order_type","description","parent_id_type","id","shortlink","delivery_date_micro","sr_hub_origin_id","ext_status","obj_id","obj_title"));
    }

    protected function initFieldsToSort()
    {
        $this->setFieldsToSort(array("title", "creation_date"));
    }

    protected function initFieldsToFilter()
    {
        $this->setFieldsToFilter(array("title"));
    }

    protected function addActions()
    {
        $this->addAction('view', $this->txt('details'), get_class($this->parent_obj), 'view');
    }


    protected function setTextData(arField $field, $value, $a_set)
    {
        if ($field->getName() == 'title')
        {
            $hubCategory      = hubCategory::find($a_set['ext_id']);
            $hubSyncHistory = hubSyncHistory::find($a_set['ext_id']);
            $this->tpl->setVariable('ENTRY_CONTENT', '<a target=\'_blank\' href=\'' . ilLink::_getLink($hubSyncHistory->getIliasId()) . '\'>' . $hubCategory->getTitlePrefix()
                . $value . '</a>');

        } else if ($field->getName() == 'parent_id')
        {
            $hubCategory      = hubCategory::find($a_set['ext_id']);
            $hubSyncHistory = hubSyncHistory::find($a_set['ext_id']);
            $hubParentCategory = hubCategory::find($hubCategory->getParentId());
            if ($hubParentCategory)
            {
                $hubSyncHistoryParent = hubSyncHistory::getInstance($hubParentCategory);
                $this->tpl->setVariable('ENTRY_CONTENT', '<a target=\'_blank\' href=\'' . ilLink::_getLink($hubSyncHistoryParent->getIliasId()) . '\'>'
                    . ilObject2::_lookupTitle(ilObject2::_lookupObjId($hubSyncHistoryParent->getIliasId())) . '</a>');
            } else
            {
                $this->tpl->setVariable('ENTRY_CONTENT', "<a target='_blank' href=''></a>");
            }
            $this->tpl->parseCurrentBlock();
            $this->tpl->setVariable('ENTRY_CONTENT', $this->txt('common_status_' . $hubSyncHistory->getTemporaryStatus()));

        } else
        {
            parent::setTextData($field, $value, $a_set);
        }

    }

    protected function addCustomColumn($key)
    {
        if ($key == "creation_date")
        {
            $this->addColumn($this->txt("status", false));
        }
        return false;
    }
}
?>