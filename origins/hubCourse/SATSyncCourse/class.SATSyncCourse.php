<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Origin/class.hubOrigin.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Origin/int.hubOriginInterface.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Category/class.hubCategory.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/User/class.hubUser.php');
require_once('./Modules/ItemGroup/classes/class.ilItemGroupItems.php');
/**
 * Class SATSyncCourse
 *
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
* @version 1.0.0** @revision $r$
*/
class SATSyncCourse extends hubOrigin implements hubOriginInterface {


    const DELIMITER = ';';

    /**
     * @var array
     */
    protected $data = array();

    /**
     * Field columns inside CSV (correct order!!!)
     *
     * @var array
     */
    protected static $fields_external = array(
        'client_id',
        'template_id',
        'id',           //ARTEMIS-id
        'course_title',
        'start_date',
        'end_date',
    );

    /**
     * Stores the objects coming from the external system that are newly delivered
     *
     * @var array
     */
    protected $new_delivered_objects = array();

    /**
     * @var bool
     */
    protected $ar_safe_read = false;

    /**
     * @param int $a_id
     */
    public function __construct($a_id = 0)
    {
        parent::__construct($a_id);
    }

    /**
     * @return bool
     * @description Connect to your Service, return bool status
     */
    public function connect()
    {
        return is_readable($this->conf()->getFilePath());
    }

    /**
     * @description read your Data an save in Class
     * @throws hubOriginException
     * @return bool
     */
    public function parseData()
    {
        $file = $this->conf()->getFilePath();
        $n_fields = count(self::$fields_external);
        $line = 0;
        $checksum = 0;
        if (($handle = fopen($file, 'r')) !== false) {
            while (($data = fgetcsv($handle, 0, self::DELIMITER)) !== false) {
                $count = count($data);
                if ($count != $n_fields) {
                    $msg = "Line $line: number of columns does not match. Got $count, expected $n_fields";
                    throw new hubOriginException(hubOriginException::PARSE_DATA_FAILED, $this, true, $msg);
                } else {
//                    $this->validateRecord($data, $line);
                    $tmp = new stdClass();
                    foreach (self::$fields_external as $i => $name) {
                        $tmp->$name = $data[$i];
                    }

                    //don't deliver course if manually deleted in ILIAS
//                    $hub_course = new hubCourse($tmp->id);
//                    if ($this->checkDeletedCourseIlias($hub_course)) {
//                        continue;
//                    }

                    if(!$this->templateExists($tmp->template_id)){
                        $this->log->write("Skipped course with artemis-id {$tmp->id}: template course with ref_id {$tmp->template_id} not found in ilias");
                        continue;
                    }

                    $this->data[$tmp->id] = $tmp;
                    $checksum++;
                    $line++;
                }
            }
            fclose($handle);
        }
        $this->checksum = $checksum;
        return true;
    }

    /**
    * @return array
    * @description return array of Data
    */
    public function getData() {
        return $this->data;
    }

    /**
    * @return bool
    */
    public function buildEntries() {
        global $tree;
        foreach ($this->data as $data) {
            $ilias_course = new ilObjCourse($data->template_id);
            $hub_course = new hubCourse($data->id);
            $hub_course->setTitle($data->course_title);
            $hub_course->setDescription($ilias_course->getDescription());
            $hub_course->setParentIdType(hubCourse::PARENT_ID_TYPE_REF_ID);
            $hub_course->setParentId($tree->getParentId($data->template_id));
            $hub_course->create($this);
        }
        return true;
    }

    /**
     * @return bool|void
     */
    public function afterSync()
    {
        foreach ($this->data as $data) {
            $hub_course = new hubCourse($data->id);

            if ($hub_course->getHistoryObject()->getStatus() == hubSyncHistory::STATUS_NEW) {
                $this->new_delivered_objects[] = $data->id;
            }
        }
    }

