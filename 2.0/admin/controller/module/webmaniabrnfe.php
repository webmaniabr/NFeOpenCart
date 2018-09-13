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
      'envio_email',
      'sefaz_env',
      'tax_class',
      'ean_barcode',
      'gtin_tributavel',
      'ncm_code',
      'cest_code',
      'cnpj_fabricante',
      'ind_escala',
      'product_source',
      'fill_address',
      'mask_fields',
      'fisco_inf',
      'cons_inf',
      'transp_include',
      'transp_method',
      'transp_rs',
      'transp_cnpj',
      'transp_ie',
      'transp_address',
      'transp_cep',
      'transp_city',
      'transp_uf',
      'carriers',
    );
    
    foreach($settings_fields as $field){
      if (isset($this->request->post[$field])) {
        $data['webmaniabrnfe_'.$field] = $this->request->post['webmaniabrnfe_'.$field];
      } else {
        $data['webmaniabrnfe_'.$field] = $this->config->get('webmaniabrnfe_'.$field);
      }
    }
    
    $this->load->model('extension/extension');
    
    //Get all installed payment methods
    $results = $this->model_extension_extension->getInstalled('payment');
    $payment_methods = array();
    
    foreach($results as $payment){
      $this->load->language('payment/'.$payment);
      $payment_methods[$payment] = $this->language->get('heading_title');
      
      if (isset($this->request->post['payment_'.$payment])) {
        $data['webmaniabrnfe_payment_'.$payment] = $this->request->post['webmaniabrnfe_payment_'.$payment];
      } else {
        $data['webmaniabrnfe_payment_'.$payment] = $this->config->get('webmaniabrnfe_payment_'.$payment);
      }
      
    }
    
    //Get all installed shipping methods
    $results = $this->model_extension_extension->getInstalled('shipping');
    $methods = array();

    foreach($results as $shipping_method){
      $this->load->language('shipping/'.$shipping_method);
      $methods[$shipping_method] = $this->language->get('heading_title');
    }


    $data['methods']         = $methods;
    $data['payment_methods'] = $payment_methods;

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

    $query_existing_column = $this->db->query("SHOW COLUMNS FROM " . DB_PREFIX . "product LIKE 'classe_imposto'");
    if($query_existing_column->num_rows == 0){
      $query = $this->db->query("ALTER TABLE  " . DB_PREFIX . "product ADD COLUMN classe_imposto VARCHAR (15), ADD COLUMN ean_barcode VARCHAR (15), ADD COLUMN ncm_code VARCHAR (15), ADD COLUMN cest_code VARCHAR (15), ADD COLUMN product_source VARCHAR (15) DEFAULT -1");
    }
    
    $query_existing_column = $this->db->query("SHOW COLUMNS FROM " . DB_PREFIX . "product LIKE 'gtin_tributavel'");
    if($query_existing_column->num_rows == 0){
      $query = $this->db->query("ALTER TABLE  " . DB_PREFIX . "product ADD COLUMN gtin_tributavel VARCHAR (15)");
    }

    $query_existing_column = $this->db->query("SHOW COLUMNS FROM " . DB_PREFIX . "product LIKE 'ignorar_nfe'");
    if($query_existing_column->num_rows == 0){
      $query = $this->db->query("ALTER TABLE  " . DB_PREFIX . "product ADD COLUMN ignorar_nfe VARCHAR (5) DEFAULT 0");
    }
    
    $query_existing_column = $this->db->query("SHOW COLUMNS FROM " . DB_PREFIX . "product LIKE 'cnpj_fabricante'");
    if($query_existing_column->num_rows == 0){
      $query = $this->db->query("ALTER TABLE  " . DB_PREFIX . "product ADD COLUMN cnpj_fabricante VARCHAR (20)");
    }
    
    $query_existing_column = $this->db->query("SHOW COLUMNS FROM " . DB_PREFIX . "product LIKE 'ind_escala'");
    if($query_existing_column->num_rows == 0){
      $query = $this->db->query("ALTER TABLE  " . DB_PREFIX . "product ADD COLUMN ind_escala VARCHAR (5)");
    }

    $query_existing_column = $this->db->query("SHOW COLUMNS FROM " . DB_PREFIX . "order LIKE 'status_nfe'");
    if($query_existing_column->num_rows == 0){
      $query = $this->db->query("ALTER TABLE  " . DB_PREFIX . "order ADD COLUMN status_nfe BOOLEAN DEFAULT 0");
    }

    $query_existing_column = $this->db->query("SHOW COLUMNS FROM " . DB_PREFIX . "order LIKE 'nfe_info'");
    if($query_existing_column->num_rows == 0){
      $query = $this->db->query("ALTER TABLE  " . DB_PREFIX . "order ADD COLUMN nfe_info TEXT");
    }
    
    $query_existing_column = $this->db->query("SHOW COLUMNS FROM " . DB_PREFIX . "order LIKE 'nfe_transporte_modalidade_frete'");
    if($query_existing_column->num_rows == 0){
      $query = $this->db->query("ALTER TABLE  " . DB_PREFIX . "order ADD COLUMN nfe_transporte_modalidade_frete VARCHAR (15)");
    }

    $query_existing_column = $this->db->query("SHOW COLUMNS FROM " . DB_PREFIX . "order LIKE 'nfe_transporte_volume'");
    if($query_existing_column->num_rows == 0){
      $query = $this->db->query("ALTER TABLE  " . DB_PREFIX . "order ADD COLUMN nfe_transporte_volume VARCHAR (15)");
    }

    $query_existing_column = $this->db->query("SHOW COLUMNS FROM " . DB_PREFIX . "order LIKE 'nfe_transporte_especie'");
    if($query_existing_column->num_rows == 0){
      $query = $this->db->query("ALTER TABLE  " . DB_PREFIX . "order ADD COLUMN nfe_transporte_especie VARCHAR (15)");
    }

    $query_existing_column = $this->db->query("SHOW COLUMNS FROM " . DB_PREFIX . "order LIKE 'nfe_transporte_peso_bruto'");
    if($query_existing_column->num_rows == 0){
      $query = $this->db->query("ALTER TABLE  " . DB_PREFIX . "order ADD COLUMN nfe_transporte_peso_bruto VARCHAR (15)");
    }

    $query_existing_column = $this->db->query("SHOW COLUMNS FROM " . DB_PREFIX . "order LIKE 'nfe_transporte_peso_liquido'");
    if($query_existing_column->num_rows == 0){
      $query = $this->db->query("ALTER TABLE  " . DB_PREFIX . "order ADD COLUMN nfe_transporte_peso_liquido VARCHAR (15)");
    }

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

  public function updateNFe(){

    if( isset($this->request->get['atualizar']) && isset($this->request->get['chave_acesso']) ){
      $chave_acesso =  $this->request->get['chave_acesso'];
      $order_id = (int) $this->request->get['order_id'];

      $response = $this->getNFe()->consultaNotaFiscal( $chave_acesso );

      if (isset($response->error)){

        $this->session->data['error_warning'] = '<p>Erro: '.$response->error.'</p>';
        return false;

      }else{

        $new_status = $response->status;
        $query_nfe_data = $this->db->query("SELECT nfe_info FROM " . DB_PREFIX . "order WHERE order_id = $order_id");
        $nfe_data = unserialize($query_nfe_data->rows[0]['nfe_info']);

        foreach($nfe_data as &$order_nfe){
          if($order_nfe['chave_acesso'] == $chave_acesso){
            $order_nfe['status'] = $new_status;
          }
        }

        $nfe_data_str = serialize($nfe_data);

        $query = $this->db->query("UPDATE " . DB_PREFIX . "order SET nfe_info = '$nfe_data_str' WHERE order_id = $order_id");
        $this->session->data['success'] = '<p><i class="fa fa-check-circle"></i> NF-e atualizada com sucesso';

        $url = new Url(HTTP_SERVER, $this->config->get('config_secure') ? HTTP_SERVER : HTTPS_SERVER);
        $this->response->redirect($url->link('sale/order/info&order_id='.$order_id, 'token=' . $this->session->data['token'], 'SSL'));

      }

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
        $response = $this->getNFe()->emissaoNotaFiscal( $data );
        if (isset($response->error) || $response->status == 'reprovado'){
          if(isset($response->error)){
            $error .= '<p><i class="fa fa-close"></i> NF-e do pedido #'.$order_id.' não emitida ( '.$response->error.' )';
          }elseif(isset($response->log->aProt[0]->xMotivo)){
            $error .= '<p><i class="fa fa-close"></i> NF-e do pedido #'.$order_id.' não emitida ( '.$response->log->aProt[0]->xMotivo.' )';
          }else{
            $error .= '<p><i class="fa fa-close"></i> NF-e do pedido #'.$order_id.' não emitida';
          }
        }else{

          $previous_info_query = $this->db->query("SELECT nfe_info FROM " . DB_PREFIX . "order WHERE order_id = $order_id");
          $previous_info = unserialize($previous_info_query->rows[0]['nfe_info']);
          if(!$previous_info){
            $previous_info = array();
          }


          $order_nfe_info = array(
            'uuid'         => (string) $response->uuid,
            'status'       => (string) $response->status,
            'chave_acesso' => $response->chave,
            'n_recibo'     => (int) $response->recibo,
            'n_nfe'        => (int) $response->nfe,
            'n_serie'      => (int) $response->serie,
            'url_xml'      => (string) $response->xml,
            'url_danfe'    => (string) $response->danfe,
            'data'         => date('d/m/Y'),
          );

          $previous_info[] = $order_nfe_info;

          $nfe_info_str = serialize($previous_info);

          $query = $this->db->query("UPDATE " . DB_PREFIX . "order SET nfe_info = '$nfe_info_str' WHERE order_id = $order_id");
          $query = $this->db->query("UPDATE " . DB_PREFIX . "order SET status_nfe = '1' WHERE order_id = $order_id");

          $success .= '<p><i class="fa fa-check-circle"></i> NF-e do pedido #'.$order_id.' emitida com sucesso';

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
  
  public function save_order_transporte_info($request){

    if(isset($request['nfe-save-transporte-info']) && isset($request['order_id'])){

      $order_id = $request['order_id'];

			$modalidade_frete = $request['webmaniabrnfe_modalidade_frete'];
			$volume           = $request['webmaniabrnfe_volume'];
			$especie          = $request['webmaniabrnfe_especie'];
			$peso_bruto       = $request['webmaniabrnfe_peso_bruto'];
			$peso_liquido     = $request['webmaniabrnfe_peso_liquido'];

			try{
				$this->db->query("UPDATE " . DB_PREFIX . "order SET nfe_transporte_modalidade_frete = '$modalidade_frete', nfe_transporte_volume = '$volume', nfe_transporte_especie = '$especie', nfe_transporte_peso_bruto = '$peso_bruto', nfe_transporte_peso_liquido = '$peso_liquido' WHERE order_id = '$order_id'");
			}catch(Exception $e){}

		}

  }

  public function get_order_transporte_info( $order_id ){

    if(!$order_id) return array();

    $data = array();

    try{

			$query_transporte_info = $this->db->query("SELECT nfe_transporte_modalidade_frete, nfe_transporte_volume, nfe_transporte_especie, nfe_transporte_peso_bruto, nfe_transporte_peso_liquido FROM " . DB_PREFIX . "order WHERE order_id = $order_id");

			$transporte_info = $query_transporte_info->row;

			$data['nfe_volume']       = $transporte_info['nfe_transporte_volume'];
			$data['nfe_especie']      = $transporte_info['nfe_transporte_especie'];
			$data['nfe_peso_bruto']   = $transporte_info['nfe_transporte_peso_bruto'];
			$data['nfe_peso_liquido'] = $transporte_info['nfe_transporte_peso_liquido'];
      $data['modalidade_frete'] = $transporte_info['nfe_transporte_modalidade_frete'];

      return $data;

		}catch(Exception $e){

			$data['nfe_volume']           = '';
			$data['nfe_especie']          = '';
			$data['nfe_peso_bruto']       = '';
			$data['nfe_peso_liquido']     = '';
      $data['nfe_modalidade_frete'] = '';

      return $data;

		}

  }

}
