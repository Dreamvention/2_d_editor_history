<?php
/*
 *  location: admin/model
 */

class ModelExtensionModuleDEditorHistory extends Model {
    public $codename = 'd_editor_history';

    /**
     * Creating tables when installing the module
     */

    public function installModule(){
        $this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."deh_history` (
            `config_name` VARCHAR(256) NOT NULL,
            `id` INT(11) NOT NULL,
            `language_id` INT(11) NOT NULL,
            `field` VARCHAR(256) NOT NULL,
            `content` LONGTEXT NOT NULL,
            `date_added` DATETIME NOT NULL
            )
            COLLATE='utf8_general_ci' ENGINE=InnoDB;");
    }

    /**
     * Deleting tables when uninstalling the module
     */
    public function uninstallModule(){
        $this->db->query("DROP TABLE IF EXISTS `".DB_PREFIX."deh_history`");
    }

    /**
     * Recovers  the contents of item with the specified ID and config from before the specified date
     */

    public function restoreItem($config_name, $id, $date){
        $module_setting = $this->getModuleSetting($config_name);

        if($module_setting['multi_language']){
            $this->load->model('localisation/language');
            $languages = $this->model_localisation_language->getLanguages();
        }

        if(!empty($module_setting['multi_language'])){
            foreach ($languages as $language) {
                foreach ($module_setting['table_fields'] as $field) {
                    $data = $this->getContentForItem($config_name, $id, $date, $field, $language['language_id']);
                    if(!empty($data)){
                        $this->updateContentForItem($module_setting, $id, $field, $data['content'], $language['language_id']);
                    }
                }
            }
        }
        else{
            foreach ($module_setting['table_fields'] as $field) {
                $data = $this->getContentForItem($config_name, $id, $date, $field);
                if(!empty($data)){
                    $this->updateContentForItem($module_setting, $id, $field, $data['content']);
                }
            }
        }
    }

    /**
     * Recovers the contents of all elements of the specified config before the specified date
     */

    public function restore($config_name, $date){

        $query = $this->db->query("SELECT `id` FROM `".DB_PREFIX."deh_history` WHERE `config_name` = '".$config_name."' AND `date_added` >= STR_TO_DATE('".$this->db->escape($date)."', '%Y-%m-%d %H:%i:%s' ) GROUP BY `id`");

        if($query->num_rows){
            foreach ($query->rows as $row) {
                $this->restoreItem($config_name, $row['id'], $date);
            }
        }
    }

    /**
     * Writes the contents of an item with the specified ID and config to history
     */

    public function backupItem($config_name, $id, $date_backup = null){
        if(is_null($date_backup)){
            $date_backup = date("Y-m-d H:i:s");
        }
        $module_setting = $this->getModuleSetting($config_name);

        if(!empty($module_setting['multi_language'])){
            $sql = "SELECT ".implode(' , ', $module_setting['table_fields']).", language_id FROM `".DB_PREFIX.$module_setting['table_name']."` WHERE `".$module_setting['id']."` = '".$id."'";

            $query = $this->db->query($sql);
            
            if($query->num_rows){
                foreach ($query->rows as $row) {
                    $language_id = $row['language_id'];
                    $row_data = $row;
                    unset($row_data['language_id']);

                    foreach ($row_data as $column_name => $column_value) {
                        $this->writeContentToHistory($config_name, $id, $column_name, $column_value, $date_backup, $language_id);
                    }
                }
            }
        }
        else{
            $sql = "SELECT ".implode(' , ', $module_setting['table_fields'])." FROM `".DB_PREFIX.$module_setting['table_name']."` WHERE `".$module_setting['id']."` = '".$id."'";

            $query = $this->db->query($sql);

            if($query->num_rows){
                foreach ($query->row as $column_name => $column_value) {
                    $this->writeContentToHistory($config_name, $id, $column_name, $date_backup, $column_value);
                }
            }
        }
    }

    /**
     * Writes the contents of all items with the specified config to history
     */

    public function backup($config_name, $date){
        $module_setting = $this->getModuleSetting($config_name);

        $query = $this->db->query("SELECT ".$module_setting['id']." as id FROM `".DB_PREFIX.$module_setting['table_name']."`");

        if(!empty($query->num_rows)){
            $date_backup = date("Y-m-d H:i:s");
            foreach ($query->rows as $row) {
                $this->backupItem($config_name, $row['id'], $date_backup);
            }
        }
    }

    /**
     * Returns the available recovery dates for all configs
     */
    public function getAvailableRecoveryDates(){

        $query = $this->db->query("SELECT `config_name`, DATE_FORMAT(MIN(`date_added`), '%Y-%m-%d %H:%i') as date_start, DATE_FORMAT(MAX(`date_added`), '%Y-%m-%d %H:%i') as date_end FROM `oc_deh_history` GROUP BY `config_name` ORDER BY `config_name` ASC");

        $history_data = array();

        if($query->num_rows){
            foreach ($query->rows as $row) {
                $history_data[$row['config_name']] = array('start' => $row['date_start'], 'end' => $row['date_end']);
            }
        }
        
        return $history_data;
    }

    /**
     * Returns the available recovery dates for the specified item
     */
    public function getAvailableRecoveryDatesForItem($config_name, $id){
        $query = $this->db->query("SELECT `date_added` FROM `".DB_PREFIX."deh_history` WHERE `config_name` = '".$config_name."' AND `id` = '".$id."' ORDER BY `date_added` DESC");

        $results = array();

        if($query->num_rows){
            foreach ($query->rows as $row) {
                $results[] = $row['date_added'];
            }
        }

        return $results;
    }

    /**
     * Returns the available submodules
     */

    public function getModules(){
        $files = glob(DIR_CONFIG.$this->codename.'/*.php');
        $results = array();
        if(!empty($files)){
            foreach ($files as $file) {
                $filename = basename($file, '.php');
                $module_setting = $this->getModuleSetting($filename);

                $results[$filename] = $module_setting['name'];
            }
        }
        return $results;
    }

    /**
     * Returns the settings of the specified submodule 
     */
    public function getModuleSetting($config_name){
        $setting = array();

        if(file_exists(DIR_CONFIG.$this->codename.'/'.$config_name.'.php')){
            $_ = array();
            require(DIR_CONFIG.$this->codename.'/'.$config_name.'.php');
            $setting = $_;
        }

        $this->load->language('extension/'.$this->codename.'_module/'.$config_name);

        $setting['name'] = $this->language->get('text_title');

        return $setting;
    }

    /**
     * Prepare a link for ajax request
     */
    public function ajax($link){
        return str_replace('&amp;', '&', $link);
    }

    /**
     * Returns the contents of the specified item
     */
    protected function getContentForItem($config_name, $id, $date, $field, $language_id = false){

        $query = $this->db->query("SELECT `content`, `language_id` FROM `".DB_PREFIX."deh_history` WHERE `config_name` = '".$config_name."' AND `date_added` >= STR_TO_DATE('".$this->db->escape($date)."', '%Y-%m-%d %H:%i:%s') AND `id` = '".$id."' AND `field` = '".$field."'  ORDER BY `date_added` ASC LIMIT 1");

        return $query->row;
    }

    /**
     * Updates the contents of the specified item 
     */
    protected function updateContentForItem($module_setting, $id, $field, $content, $language_id = false){
        $sql = "UPDATE `".DB_PREFIX.$module_setting['table_name']."` SET `".$field."` = '".$this->db->escape($content)."' WHERE `".$module_setting['id']."` = '".$id."' ";

        if($module_setting['multi_language']){
            $sql .= " AND `language_id` = '".(int)$language_id."'";
        }

        $this->db->query($sql);
    }

    /**
     * Writes the specified content for the specified item to the history
     */
    protected function writeContentToHistory($config_name, $id, $field, $content, $date_backup, $language_id = false){
        
        $this->db->query("INSERT INTO `".DB_PREFIX."deh_history` SET 
            `config_name` = '".$config_name."',
            `id` = '".$id."',". 
            ($language_id?"`language_id` = '".$language_id."', ":'')."
            `content` = '".$this->db->escape($content)."',
            `field` = '".$field."',
            `date_added` = '".$date_backup."'");

        $query = $this->db->query("SELECT count(*) as total FROM `".DB_PREFIX."deh_history` deh WHERE `deh`.`config_name` = '".$config_name."' AND `field` = '".$field."' AND `deh`.`id` = '".$id."'");
        if($query->row['total'] > 20){
            $this->db->query("DELETE FROM `".DB_PREFIX."deh_history` WHERE `config_name` = '".$config_name."'AND `field` = '".$field."' AND `id` = '".$id."' ORDER BY `date_added` ASC LIMIT ".($query->row['total']-20));
        }
    }
}