    /**
     * Do all stuff after the ILIAS object is existing for sure
     *
     * @param hubCourse $hub_object
     * @return \hubObject|void
     */
    public function afterObjectInit(hubCourse $hub_object)
    {
        $ref_id = $hub_object->getHistoryObject()->getIliasId();
        $ext_id = $hub_object->getExtId();

        // Create object blocks if not existing and move course into correct object block (autumn or spring)
        // Not when deleted
        if(!$ref_id){
            $this->log->write("Course deleted: ext_id={$ext_id}");
        }

        // Only when newly delivered!!
        if (in_array($hub_object->getExtId(), $this->new_delivered_objects)) {
            $this->log->write("saveMetaData: ext_id=$ext_id, ref_id=$ref_id");
            // Add advanced metadata to course
            $this->saveMetaData($hub_object);
            $this->log->write("copyOrLinkCourseContent: ext_id=$ext_id, ref_id=$ref_id");
            // Copy or link content from course template
            $this->copyContainerContent(0, 0, $hub_object);
            $this->log->write("addImportId: ext_id=$ext_id, ref_id=$ref_id");
            // Add Import ID to course
            $this->addImportId($hub_object);
            $this->log->write("addImportIdToParentCategory: ext_id=$ext_id, ref_id=$ref_id");
            // Add Import ID to parent category
            $this->addImportIdToParentCategory($hub_object);
            $this->log->write("copy learning progress: ext_id=$ext_id, ref_id=$ref_id");
            // Copy LP
            $this->copyLearningProgress($hub_object);
            // Set course online
            $this->setOnline($hub_object);
        } else {
            $this->log->write("Course not newly delivered: ext_id={$ext_id}, ref_id={$ref_id}");
        }
        $this->log->write("setCourseValidity: ext_id=$ext_id, ref_id=$ref_id");
        //update validity of course
        $this->updateValidity($hub_object);
    }

    protected function copyLearningProgress(hubCourse $hub_object) {
        $data = $this->data[$hub_object->getExtId()];
        $from_ref_id = $data->template_id;
        $to_ref_id = $hub_object->getHistoryObject()->getIliasId();

        //copy settings
        $settings_old = new ilLPObjSettings(ilObject2::_lookupObjId($from_ref_id));
        $settings_new = new ilLPObjSettings(ilObject2::_lookupObjId($to_ref_id));
        $settings_new->setMode($settings_old->getMode());
        $settings_new->setVisits($settings_old->getVisits());
        $settings_new->update();

        //copy collection
        $collection_old = new ilLPCollections(ilObject2::_lookupObjId($from_ref_id));
        $collection_new = new ilLPCollections(ilObject2::_lookupObjId($to_ref_id));
        foreach ($collection_old->getItems() as $item) {
            $collection_new->add($item);
        }
    }

    /**
     * set activation start/end to course and set course online
     *
     * @param $hub_object
     */
    protected function setOnline(hubCourse $hub_object){
        $ref_id = $hub_object->getHistoryObject()->getIliasId();
        $course = new ilObjCourse($ref_id);
        $course->setOfflineStatus(false);
        $course->update();
    }

    protected function updateValidity(hubCourse $hub_object) {
        global $ilDB;
        $ref_id = $hub_object->getHistoryObject()->getIliasId();
        $data = $this->data[$hub_object->getExtId()];
        $ilDB->update('crs_items',
            array(
                'timing_type' => array('integer', 0),
                'timing_start' => array('integer', strtotime($data->start_date)),
                'timing_end' => array('integer', strtotime($data->end_date))),
            array(
                'obj_id' => array('integer', $ref_id))
        );
    }

    /**
     * Add advanced metadata to course
     *
     * @param hubCourse $hub_object
     */
    protected function saveMetaData(hubCourse $hub_object)
    {

//        //old solution for ilias 4.3
//        $ref_id = $hub_object->getHistoryObject()->getIliasId();
//        $obj_id = ilObject2::_lookupObjectId($ref_id);
//        foreach (self::$mapping_metadata as $name => $field_id) {
//            $data = $this->data[$hub_object->getExtId()];
//            $value = $data->{$name};
//            if ($value) {
//                $adv = new ilAdvancedMDValue($field_id, $obj_id);
//                $adv->setValue($value);
//                $adv->save();
//            }
//        }

//        //new solution for ilias 5.0.x
//        $ref_id = $hub_object->getHistoryObject()->getIliasId();
//        $obj_id = ilObject2::_lookupObjectId($ref_id);
//        $data = $this->data[$hub_object->getExtId()];
//        foreach(ilAdvancedMDValues::getInstancesForObjectId($obj_id) as $record_id => $md_values){
//            $md_values->read();
//            $adt_group = $md_values->getADTGroup();
//            foreach (self::$mapping_metadata as $name => $field_id) {
//                $value = $data->{$name};
//                if ($value) {
//                    $adt_group->getElement($field_id)->setText($value);
//                }
//            }
//            $md_values->write();
//        }
    }

