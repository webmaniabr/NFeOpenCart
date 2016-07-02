<?php

class ControllerModuleWebmaniabrNfe extends Controller {

  private $error = array();
  public  $NFe = null;
  public  $module_settings = null;

  function __construct( $registry ){

      $this->registry = $registry;

      require_once (__DIR__.'/../nfe/NFe.php');
      require_once (__DIR__.'/../nfe/functions.php');

      $this->NFeFunctions = new NFeFunctions;

      if(!$this->NFeFunctions->isInstalled( $this, true )) return false;
      if(!$this->checkAuthentication()) return false;

      $this->NFe = $this->getNFe();
      $this->NFeFunctions = new NFeFunctions;

  }

  public function index() {

    $this->load->language('module/webmaniabrnfe');

    $this->document->setTitle($this->language->get('heading_title'));

    // Load the Setting Model  (All of the OpenCart Module & General Settings are saved using this Model )
    $this->load->model('setting/setting');

    // Save settings if valid
    if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
      $this->model_setting_setting->editSetting('webmaniabrnfe', $this->request->post);

      // Displays the success text on data save
      $this->session->data['success'] = $this->language->get('text_success');

      // Redirect to the Module Listing
      $this->response->redirect($this->url->link('module/webmaniabrnfe', 'token=' . $this->session->data['token'], 'SSL'));
    }

    // Assign the language data for parsing it to view
    $language_data = array(
      'heading_title'       => 'heading_title',
      'text_edit'           => 'text_edit',
      'text_enabled'        => 'text_enabled',
      'text_disabled'       => 'text_disabled',
      'text_content_top'    => 'text_content_top',
      'text_content_bottom' => 'text_content_bottom',
      'text_column_left'    => 'text_column_left',
      'text_column_right'   => 'text_column_right',
      'entry_code'          => 'entry_code',
      'entry_layout'        => 'entry_layout',
      'entry_position'      => 'entry_position',
      'entry_status'        => 'entry_status',
      'entry_sort_order'    => 'entry_sort_order',
      'button_save'         => 'button_save',
      'button_edit'         => 'button_edit',
      'button_cancel'       => 'button_cancel',
      'button_add_module'   => 'button_add_module',
      'button_remove'       => 'button_remove',
    );

    foreach($language_data as $key => $value){
      $data[$key] = $this->language->get($value);
    }

    // Add warnings
    if (isset($this->error['warning'])) {
      $data['error_warning'] = $this->error['warning'];
    } else {
      $data['error_warning'] = '';
    }

    //Add success
    if (isset($this->session->data['success'])) {
      $data['success_message'] = $this->session->data['success'];
    } else {
      $data['success_message'] = '';
    }

    // This Block returns the error code if any
    if (isset($this->error['code'])) {
      $data['error_code'] = $this->error['code'];
    } else {
      $data['error_code'] = '';
    }

    // Making of Breadcrumbs to be displayed on site
    $data['breadcrumbs'] = array();
    $data['breadcrumbs'][] = array(
      'text'      => $this->language->get('text_home'),
      'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
      'separator' => false
    );
    $data['breadcrumbs'][] = array(
      'text'      => $this->language->get('text_module'),
      'href'      => $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'),
      'separator' => ' :: '
    );
    $data['breadcrumbs'][] = array(
      'text'      => $this->language->get('heading_title'),
      'href'      => $this->url->link('module/webmaniabrnfe', 'token=' . $this->session->data['token'], 'SSL'),
      'separator' => ' :: '
    );

    $data['action'] = $this->url->link('module/webmaniabrnfe', 'token=' . $this->session->data['token'], 'SSL'); // URL to be directed when the save button is pressed

    $data['cancel'] = $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'); // URL to be redirected when cancel button is pressed

