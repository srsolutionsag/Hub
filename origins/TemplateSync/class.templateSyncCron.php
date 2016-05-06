<?php

/**
 * Class templateSyncCron
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class templateSyncCron{

    const DELIMITER = ';';

    /**
     * @var ilDB
     */
    protected $db;

    /**
     * @var hubLog
     */
    protected $log;

    /**
     * Field columns inside CSV (correct order!!!)
     *
     * @var array
     */
    protected static $fields = array(
        'client_id',
        'id',
        'title',
        'start_date',
        'end_date',
        'category',
        'description',
        'days_before',
        'days_after',
    );


    function __construct(){
        global $ilDB;
        $this->db = $ilDB;
        $this->log = hubLog::getInstance();
    }

    public static function initAndRun()
    {
        require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/class.hub.php');
        hub::initILIAS();
        $cronJob = new self();
        $cronJob->run();
    }

    public function run(){
        global $tree;

        require_once './Services/Object/classes/class.ilObject2.php';
        $this->log->write('Start synchronisation of templates');
        $config = parse_ini_file('config.ini');
        $client_id = $this->buildClientId($config);
        $file = $config['file_path'] . 'Vorlagenliste_' . $client_id . '.csv';
        if (!is_dir($config['file_path'])) {
            ilUtil::makeDirParents($config['file_path']);
        }
        $sql = $this->buildQuery();
        $query = $this->db->query($sql);
        $this->log->write('templates found: ' . $this->db->numRows($query));
        if (($handle = fopen($file, 'w')) !== false)
        {
            $this->log->write('start writing to file: ' . $file);
            while ($res = $this->db->fetchAssoc($query))
            {
                if(!$res['validity_unlimited']) {
                    if (time() < $res['valid_from'] || time() > $res['valid_to']) {
                        $this->log->write("skipped template with ref_id {$res['ref_id']}: template is not valid at this moment");
                        continue;
                    }
                }

                $parent = $tree->getParentId($res['ref_id']);
                while ($tree->getParentId($parent) != 1) {
                    $parent = $tree->getParentId($parent);
                }
                $obj_id = ilObject2::_lookupObjId($parent);
                $res['category'] = ilObject2::_lookupTitle($obj_id);

                unset($res['validity_unlimited']);
                if (sizeof($res) == sizeof(self::$fields))
                {
                    $res['client_id'] = $client_id;
                    $res['valid_from'] = $res['valid_from'] ? date("Y-m-d", $res['valid_from']) : 'unlimited';
                    $res['valid_to'] = $res['valid_to'] ? date("Y-m-d", $res['valid_to']) : 'unlimited';
                    $res['days_before'] = $res['days_before'] ? $res['days_before'] : 0;
                    $res['days_after'] = $res['days_after'] ? $res['days_after'] : 0;
                    $this->log->write("write to file -> template: {$res['title']}, ref_id: {$res['ref_id']}");
                    fputcsv($handle, $res, self::DELIMITER);
                }else{
                    $this->log->write("skipped template: number of columns didn't match");
                }
            }
            $this->log->write('Templates synchronisation finished successfully');
        }else{
            $this->log->write("FAILURE: could not open file {$file} for writing");
        }
    }

    /**
     * @param $ilDB
     * @return string
     */
    protected function buildQuery()
    {
        $sql = "SELECT '' AS client_id, object_reference.ref_id, object_data.title, crs_items.timing_start AS valid_from,
                  crs_items.timing_end AS valid_to, crs_items.timing_type AS validity_unlimited,
                  object_data.description, values_before.value AS days_before, values_after.value AS days_after
                FROM il_meta_keyword
                INNER JOIN object_reference ON object_reference.obj_id = il_meta_keyword.obj_id
                INNER JOIN object_data ON object_data.obj_id = il_meta_keyword.obj_id
                INNER JOIN crs_items ON crs_items.obj_id = object_reference.ref_id
                INNER JOIN crs_settings ON crs_settings.obj_id = il_meta_keyword.obj_id
                INNER JOIN adv_mdf_definition AS definition_before ON definition_before.title = " . $this->db->quote('DAYS_BEFORE', 'text') . "
                LEFT JOIN adv_md_values AS values_before ON values_before.obj_id = object_reference.obj_id AND values_before.field_id = definition_before.field_id
                INNER JOIN adv_mdf_definition AS definition_after ON definition_after.title = " . $this->db->quote('DAYS_AFTER', 'text') . "
                LEFT JOIN adv_md_values AS values_after ON values_after.obj_id = object_reference.obj_id AND values_after.field_id = definition_after.field_id
                WHERE il_meta_keyword.keyword = " . $this->db->quote('ARTEMIS_TEMPLATE', 'text') . "
                    AND object_reference.deleted IS NULL";
        return $sql;
    }

    /**
     * @param $config
     * @return string
     */
    protected function buildClientId($config)
    {
        $client_id = hubConfig::get(hubConfig::F_ASYNC_CLIENT);
        if (!$client_id) {
            $this->log->write("WARNING: no client_id defined in hub-configuration. Set default to: {$config['default_client_id']}");
            $client_id = $config['default_client_id'];
            return $client_id;
        }
        return $client_id;
    }
}