    /**
     * Add import ID to parent category of course
     *
     * @param hubCourse $hub_object
     */
    protected function addImportIdToParentCategory(hubCourse $hub_object)
    {
        global $ilLog;
        $ref_id = $hub_object->getHistoryObject()->getIliasId();
        $ilLog->write("DEBUG: REF_ID:".$ref_id);
        $data = $this->data[$hub_object->getExtId()];
        $cat = new ilObjCategory($hub_object->getParentId());
        $cat->setImportId('refID_' . $data->id);
        $cat->update();
    }

    /**
     * Add Import ID to course in ILIAS
     *
     * @param hubCourse $hub_object
     */
    protected function addImportId(hubCourse $hub_object)
    {
        $crs = new ilObjCourse($hub_object->getHistoryObject()->getIliasId());
        $crs->setImportId("ARTEMIS_" . $hub_object->getExtId());
        $crs->update();
    }

    /**
     * Copy content from a container to another container.
     * Containers can be courses or folders. In case of a folder, this method calls itself recursive
     * in order to copy also the folder content to the new location
     *
     * @param int $from_ref_id Container ref-id where content is copied from
     * @param int $to_ref_id Container ref-id where content is copied to
     * @param string $mode copy|link Copy or link content, respects settings of plugins (certain types can't be linked...)
     */
    protected function copyContainerContent($from_ref_id, $to_ref_id, hubCourse $hub_object = null)
    {
        if($hub_object){
            $history = $hub_object->getHistoryObject();
            $data = $this->data[$hub_object->getExtId()];
            $from_ref_id = $data->template_id;
            $to_ref_id = $history->getIliasId();
        }

        // temporarily disable mathjax to avoid a bug occuring while copying a forum
        $mathJaxSetting = new ilSetting("MathJax");
        $mathjax_enabled = $mathJaxSetting->get("enable");
        if($mathjax_enabled){
            $mathJaxSetting->set('enable', 0);
        }

        /** @var ilContainer $obj */
        $allowed_types = array('crs', 'fold');
        $type = ilObject2::_lookupType($from_ref_id, true);
        if (!in_array($type, $allowed_types)) {
            $this->log->write("SATSyncCourses::copyCourseContent() Trying to copy content from a wrong type [{$type}], aborted copying content");
            return;
        }
        $class = ilObjectFactory::getClassByType($type);
        $obj = new $class($from_ref_id);
        $new_obj = new $class($to_ref_id);
        $_obj = $obj; // Save original from container
        $_new_obj = $new_obj; // Save original to container
        if ($type == 'crs') {
            // First call for course: Need to copy additional course settings
            $this->copyCourseData($obj, $new_obj); // Copy settings and stuff from container
        }

        // Start copying/linking all children of the container from_ref_id to new container to_ref_id
        // ******************************************************************************************

        $copied_objects = array();
        $items = $this->getSubItems($_obj);

        foreach ($items as $item) {
            $child_ref_id = $item['ref_id'];
            $type = $item['type'];
            // First check if we are allowed to copy/link the item at all
            if (!$this->allowedCopyType($type)) {
                $this->log->write("Skipping copying object with type [{$type}], not allowed to copy");
                continue;
            }
            // Start copying item
                $class = ilObjectFactory::getClassByType($type);
                /** @var ilObject $obj */
                /** @var ilObject $new_obj */
                $obj = new $class($child_ref_id);
                try {
                    $new_obj = $obj->cloneObject($to_ref_id);
                    $copied_objects[$child_ref_id] = array('new_ref_id' => $new_obj->getRefId(), 'type' => $type, 'obj' => $obj, 'new_obj' => $new_obj);
                    if ($type == 'fold') {
                        // If it is a folder, we need to copy also its content -> call this function recursive (with original mode)
                        $this->copyContainerContent($child_ref_id, $new_obj->getRefId());
                    }
                } catch (Exception $e) {
                    $this->log->write("Exception while copying container content [ref_id=$child_ref_id, type=$type, message={$e->getMessage()}]");
                }
        }
        $this->afterCopyingObjects($_obj, $_new_obj, $copied_objects);

        //enable mathjax again (if it was before)
        if($mathjax_enabled){
            $mathJaxSetting->set('enable', 1);
        }
    }

