<?php
/*
*  location: admin/controller
*/

class ControllerExtensionDEditorHistoryModuleProduct extends Controller {

    private $codename = 'd_editor_history';
    private $route = 'extension/d_editor_history_module/product';

    public function __construct($registry) {
        parent::__construct($registry);
        $this->load->model('extension/module/'.$this->codename);
        $this->load->language($this->route);
    }
    
    public function model_editProduct_before(&$route, &$data){
        $this->{'model_extension_module_'.$this->codename}->backupItem('product', $data[0]);
    }

    public function view_product_form_after(&$route, &$data, &$output){

        if(!isset($this->request->get['product_id'])){
            return;
        }

        $this->load->model('extension/d_opencart_patch/url');
        $this->load->model('extension/d_opencart_patch/load');

        $html_dom = new d_simple_html_dom();
        $html_dom->load((string)$output, $lowercase = true, $stripRN = false, $defaultBRText = DEFAULT_BR_TEXT);
        
        $data['times'] = $this->{'model_extension_module_'.$this->codename}->getAvailableRecoveryDatesForItem('product', $this->request->get['product_id']);

        array_walk($data['times'], function(&$v, &$k){
            $v['name'] = $v['date_added'];

            if($v['draft']){
                $v['name'] .= ' ('.$this->language->get('text_draft').')';
            }
        });

        $this->load->model('localisation/language');

        $data['languages'] = $this->model_localisation_language->getLanguages();

        $data['text_title_restore'] = $this->language->get('text_title_restore');
        $data['text_empty_history'] = $this->language->get('text_empty_history');

        $data['button_restore'] = $this->language->get('button_restore');

        $data['entry_date'] = $this->language->get('entry_date');

        $data['product_id'] = $this->request->get['product_id'];

        $data['restore_url'] = str_replace('&amp;', '&', $this->model_extension_d_opencart_patch_url->link('extension/module/'.$this->codename.'/restoreItem'));
        $data['draft_url'] = str_replace('&amp;', '&', $this->model_extension_d_opencart_patch_url->link('extension/module/'.$this->codename.'/draftItem'));

        $html_dom->find('#content > div.page-header > div.container-fluid > div.pull-right', 0)->innertext = $this->model_extension_d_opencart_patch_load->view($this->route, $data).$html_dom->find('#content > div.page-header > div.container-fluid > div.pull-right', 0)->innertext;
        $output = (string)$html_dom;
    }

    public function view_vd_frontend_after(&$route, &$data, &$output){

        if($this->request->get['route_config'] != 'product'){
            return;
        }

        $this->load->model('extension/d_opencart_patch/url');
        $this->load->model('extension/d_opencart_patch/load');

        $html_dom = new d_simple_html_dom();
        $html_dom->load((string)$output, $lowercase = true, $stripRN = false, $defaultBRText = DEFAULT_BR_TEXT);
        
        $data['times'] = $this->{'model_extension_module_'.$this->codename}->getAvailableRecoveryDatesForItem('product', $this->request->get['id']);

        if(empty($data['times'])){
            return;
        }

        array_walk($data['times'], function(&$v, &$k){
            $v['name'] = $v['date_added'];

            if($v['draft']){
                $v['name'] .= ' ('.$this->language->get('text_draft').')';
            }
        });

        $data['text_title_restore'] = $this->language->get('text_title_restore');

        $data['button_restore'] = $this->language->get('button_restore');

        $data['entry_date'] = $this->language->get('entry_date');

        $data['product_id'] = $this->request->get['id'];

        $data['restore_url'] = str_replace('&amp;', '&', $this->model_extension_d_opencart_patch_url->link('extension/module/'.$this->codename.'/restoreItem'));

        $html_dom->find('.vd-navbar', 0)->innertext .= $this->model_extension_d_opencart_patch_load->view($this->route.'_visual_designer', $data);
        $output = (string)$html_dom;
    }
}