    $settings_fields = array(
      'consumer_key',
      'consumer_secret',
      'access_token',
      'access_token_secret',
      'operation_nature',
      'sefaz_env',
      'tax_class',
      'ean_barcode',
      'ncm_code',
      'cest_code',
      'product_source',
      'fill_address',
      'mask_fields',
    );
    foreach($settings_fields as $field){
      if (isset($this->request->post[$field])) {
        $data['webmaniabrnfe_'.$field] = $this->request->post['webmaniabrnfe_'.$field];
      } else {
        $data['webmaniabrnfe_'.$field] = $this->config->get('webmaniabrnfe_'.$field);
      }
    }

      $data['header'] = $this->load->controller('common/header');
      $data['column_left'] = $this->load->controller('common/column_left');
      $data['footer'] = $this->load->controller('common/footer');

      $this->response->setOutput($this->load->view('module/webmaniabrnfe.tpl', $data));

    }

    public function install(){

      // Install vqMod file
      $filename = 'nfe.ocmod.xml';
      $dest = __DIR__.'/../../../vqmod/xml/';
      $file_copy = __DIR__.'/../nfe/xml/nfe.ocmod.xml';
      $oc_mod_exist = file_exists($dest.$filename);

      if($oc_mod_exist === false){
        copy($file_copy, $dest.$filename);
      }

      //Try to insert Required custom Fields on Install
      $this->NFeFunctions->getCustomFieldsIds( $this );

      //Disable Guest Checkout
      $store_id = $this->config->get('config_store_id');
      $this->load->model('setting/setting');
      $this->model_setting_setting->editSettingValue('config', 'config_checkout_guest', 0, $store_id);

    }

    public function uninstall(){

      // Delete vqMod file
      $filepath = __DIR__.'/../../../vqmod/xml/nfe.ocmod.xml';
      $oc_mod_exist = file_exists($filepath);
      if($oc_mod_exist === true){
        unlink($filepath);
      }

    }



    /* Function that validates the data when Save Button is pressed */
    protected function validate() {

      // Block to check the user permission to manipulate the module
      if (!$this->user->hasPermission('modify', 'module/webmaniabrnfe')) {
        $this->error['warning'] = $this->language->get('error_permission');
      }

      // Block returns true if no error is found, else false if any error detected
      if (!$this->error) {
        return true;
      } else {
        return false;
      }
    }

    //Languages for custom_field_description
    public function getLanguages(){
      $languages = $this->db->query("SELECT language_id FROM " . DB_PREFIX . "language");
      return $languages;
    }

    function getModuleSettings(){
      if(is_null($this->module_settings)){
        $this->load->model('setting/setting');
        $this->module_settings = $this->model_setting_setting->getSetting('webmaniabrnfe');
      }

      return $this->module_settings;
    }

    //Check if authentication values are set in module configuration
    function checkAuthentication(){

      $settings = $this->getModuleSettings();
      $settings_arr = array(
        'webmaniabrnfe_access_token',
        'webmaniabrnfe_access_token_secret',
        'webmaniabrnfe_consumer_key',
        'webmaniabrnfe_consumer_secret',
      );

      foreach($settings_arr as $setting_val){
        if(!isset($settings[$setting_val]) || empty($settings[$setting_val])){
          $this->session->data['status_sefaz'] = '<strong>Opencart NF-e:</strong> Informe as credenciais de acesso da aplicação em Extensões > Módulos > WebmaniaBR NF-e.';
          return false;
        }
      }

      return true;

    }

    //Get NFe object and create in case it doesnt exist
    function getNFe(){

      if( !is_object($this->NFe) ){
        $module_settings = $this->getModuleSettings();
        $settings = array(
            'oauth_access_token'        => $module_settings['webmaniabrnfe_access_token'],
            'oauth_access_token_secret' => $module_settings['webmaniabrnfe_access_token_secret'],
            'consumer_key'              => $module_settings['webmaniabrnfe_consumer_key'],
            'consumer_secret'           => $module_settings['webmaniabrnfe_consumer_secret'],
        );
        $this->NFe = new NFe($settings);
      }

      return $this->NFe;

    }

    //Get order and customer info from Model and emit
    public function emitirNfe(){

      if (isset($this->request->post['selected'])) {
        $this->load->model('sale/order');
        $this->load->model('module/webmaniabrnfe');
        $success = '';
        $error = '';
        foreach ($this->request->post['selected'] as $order_id) {
          $order_info = $this->model_sale_order->getOrder($order_id);
          $products_info = $this->model_sale_order->getOrderProducts($order_id);
          $data = $this->model_module_webmaniabrnfe->getNfeInfo($order_info, $products_info);
          $response = $this->NFe->emissaoNotaFiscal( $data );
          if (isset($response->error) || $response->status == 'reprovado'){
            if(isset($response->error)){
              $error .= '<p><i class="fa fa-close"></i> NF-e do pedido #'.$order_id.' não emitida ( '.$response->error.' )';
            }elseif(isset($response->log->aProt[0]->xMotivo)){
              $error .= '<p><i class="fa fa-close"></i> NF-e do pedido #'.$order_id.' não emitida ( '.$response->log->aProt[0]->xMotivo.' )';
            }else{
              $error .= '<p><i class="fa fa-close"></i> NF-e do pedido #'.$order_id.' não emitida';
            }
          }else{
            $success .= '<p><i class="fa fa-check-circle"></i> NF-e do pedido #'.$order_id.' emitida com sucesso';
            $query = $this->db->query("UPDATE " . DB_PREFIX . "order SET status_nfe = '1' WHERE order_id = $order_id");
          }
        }

        if(strlen($error) > 0){
          $this->session->data['error_warning'] = $error;
        }
        if(strlen($success) > 0){
          $this->session->data['success'] = $success;
        }
        $this->response->redirect($this->url->link('sale/order', 'token=' . $this->session->data['token'] . $url, 'SSL'));
      }

    }

    //Get Sefaz status in case the last check was at least an hour ago
    function displayStatusSefaz(){

      if(!isset($this->session->data['sefaz_last_check'])){
        $status = $this->getNFe()->statusSefaz();
        $this->session->data['sefaz_last_check'] = time();
        if($status === false){
          $this->session->data['status_sefaz'] = 'Sefaz Offline';
        }else{
          if(isset($this->session->data['status_sefaz'])){
            unset($this->session->data['status_sefaz']);
          }
        }
      }else{
        $current = time();
        $last_check = $this->session->data['sefaz_last_check'];
        if(($current - $last_check) > 3600){
          unset($this->session->data['sefaz_last_check']);
        }
      }

    }

    //Get certificate expiration once a day
    function displayMessageCertificado(){

      if(!isset($this->session->data['certificado_last_check'])){
        $validade = $this->getNFe()->validadeCertificado();
        $this->session->data['certificado_last_check'] = time();
        if(!is_object($validade) && $validade > 1 && $validade < 45){
          $this->session->data['validade_certificado'] = 'Faltam '.$validade.' dias para expirar seu certificado digital.';
        }else{
          if(isset($this->session->data['validade_certificado'])){
            unset($this->session->data['validade_certificado']);
          }
        }
      }else{
        $current = time();
        $last_check = $this->session->data['certificado_last_check'];
        if(($current - $last_check) > 86400){
          unset($this->session->data['certificado_last_check']);
        }
      }

    }

    function is_cpf( $cpf = null ){

        return $this->NFeFunctions->is_cpf( $cpf );

    }

    function is_cnpj( $cnpj = null ){

        return $this->NFeFunctions->is_cnpj( $cnpj );

    }

    function cpf( $string = null ){

        return $this->NFeFunctions->cpf( $string );

    }

    function cnpj( $string = null ){

        return $this->NFeFunctions->cnpj( $string );

    }

    function cep( $string = null ){

        return $this->NFeFunctions->cep( $string );

    }

    function getCustomFields(){

        return $this->NFeFunctions->getCustomFields( $this );

    }

    function getCustomFieldsIds(){

        return $this->NFeFunctions->getCustomFieldsIds( $this, 'backend' );

    }

    //Check if module is installed
    function isInstalled(){

      return $this->NFeFunctions->isInstalled( $this, true );

    }

  }