    /**
     * Copy course settings, sorting, page objects etc.
     *
     * @param ilObjCourse $obj Container (course, folder) where data is copied from
     * @param ilObjCourse $new_obj Container (course, folder) where data is copied to
     */
    protected function copyCourseData(ilObjCourse $obj, ilObjCourse $new_obj)
    {
        include_once('./Services/Container/classes/class.ilContainerSortingSettings.php');
        $sorting = new ilContainerSortingSettings($new_obj->getId());
        $sorting->setSortMode($obj->getOrderType());
        $sorting->update();

        // copy content page
        include_once("./Services/COPage/classes/class.ilPageObject.php");
        if (ilPageObject::_exists($obj->getType(), $obj->getId())) {
            $orig_page = new ilPageObject($obj->getType(), $obj->getId());
            $new_page_object = new ilPageObject($obj->getType());
            $new_page_object->setParentId($new_obj->getId());
            $new_page_object->setId($new_obj->getId());
            $new_page_object->createFromXML();
            $new_page_object->setXMLContent($orig_page->getXMLContent());
            $new_page_object->buildDom();
            $new_page_object->update();
        }

        // Copy course settings, overwrite desired settings afterwards ;)
        $obj->cloneSettings($new_obj);
    }

    protected function cloneCertificateSettings(ilObjCourse $obj, ilObjCourse $new_obj)
    {
        $this->log->write('start cloning CertificateSettings');
        if($cert_def = srCertificateDefinition::where(array('ref_id' => $obj->getRefId()))->first())
        {
            $cert_def->copy($new_obj->getRefId());
        }

    }

    /**
     * Return all children of a container with ref_id and type
     *
     * @param ilContainer $container_obj
     * @return array
     */
    protected function getSubItems(ilContainer $container_obj)
    {
        $sub_items = $container_obj->getSubItems();
        $items = array();
        if (is_array($sub_items['_all'])) {
            foreach ($sub_items['_all'] as $item) {
                $ref_id = $item['child'];
                $type = ilObject2::_lookupType($ref_id, true);
                $items[$ref_id] = array('ref_id' => $ref_id, 'type' => $type);
                if ($type == 'sess') {
                    // If type is session (Sitzung), append session items to to the array, because they are missing... -.-
                    /** @var ilObjSession $obj */
                    $materials = new ilEventItems(ilObject::_lookupObjectId($ref_id));
                    foreach ($materials->getItems() as $item_ref_id) {
                        if (isset($items[$item_ref_id])) {
                            continue;
                        }
                        $type = ilObject2::_lookupType($item_ref_id, true);
                        $items[$item_ref_id] = array('ref_id' => $item_ref_id, 'type' => $type);
                    }
                }
            }
        }
        return $items;
    }

    /**
     * Checks if a certain object type is allowed to be copied (depending on plugin settings)
     *
     * @param $type
     * @return bool
     */
    protected function allowedCopyType($type)
    {
        $types = $this->props()->get('omit_copy_object_types');
        if ($types) {
            $omit_copy_types = explode(',', $types);
            return !in_array($type, $omit_copy_types);
        }
        return true;
    }

