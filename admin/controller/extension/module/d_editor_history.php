<?php
/*
*  location: admin/controller
*/

class ControllerExtensionModuleDEditorHistory extends Controller {

    private $codename = 'd_editor_history';
    private $route = 'extension/module/d_editor_history';
    private $config_file = 'd_editor_history';
    private $extension = array();
    private $store_id = 0;
    private $error = array();

    private $token = '';
    private $token_name = '';


    public function __construct($registry) {
        parent::__construct($registry);
        $this->load->model($this->route);
        $this->load->language($this->route);

        $this->d_shopunity = (file_exists(DIR_SYSTEM.'library/d_shopunity/extension/d_shopunity.json'));
        $this->extension = json_decode(file_get_contents(DIR_SYSTEM.'library/d_shopunity/extension/'.$this->codename.'.json'), true);
        $this->store_id = (isset($this->request->get['store_id'])) ? $this->request->get['store_id'] : 0;

        $this->token_name = VERSION >= '3.0.0.0'?'user_token':'token';
        $this->token = VERSION >= '3.0.0.0'?$this->session->data['user_token']:$this->session->data['token'];

    }

    public function required(){

        $this->document->setTitle($this->language->get('heading_title_main'));
        $data['heading_title'] = $this->language->get('heading_title_main');
        $data['text_not_found'] = $this->language->get('text_not_found');
        $data['breadcrumbs'] = array();

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->request->get['extension'] = $this->codename;
        if(VERSION >= '2.3.0.0'){
            $this->load->controller('extension/extension/module/uninstall');
        }else{
            $this->load->controller('extension/module/uninstall');
        }
        $this->response->setOutput($this->load->view('error/not_found'.(VERSION < '2.2.0.0'?'.twig':''), $data));
    }

