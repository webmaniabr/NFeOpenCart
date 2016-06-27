<?php
class ControllerModuleWebmaniabrNfe extends Controller {
  private $error = array();

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

      //Disable Guest Checkout
      $store_id = $this->config->get('config_store_id');
      $this->load->model('setting/setting');
      $this->model_setting_setting->editSettingValue('config', 'config_checkout_guest', 0, $store_id);

      //Insert Columns in DB
      $query_existing_column = $this->db->query("SHOW COLUMNS FROM " . DB_PREFIX . "customer LIKE 'document_type'");
      if($query_existing_column->num_rows == 0){
        $query = $this->db->query("ALTER TABLE  " . DB_PREFIX . "customer ADD COLUMN document_type VARCHAR ( 5 )");
      }

      $query_existing_column = $this->db->query("SHOW COLUMNS FROM " . DB_PREFIX . "customer LIKE 'document_number'");
      if($query_existing_column->num_rows == 0){
        $query = $this->db->query("ALTER TABLE  " . DB_PREFIX . "customer ADD COLUMN document_number VARCHAR( 14 ) NOT NULL");
      }

      $query_existing_column = $this->db->query("SHOW COLUMNS FROM " . DB_PREFIX . "customer LIKE 'pj_ie'");
      if($query_existing_column->num_rows == 0){
        $query = $this->db->query("ALTER TABLE  " . DB_PREFIX . "customer ADD COLUMN pj_ie VARCHAR( 14 ) DEFAULT 0");
      }

      $query_existing_column = $this->db->query("SHOW COLUMNS FROM " . DB_PREFIX . "customer LIKE 'razao_social'");
      if($query_existing_column->num_rows == 0){
        $query = $this->db->query("ALTER TABLE  " . DB_PREFIX . "customer ADD COLUMN razao_social VARCHAR( 50 ) NOT NULL");
      }

      $query_existing_column = $this->db->query("SHOW COLUMNS FROM " . DB_PREFIX . "order LIKE 'status_nfe'");
      if($query_existing_column->num_rows == 0){
        $query = $this->db->query("ALTER TABLE  " . DB_PREFIX . "order ADD COLUMN status_nfe BOOLEAN DEFAULT 0");
      }

      $query_existing_column = $this->db->query("SHOW COLUMNS FROM " . DB_PREFIX . "address LIKE 'address_number'");
      if($query_existing_column->num_rows == 0){
        $query = $this->db->query("ALTER TABLE  " . DB_PREFIX . "address ADD COLUMN address_number TEXT");
      }

      $query_existing_column = $this->db->query("SHOW COLUMNS FROM " . DB_PREFIX . "product LIKE 'classe_imposto'");
      if($query_existing_column->num_rows == 0){
        $query = $this->db->query("ALTER TABLE  " . DB_PREFIX . "product ADD COLUMN classe_imposto VARCHAR (15), ADD COLUMN ean_barcode VARCHAR (15), ADD COLUMN ncm_code VARCHAR (15), ADD COLUMN cest_code VARCHAR (15), ADD COLUMN product_source VARCHAR (15) DEFAULT -1");
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
          $response = $this->emissaoNotaFiscal( $data );
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

    function statusSefaz($data = ''){
      $response = self::connect_webmaniabr( 'GET', 'https://webmaniabr.com/api/1/nfe/sefaz/', $data );
      if (isset($response->error)) return $response;
      if ($response->status == 'online') return true;
      else return false;
    }

    function validadeCertificado($data = ''){
      $response = self::connect_webmaniabr('GET', 'https://webmaniabr.com/api/1/nfe/certificado/', $data);
      if (isset($response->error)) return $response;
      return $response->expiration;
    }

    //Get Sefaz status in case the last check was at least an hour ago
    function displayStatusSefaz(){
      if(!isset($this->session->data['sefaz_last_check'])){
        $status = $this->statusSefaz();
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
        $validade = $this->validadeCertificado();
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

    function emissaoNotaFiscal( array $data ){
      $response = self::connect_webmaniabr( 'POST', 'https://webmaniabr.com/api/1/nfe/emissao/', $data );
      return $response;
    }

    function consultaNotaFiscal( $chave ){
      $data = array();
      $data['chave'] = $chave;
      $response = self::connect_webmaniabr( 'GET', 'https://webmaniabr.com/api/1/nfe/consulta/', $data );
      return $response;
    }

    function cancelarNotaFiscal( $chave, $motivo ){
      $data = array();
      $data['chave'] = $chave;
      $data['motivo'] = $motivo;
      $response = self::connect_webmaniabr( 'PUT', 'https://webmaniabr.com/api/1/nfe/cancelar/', $data );
      return $response;
    }

    function inutilizarNumeracao( $sequencia, $motivo ){
      $data = array();
      $data['sequencia'] = $sequencia;
      $data['motivo'] = $motivo;
      $response = self::connect_webmaniabr( 'PUT', 'https://webmaniabr.com/api/1/nfe/inutilizar/', $data );
      return $response;
    }

    function connect_webmaniabr( $request, $endpoint, $data ){
      $this->load->model('setting/setting');
      $module_settings = $this->model_setting_setting->getSetting('webmaniabrnfe');
      if(!isset($module_settings['webmaniabrnfe_consumer_key'])){
        $module_settings['webmaniabrnfe_consumer_key'] = '';
      }

      if(!isset($module_settings['webmaniabrnfe_consumer_secret'])){
        $module_settings['webmaniabrnfe_consumer_secret'] = '';
      }

      if(!isset($module_settings['webmaniabrnfe_access_token'])){
        $module_settings['webmaniabrnfe_access_token'] = '';
      }

      if(!isset($module_settings['webmaniabrnfe_access_token_secret'])){
        $module_settings['webmaniabrnfe_access_token_secret'] = '';
      }

      @set_time_limit( 300 );
      ini_set('max_execution_time', 300);
      ini_set('max_input_time', 300);
      ini_set('memory_limit', '256M');

      $headers = array(
        'Cache-Control: no-cache',
        'Content-Type:application/json',
        'X-Consumer-Key: '.$module_settings['webmaniabrnfe_consumer_key'],
        'X-Consumer-Secret: '.$module_settings['webmaniabrnfe_consumer_secret'],
        'X-Access-Token: '.$module_settings['webmaniabrnfe_access_token'],
        'X-Access-Token-Secret: '.$module_settings['webmaniabrnfe_access_token_secret']
      );


      $rest = curl_init();
      curl_setopt($rest, CURLOPT_CONNECTTIMEOUT , 300);
      curl_setopt($rest, CURLOPT_TIMEOUT, 300);
      curl_setopt($rest, CURLOPT_URL, $endpoint.'?time='.time());
      curl_setopt($rest, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($rest, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($rest, CURLOPT_SSL_VERIFYHOST, false);
      curl_setopt($rest, CURLOPT_CUSTOMREQUEST, $request);
      curl_setopt($rest, CURLOPT_POSTFIELDS, json_encode( $data ));
      curl_setopt($rest, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($rest, CURLOPT_FRESH_CONNECT, true);
      $response = curl_exec($rest);
      curl_close($rest);

      return json_decode($response);

    }
  }
