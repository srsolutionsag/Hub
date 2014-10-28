<?php
require_once('./Customizing/global/plugins/Libraries/ActiveRecord/Views/Index/class.arIndexTableGUI.php');
require_once('./Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php');
require_once('class.hubCategory.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Sync/class.hubSyncHistory.php');
include_once('./Services/Link/classes/class.ilLink.php');

/**
 * TableGUI hubCategoryIndexTableGUI
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.1.04
 *
 */
class hubCategoryIndexTableGUI extends arIndexTableGUI {

    protected function beforeGetData(){
        $this->setDefaultOrderField("title");
    }

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
        $field->setTxt("view_field_".$field->getName());
        $field->setVisible(true);
        $field->setSortable(true);
        $field->setHasFilter(true);
        $field->setPosition(10);

        $field = $this->getField("parent_id");
        $field->setTxt("view_field_".$field->getName());
        $field->setVisible(true);
        $field->setPosition(20);

        $field = $this->getField("creation_date");
        $field->setTxt("view_field_".$field->getName());
        $field->setVisible(true);
        $field->setSortable(true);
        $field->setPosition(40);

        $field = new arIndexTableField("status", "view_field_status", 30, true, false, false);
        $this->addField($field);
    }

    protected function setArFieldData(arIndexTableField $field, $item, $value)
    {
        switch ($field->getName())
        {
            case 'title':
                $hubCategory    = hubCategory::find($item['ext_id']);
                $hubSyncHistory = hubSyncHistory::find($item['ext_id']);
                return '<a target=\'_blank\' href=\'' . ilLink::_getLink($hubSyncHistory->getIliasId()) . '\'>' . $hubCategory->getTitlePrefix(). $value . '</a>';
                break;
            case 'parent_id':
                $hubCategory       = hubCategory::find($item['ext_id']);
                $hubParentCategory = hubCategory::find($hubCategory->getParentId());
                if ($hubParentCategory)
                {
                    $hubSyncHistoryParent = hubSyncHistory::getInstance($hubParentCategory);
                    return '<a target=\'_blank\' href=\'' . ilLink::_getLink($hubSyncHistoryParent->getIliasId()) . '\'>'
                        . ilObject2::_lookupTitle(ilObject2::_lookupObjId($hubSyncHistoryParent->getIliasId())) . '</a>';
                } else
                {
                    return "<a target='_blank' href=''></a>";
                }
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
}
?>