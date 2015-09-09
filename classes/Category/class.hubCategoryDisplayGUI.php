<?php
require_once(hub::pathToActiveRecord().'/Views/Display/class.arDisplayGUI.php');

/**
 * GUI-Class hubCategoryDisplayGUI
 *
 * @author            Timon Amstutz <timon.amstutz@ilub.unibe.ch>
 * @version           $Id:
 *
 */
class hubCategoryDisplayGUI extends arDisplayGUI
{
    /**
     * @var hubCategory $ar
     */
    protected $ar;

    public function setTitle(){
        /**
         * @var hubCourse $hubCourse
         */
        $hubCourse      = hubCategory::find($this->ar->getExtId());
        $hubSyncHistory = hubSyncHistory::find($this->ar->getExtId());
        $this->title =  '<a target=\'_blank\' href=\'' . ilLink::_getLink($hubSyncHistory->getIliasId()) . '\'>' . $hubCourse->getTitlePrefix() . $hubCourse->getTitle() . '</a>';
    }
    public function customizeFields()
    {
        $this->getFields()->setTxtPrefix("view_field_");
        $field = $this->getField("title");
        $field->setVisible(false);

        foreach($this->getFieldsAsArray() as $field){
            /**
             * @var arDisplayField $field
             */
            $getFunction = $field->getGetFunctionName();
            if(!$this->ar->$getFunction())
            {
                $field->setVisible(false);
            }
        }

        $field = $this->getField("parent_id");
        $field->setPosition(-30);

        $field = $this->getField("creation_date");
        $field->setPosition(10);

        $field = $this->getField("delivery_date_micro");
        $field->setPosition(20);

        $field= new arDisplayField("status","view_field_status", -1,true,true);
        $this->addField($field);
    }

    /**
     * @param arDisplayField $field
     * @param $value
     * @return bool|null|string
     */
    protected function setArFieldData(arDisplayField $field, $value)
    {
        switch ($field->getName())
        {
            case 'title':
                $hubSyncHistory = hubSyncHistory::find($this->ar->getExtId());
                return '<a target=\'_blank\' href=\'' . ilLink::_getLink($hubSyncHistory->getIliasId()) . '\'>' . $this->ar->getTitlePrefix() . $value . '</a>';
                break;
            case 'parent_id':
                $hubParentCategory = hubCategory::find($this->ar->getParentId());
                if ($hubParentCategory)
                {
                    $hubSyncHistoryParent = hubSyncHistory::getInstance($hubParentCategory);
                    return '<a target=\'_blank\' href=\'' . ilLink::_getLink($hubSyncHistoryParent->getIliasId()) . '\'>'. ilObject2::_lookupTitle(ilObject2::_lookupObjId($hubSyncHistoryParent->getIliasId())) . '</a>';
                } else
                {
                    return "<a target='_blank' href=''></a>";
                }
                break;
            case 'sr_hub_origin_id':
                return hubOrigin::find($this->ar->getSrHubOriginId())->getTitle();
                break;
            case 'creation_date':
                return $value;
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