    public function index(){
        if(!$this->d_shopunity){
            $this->response->redirect($this->url->link($this->route.'/required', 'codename=d_shopunity&'.$this->token_name.'='.$this->token, 'SSL'));
        }

        $this->load->model('extension/d_shopunity/mbooth');
        $this->model_extension_d_shopunity_mbooth->validateDependencies($this->codename);

        $this->load->model('extension/d_shopunity/ocmod');
        
        $twig_support = $this->model_extension_d_shopunity_ocmod->getModificationByName('d_twig_manager');

        if(!$twig_support){
            $this->load->model('extension/module/d_twig_manager');
            $this->model_extension_module_d_twig_manager->installCompatibility();
        }
        
        $this->load->model('setting/setting');
        $this->load->model('extension/module');
        $this->load->model('extension/d_shopunity/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {

            $this->uninstallEvents();

            if(!empty($this->request->post[$this->codename.'_status'])){
                $this->installEvents($this->request->post[$this->codename.'_setting']['use']);
            }

            $this->model_setting_setting->editSetting($this->codename, $this->request->post, $this->store_id);

            $this->session->data['success'] = $this->language->get('text_success');

            if(VERSION>='2.3.0.0'){
                $this->response->redirect($this->url->link('extension/extension', $this->token_name.'='.$this->token.'&type=module', 'SSL'));
            }
            else{
                $this->response->redirect($this->url->link('extension/module', $this->token_name.'='.$this->token, 'SSL'));
            }
            
        }

        // styles and scripts
        $this->document->addStyle('view/stylesheet/shopunity/bootstrap.css');
        
        $this->document->addScript('view/javascript/shopunity/bootstrap-switch/bootstrap-switch.min.js');
        $this->document->addStyle('view/stylesheet/shopunity/bootstrap-switch/bootstrap-switch.css');

        // Add more styles, links or scripts to the project is necessary
        $url_params = array();
        $url = '';

        if(isset($this->response->get['store_id'])){
            $url_params['store_id'] = $this->store_id;
        }

        $url = ((!empty($url_params)) ? '&' : '' ) . http_build_query($url_params);

        $this->document->setTitle($this->language->get('heading_title_main'));
        $data['heading_title'] = $this->language->get('heading_title_main');
        $data['text_edit'] = $this->language->get('text_edit');

        $data['codename'] = $this->codename;
        $data['route'] = $this->route;
        $data['version'] = $this->extension['version'];
        $data[$this->token_name] = $this->token;
        $data['d_shopunity'] = $this->d_shopunity;
        
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['text_yes'] = $this->language->get('text_yes');
        $data['text_no'] = $this->language->get('text_no');
        $data['text_default'] = $this->language->get('text_default');
        $data['text_no_results'] = $this->language->get('text_no_results');
        $data['text_confirm'] = $this->language->get('text_confirm');
        $data['text_select_all'] = $this->language->get('text_select_all');
        $data['text_unselect_all'] = $this->language->get('text_unselect_all');

        $data['entry_status'] = $this->language->get('entry_status');
        $data['entry_use_editor_history'] = $this->language->get('entry_use_editor_history');
        $data['entry_datetime'] = $this->language->get('entry_datetime');
        $data['entry_module'] = $this->language->get('entry_module');
        $data['entry_availability_date'] = $this->language->get('entry_availability_date');

        $data['button_save'] = $this->language->get('button_save');
        $data['button_save_and_stay'] = $this->language->get('button_save_and_stay');
        $data['button_cancel'] = $this->language->get('button_cancel');
        $data['button_restore'] = $this->language->get('button_restore');

        $data['module_link'] = $this->url->link($this->route, $this->token_name.'='.$this->token, 'SSL');
        $data['action'] = $this->url->link($this->route, $this->token_name.'='.$this->token . $url, 'SSL');
        $data['restore'] = $this->url->link($this->route.'/restore', $this->token_name.'='.$this->token . $url, 'SSL');
        
        if(VERSION>='2.3.0.0'){
            $data['cancel'] = $this->url->link('extension/extension', $this->token_name.'='.$this->token.'&type=module', 'SSL');
        }
        else{
            $data['cancel'] = $this->url->link('extension/module', $this->token_name.'='.$this->token, 'SSL');
        }

        if (isset($this->request->post[$this->codename.'_status'])) {
            $data[$this->codename.'_status'] = $this->request->post[$this->codename.'_status'];
        } else {
            $data[$this->codename.'_status'] = $this->config->get($this->codename.'_status');
        }

        //get store
        $data['store_id'] = $this->store_id;
        $data['stores'] = $this->model_extension_d_shopunity_setting->getStores();

        //get setting
        $data['setting'] = $this->model_extension_d_shopunity_setting->getSetting($this->codename);

        $this->load->model('setting/store');


        // Breadcrumbs
        $data['breadcrumbs'] = array(); 
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home', $this->token_name.'='.$this->token, 'SSL')
            );
        if(VERSION>='2.3.0.0'){
            $data['breadcrumbs'][] = array(
                'text'      => $this->language->get('text_module'),
                'href'      => $this->url->link('extension/extension', $this->token_name.'='.$this->token.'&type=module', 'SSL')
                );
        }
        else{
            $data['breadcrumbs'][] = array(
                'text'      => $this->language->get('text_module'),
                'href'      => $this->url->link('extension/module', $this->token_name.'='.$this->token, 'SSL')
                );
        }
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title_main'),
            'href' => $this->url->link($this->route, $this->token_name.'='.$this->token . $url, 'SSL')
            );

        foreach($this->error as $key => $error){
            $data['error'][$key] = $error;
        }

        if(!empty($this->session->data['success'])){
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        }

        $data['modules'] = $this->{'model_extension_module_'.$this->codename}->getModules();

        $data['time_modules'] = $this->{'model_extension_module_'.$this->codename}->getAvailableRecoveryDates();

        array_walk($data['time_modules'], function(&$value, $key){
            $module_setting = $this->{'model_extension_module_'.$this->codename}->getModuleSetting($key);
            $value['name'] = $module_setting['name'];
        });

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view($this->route.(VERSION < '2.2.0.0'?'.twig':''), $data));
    }

    public function installEvents($status){
        $this->load->model('extension/d_shopunity/event');
        foreach ($status as $value) {
            $module_setting = $this->{'model_extension_module_'.$this->codename}->getModuleSetting($value);
            if(!empty($module_setting['events'])){
                foreach ($module_setting['events'] as $trigger => $action) {
                    $this->model_extension_d_shopunity_event->addEvent($this->codename, $trigger, $action);
                }
            }
        }
    }

    public function uninstallEvents(){
        $this->load->model('extension/d_shopunity/event');
        $this->model_extension_d_shopunity_event->deleteEvent($this->codename);
    }

    public function restore(){
        if(isset($this->request->post['datetime'])){
            $datetime = $this->request->post['datetime'];
        }

        if(isset($this->request->post['module'])){
            $module = $this->request->post['module'];
        }

        if(isset($module) && isset($datetime)){
            $this->{'model_extension_module_'.$this->codename}->restore($module, $datetime);
            $this->session->data['success'] = $this->language->get('text_restore_success');
        }

        $this->response->redirect($this->url->link($this->route, $this->token_name.'='.$this->token, 'SSL'));
    }

    public function install_event_support(){
        if (!$this->user->hasPermission('modify', $this->route)) {
            $this->session->data['error'] = $this->language->get('error_permission');
            $this->response->redirect($this->url->link($this->route, $this->token_name.'='.$this->token, 'SSL'));
        }
        if(file_exists(DIR_SYSTEM.'mbooth/extension/d_event_manager.json')){
            $this->load->model('module/d_event_manager');
            $this->model_module_d_event_manager->installCompatibility();
        }
        $this->response->redirect($this->url->link($this->route, $this->token_name.'='.$this->token, 'SSL'));
    }

    public function restoreItem(){
        $json = array();

        if(isset($this->request->post['datetime'])){
            $datetime = $this->request->post['datetime'];
        }

        if(isset($this->request->post['config_name'])){
            $config_name = $this->request->post['config_name'];
        }

        if(isset($this->request->post['id'])){
            $id = $this->request->post['id'];
        }

        if(isset($id) && isset($config_name) && isset($datetime)){
            $this->{'model_extension_module_'.$this->codename}->restoreItem($config_name, $id, $datetime);
            $json['success'] = 'success';
        }
        else{
            $json['error'] = 'error';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }


    private function validate($permission = 'modify') {

        if (isset($this->request->post['config'])) {
            return false;
        }

        $this->language->load($this->route);

        if (!$this->user->hasPermission($permission, $this->route)) {
            $this->error['warning'] = $this->language->get('error_permission');
            return false;
        }

        return true;
    }

    public function install() {
        if($this->d_shopunity){
            $this->load->model('extension/d_shopunity/mbooth');
            $this->model_extension_d_shopunity_mbooth->installDependencies($this->codename);
        }

        $this->{'model_extension_module_'.$this->codename}->installModule();
    }

    public function uninstall(){
        $this->uninstallEvents();

        $this->{'model_extension_module_'.$this->codename}->uninstallModule();
    }
}