    /**
     * Executed after children of a container are copied or linked to new location.
     * This method fixes item groups (object blocks) that are empty after copying
     * Also the sorting of the children is fixed if sorting mode of container is manual
     *
     *
     * @param ilContainer $obj Original object where items were copied from
     * @param ilContainer $new_obj New object where items were copied to
     * @param array $copied_objects
     */
    protected function afterCopyingObjects($obj, $new_obj, array $copied_objects)
    {
        global $ilDB;

        if($obj->getType() == 'crs') {
            //Copy certificates
            $this->cloneCertificateSettings($obj, $new_obj);
        }

        // If sorting of container is manual, need to update the sorting of each child
        $sort_data = array();
        $set = $ilDB->query("SELECT * FROM container_sorting WHERE obj_id = " . $ilDB->quote($obj->getId(), 'integer'));
        while ($row = $ilDB->fetchObject($set)) {
            $sort_data[$row->child_id] = $row;
        }

        foreach ($copied_objects as $orig_ref_id => $copy_data) {

            // Object blocks are copied but the objects inside are not inserted at new container
            // Get all copied object blocks (type=itgr) and append the same item(s) as in the old container
            if ($copy_data['type'] == 'itgr') {
                $block_items = new ilItemGroupItems($orig_ref_id);
                if (count($block_items->getItems())) {
                    $new_block_item_ref_id = $copy_data['new_ref_id'];
                    $block_items_new = new ilItemGroupItems($new_block_item_ref_id);
                    foreach ($block_items->getItems() as $item_ref_id) {
                        $new_ref_id = $copied_objects[$item_ref_id]['new_ref_id'];
                        $block_items_new->addItem($new_ref_id);
                    }
                    $block_items_new->update();
                }
            }

            // Same problem with sessions -> add all objects to the new sessions created
            if ($copy_data['type'] == 'sess') {
                $session_items = new ilEventItems($copy_data['obj']->getId());
                if (count($session_items->getItems())) {
                    $session_items_new = new ilEventItems($copy_data['new_obj']->getId());
                    foreach ($session_items->getItems() as $item_ref_id) {
                        $new_ref_id = $copied_objects[$item_ref_id]['new_ref_id'];
                        $session_items_new->addItem($new_ref_id);
                    }
                    $session_items_new->update();
                }
            }

            // Fix sorting if children are manually sorted
            if (count($sort_data) && isset($sort_data[$orig_ref_id])) {
                $sort = $sort_data[$orig_ref_id];
                // If the item is inside a object block or session (sitzung), need to have object id of new object
                $parent_id = 0;
                if (in_array($sort->parent_type, array('itgr', 'sess'))) {
                    foreach ($copied_objects as $_copy_data) {
                        if ($_copy_data['type'] == $sort->parent_type && $_copy_data['obj']->getId() == $sort->parent_id) {
                            $parent_id = $_copy_data['new_obj']->getId();
                            break;
                        }
                    }
                }
                $query = "INSERT INTO container_sorting (obj_id,child_id,position,parent_type,parent_id) ".
                    "VALUES( ".
                    $ilDB->quote($new_obj->getId() , 'integer').", ".
                    $ilDB->quote($copy_data['new_ref_id'] , 'integer') . ", ".
                    $ilDB->quote($sort->position, 'integer') . ", ".
                    $ilDB->quote($sort->parent_type, 'text') . ", ".
                    $ilDB->quote((int) $parent_id, 'integer').
                    ")";
                $ilDB->manipulate($query);
            }
        }
    }


    /**
     * Check if ILIAS course of corresponding hubCourse object is deleted in ILIAS
     *
     * @param hubCourse $hub_course
     * @return bool
     */
    protected function checkDeletedCourseIlias(hubCourse $hub_course)
    {
        if (is_object($hub_course)) {
            $ref_id = $hub_course->getHistoryObject()->getIliasId();
            $this->log->write("SATSyncCourses: Prüfe ref_id, ob gelöscht: ".$ref_id);
            if (ilObjCourse::_lookupDeletedDate($ref_id)) {
                $this->log->write("SATSyncCourses: Deleted Course: ".$ref_id);
                $his_object = $hub_course->getHistoryObject();
                $his_object->delete();
                $hub_course->delete();
                return true;
            }
        }
        return false;
    }

    protected function templateExists($id){
        return ilObject2::_lookupType($id, true) == 'crs';
    }
}