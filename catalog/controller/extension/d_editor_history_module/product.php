<?php
/*
*  location: admin/controller
*/

class ControllerExtensionDEditorHistoryModuleProduct extends Controller {

    private $codename = 'd_editor_history';


    public function __construct($registry) {
        parent::__construct($registry);
        $this->load->model('extension/module/'.$this->codename);
    }

    public function model_editProduct_before(&$route, &$data, &$output){
        $this->{'model_extension_module_'.$this->codename}->backupItem('product', $data[0]);
    }
}