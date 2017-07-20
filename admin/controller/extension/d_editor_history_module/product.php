<?php
/*
*  location: admin/controller
*/

class ControllerExtensionDEditorHistoryModuleProduct extends Controller {

    private $codename = 'd_editor_history';
    private $route = 'extension/d_editor_history_module/product';

    private $token = '';
    private $token_name = '';


    public function __construct($registry) {
        parent::__construct($registry);
        $this->load->model('extension/module/'.$this->codename);
        $this->load->language($this->route);

        $this->token_name = VERSION >= '3.0.0.0'?'user_token':'token';
        $this->token = VERSION >= '3.0.0.0'?$this->session->data['user_token']:$this->session->data['token'];
    }

    public function model_editProduct_before(&$route, &$data, &$output){
        $this->{'model_extension_module_'.$this->codename}->backupItem('product', $data[0]);
    }

    public function view_product_form_after(&$route, &$data, &$output){
        if(!isset($this->request->get['product_id'])){
            return;
        }
        $html_dom = new d_simple_html_dom();
        $html_dom->load((string)$output, $lowercase = true, $stripRN = false, $defaultBRText = DEFAULT_BR_TEXT);
        
        $data['times'] = $this->{'model_extension_module_'.$this->codename}->getAvailableRecoveryDatesForItem('product', $this->request->get['product_id']);

        if(empty($data['times'])){
            return;
        }

        $data['text_title_restore'] = $this->language->get('text_title_restore');

        $data['button_restore'] = $this->language->get('button_restore');

        $data['entry_date'] = $this->language->get('entry_date');

        $data['product_id'] = $this->request->get['product_id'];

        $data['restore_url'] = str_replace('&amp;', '&', $this->url->link('extension/module/'.$this->codename.'/restoreItem', $this->token_name.'='.$this->token, 'SSL'));

        $html_dom->find('#content > div.page-header > div.container-fluid > div.pull-right', 0)->innertext = $this->load->view($this->route.(VERSION < '2.2.0.0'?'.twig':''), $data).$html_dom->find('#content > div.page-header > div.container-fluid > div.pull-right', 0)->innertext;
        $output = (string)$html_dom;
    }

    public function view_vd_frontend_after(&$route, &$data, &$output){
        if($this->request->get['route_config'] != 'product'){
            return;
        }

        $html_dom = new d_simple_html_dom();
        $html_dom->load((string)$output, $lowercase = true, $stripRN = false, $defaultBRText = DEFAULT_BR_TEXT);
        
        $data['times'] = $this->{'model_extension_module_'.$this->codename}->getAvailableRecoveryDatesForItem('product', $this->request->get['id']);

        if(empty($data['times'])){
            return;
        }

        $data['text_title_restore'] = $this->language->get('text_title_restore');

        $data['button_restore'] = $this->language->get('button_restore');

        $data['entry_date'] = $this->language->get('entry_date');

        $data['product_id'] = $this->request->get['id'];

        $data['restore_url'] = str_replace('&amp;', '&', $this->url->link('extension/module/'.$this->codename.'/restoreItem', $this->token_name.'='.$this->token, 'SSL'));

        $html_dom->find('.vd-navbar', 0)->innertext .= $this->load->view($this->route.'_visual_designer'.(VERSION < '2.2.0.0'?'.twig':''), $data);
        $output = (string)$html_dom;
    }
}