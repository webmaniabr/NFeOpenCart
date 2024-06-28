<?php

class ControllerExtensionModuleWebmaniaBRNFe extends Controller {

  private $error = array();
  public  $NFe = null;
  public  $module_settings = null;

  function __construct( $registry ){

    $this->registry = $registry;

    require_once (__DIR__.'/../nfe/NFe.php');
    require_once (__DIR__.'/../nfe/functions.php');
    require_once (__DIR__.'/../pdf/PDFMerger.php');

    $this->NFeFunctions = new NFeFunctions;

    $this->cleanCache();
    if(!$this->NFeFunctions->isInstalled( $this, true )) return false;
    if(!$this->checkAuthentication()) return false;

    $this->NFe = $this->getNFe();
    $this->NFeFunctions = new NFeFunctions;

  }

  public function index() {

    $this->load->language('extension/module/webmaniabrnfe');

    $this->document->setTitle($this->language->get('heading_title'));

    // Load the Setting Model  (All of the OpenCart Module & General Settings are saved using this Model )
    $this->load->model('setting/setting');

    // Save settings if valid
    if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
      $this->model_setting_setting->editSetting('webmaniabrnfe', $this->request->post);

      // Displays the success text on data save
      $this->session->data['success'] = $this->language->get('text_success');

      // Redirect to the Module Listing
      $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
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
      'href'      => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], 'SSL'),
      'separator' => false
    );
    $data['breadcrumbs'][] = array(
      'text'      => $this->language->get('text_extension'),
      'href'      => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], 'SSL'),
      'separator' => ' :: '
    );
    $data['breadcrumbs'][] = array(
      'text'      => $this->language->get('heading_title'),
      'href'      => $this->url->link('extension/module/webmaniabrnfe', 'user_token=' . $this->session->data['user_token'], 'SSL'),
      'separator' => ' :: '
    );

    $data['action'] = $this->url->link('extension/module/webmaniabrnfe', 'user_token=' . $this->session->data['user_token'], 'SSL'); // URL to be directed when the save button is pressed

    $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', 'SSL'); // URL to be redirected when cancel button is pressed

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
      'intermediador',
      'intermediador_cnpj',
      'intermediador_id',
      'fill_address',
      'mask_fields',
      'fisco_inf',
      'cons_inf',
      'transp_include',
      'carriers',
      
    );

    foreach($settings_fields as $field){

      if (isset($this->request->post[$field])) {
        $data['webmaniabrnfe_'.$field] = $this->request->post['webmaniabrnfe_'.$field];
      } else {
        $data['webmaniabrnfe_'.$field] = $this->config->get('webmaniabrnfe_'.$field);
      }

    }

    $data['webmaniabrnfe_carriers_d'] = json_decode(stripslashes(html_entity_decode($data['webmaniabrnfe_carriers'])), true);

    $data['header'] = $this->load->controller('common/header');
    $data['column_left'] = $this->load->controller('common/column_left');
    $data['footer'] = $this->load->controller('common/footer');

    //Load shipping methods

    $this->load->model('setting/extension');
    
    //Get all installed payment methods
    $results = $this->model_setting_extension->getInstalled('payment');
    
    $payment_methods = array();
    
    foreach($results as $payment){
      $this->load->language('extension/payment/'.$payment);
      $payment_methods[$payment] = $this->language->get('heading_title');
      
      if (isset($this->request->post['payment_'.$payment])) {
        $data['webmaniabrnfe_payment_'.$payment] = $this->request->post['webmaniabrnfe_payment_'.$payment];
        $data['webmaniabrnfe_payment_'.$payment.'_desc'] = $this->request->post['webmaniabrnfe_payment_'.$payment.'_desc'];
      } else {
        $data['webmaniabrnfe_payment_'.$payment] = $this->config->get('webmaniabrnfe_payment_'.$payment);
        $data['webmaniabrnfe_payment_'.$payment.'_desc'] = $this->config->get('webmaniabrnfe_payment_'.$payment.'_desc');
      }
      
    }
    
    //Get all installed shipping methods
    $results = $this->model_setting_extension->getInstalled('shipping');
    $methods = array();

    foreach($results as $shipping_method){
      $this->load->language('extension/shipping/'.$shipping_method);
      $methods[$shipping_method] = $this->language->get('heading_title');
    }


    $data['methods']         = $methods;
    $data['payment_methods'] = $payment_methods;

    //Reload module language
    $this->load->language('extension/module/webmaniabrnfe');

    $this->response->setOutput($this->load->view('extension/module/webmaniabrnfe', $data));

  }

  /**
   * Clean cache vqMod
   */
  public function cleanCache(){

    if (
      $_GET &&
      $_GET['route'] &&
      in_array($_GET['route'], [ 
        'catalog/product/edit',
        'sale/order/info',
        'sale/order/edit',
        'module/webmaniabrnfe'
      ])
    ){

      $store_id = $this->config->get('config_store_id');
      $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE store_id = '$store_id' AND code = 'webmaniabr_cache'");

      if ($query->num_rows > 0){

        $cached = $query->row['value'];

        if ($cached == $cached){

          // Clean files
          $files = glob(__DIR__.'/../../../vqmod/vqcache/*');
          foreach($files as $file){
            if (is_file($file))
              unlink($file);
          }

          // Update cache
          $cached++;
          $id = $query->row['setting_id'];
          $this->db->query("UPDATE " . DB_PREFIX . "setting SET value = '$cached' WHERE setting_id = '$id'");

        }

      }

    }

  }

  /**
   * Install
   */
  public function install(){

    // Install vqMod file
    $filename = 'nfe.ocmod.xml';
    $dest = __DIR__.'/../../../../vqmod/xml/';
    $file_copy = __DIR__.'/../nfe/xml/nfe.ocmod.xml';
    $oc_mod_exist = file_exists($dest.$filename);

    // Copy vqMod
    if ($oc_mod_exist === false){
      copy($file_copy, $dest.$filename);
    }

    // Try to insert Required custom Fields on Install
    $this->getCustomFieldsIds( $this );

    // Disable Guest Checkout
    $store_id = $this->config->get('config_store_id');
    $this->load->model('setting/setting');
    $this->model_setting_setting->editSettingValue('config', 'config_checkout_guest', 0, $store_id);

    // Install custom fields
    $query_existing_column = $this->db->query("SHOW COLUMNS FROM " . DB_PREFIX . "category LIKE 'category_ncm'");
    if($query_existing_column->num_rows == 0){
      $query = $this->db->query("ALTER TABLE  " . DB_PREFIX . "category ADD COLUMN category_ncm VARCHAR (15)");
    }

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

    $order_table = (DB_PREFIX) ? "order" : "`order`";
    $query_existing_column = $this->db->query("SHOW COLUMNS FROM " . DB_PREFIX . "$order_table LIKE 'status_nfe'");
    if($query_existing_column->num_rows == 0){
      $query = $this->db->query("ALTER TABLE  " . DB_PREFIX . "$order_table ADD COLUMN status_nfe BOOLEAN DEFAULT 0");
    }

    $query_existing_column = $this->db->query("SHOW COLUMNS FROM " . DB_PREFIX . "$order_table LIKE 'nfe_info'");
    if($query_existing_column->num_rows == 0){
      $query = $this->db->query("ALTER TABLE  " . DB_PREFIX . "$order_table ADD COLUMN nfe_info TEXT");
    }

    $query_existing_column = $this->db->query("SHOW COLUMNS FROM " . DB_PREFIX . "$order_table LIKE 'nfe_transporte_modalidade_frete'");
    if($query_existing_column->num_rows == 0){
      $query = $this->db->query("ALTER TABLE  " . DB_PREFIX . "$order_table ADD COLUMN nfe_transporte_modalidade_frete VARCHAR (15)");
    }

    $query_existing_column = $this->db->query("SHOW COLUMNS FROM " . DB_PREFIX . "$order_table LIKE 'nfe_transporte_volume'");
    if($query_existing_column->num_rows == 0){
      $query = $this->db->query("ALTER TABLE  " . DB_PREFIX . "$order_table ADD COLUMN nfe_transporte_volume VARCHAR (15)");
    }

    $query_existing_column = $this->db->query("SHOW COLUMNS FROM " . DB_PREFIX . "$order_table LIKE 'nfe_transporte_especie'");
    if($query_existing_column->num_rows == 0){
      $query = $this->db->query("ALTER TABLE  " . DB_PREFIX . "$order_table ADD COLUMN nfe_transporte_especie VARCHAR (15)");
    }

    $query_existing_column = $this->db->query("SHOW COLUMNS FROM " . DB_PREFIX . "$order_table LIKE 'nfe_transporte_peso_bruto'");
    if($query_existing_column->num_rows == 0){
      $query = $this->db->query("ALTER TABLE  " . DB_PREFIX . "$order_table ADD COLUMN nfe_transporte_peso_bruto VARCHAR (15)");
    }

    $query_existing_column = $this->db->query("SHOW COLUMNS FROM " . DB_PREFIX . "$order_table LIKE 'nfe_transporte_peso_liquido'");
    if($query_existing_column->num_rows == 0){
      $query = $this->db->query("ALTER TABLE  " . DB_PREFIX . "$order_table ADD COLUMN nfe_transporte_peso_liquido VARCHAR (15)");
    }

    // Cache
    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE store_id = '$store_id' AND code = 'webmaniabr_cache'");
    if ($query->num_rows > 0){
      $this->db->query("UPDATE " . DB_PREFIX . "setting SET value = '0' WHERE setting_id = '$id'");
    } else {
      $this->db->query("INSERT INTO " . DB_PREFIX . "setting SET store_id = '$store_id', code = 'webmaniabr_cache', value = '0', serialized = '0'");
    }

  }

  public function uninstall(){

    // Delete vqMod file
    $filepath = __DIR__.'/../../../../vqmod/xml/nfe.ocmod.xml';
    $oc_mod_exist = file_exists($filepath);
    if($oc_mod_exist === true){
      unlink($filepath);
    }

  }

  /* Function that validates the data when Save Button is pressed */
  protected function validate() {

    // Block to check the user permission to manipulate the module
    if (!$this->user->hasPermission('modify', 'extension/module/webmaniabrnfe')) {
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

      $map = array(
        'oauth_access_token'        => 'webmaniabrnfe_access_token',
        'oauth_access_token_secret' => 'webmaniabrnfe_access_token_secret',
        'consumer_key'              => 'webmaniabrnfe_consumer_key',
        'consumer_secret'           => 'webmaniabrnfe_consumer_secret'
      );

      $settings = array();

      foreach($map as $oauth => $setting_key){
        if(isset($module_settings[$setting_key])){
          $settings[$oauth] = $module_settings[$setting_key];
        }else{
          $settings[$oauth] = '';
        }
      }

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
        $order_table = (DB_PREFIX) ? "order" : "`order`";
        $query_nfe_data = $this->db->query("SELECT nfe_info FROM " . DB_PREFIX . "$order_table WHERE order_id = $order_id");
        $nfe_data = @unserialize($query_nfe_data->rows[0]['nfe_info']);
        if (!$nfe_data) {
          $nfe_data = @unserialize(utf8_decode(base64_decode($query_nfe_data->rows[0]['nfe_info'])));
        }
        if (!$nfe_data) {
          $nfe_data = array();
        }

        foreach($nfe_data as &$order_nfe){
          if($order_nfe['chave_acesso'] == $chave_acesso){
            $order_nfe['status'] = $new_status;
          }
        }

        $nfe_data_str = base64_encode(utf8_encode(serialize($nfe_data)));

        $query = $this->db->query("UPDATE " . DB_PREFIX . "$order_table SET nfe_info = '$nfe_data_str' WHERE order_id = $order_id");
        $this->session->data['success'] = '<p><i class="fa fa-check-circle"></i> NF-e atualizada com sucesso';

        $url = new Url(HTTP_SERVER, $this->config->get('config_secure') ? HTTP_SERVER : HTTPS_SERVER);
        $this->response->redirect($url->link('sale/order/info&order_id='.$order_id, 'user_token=' . $this->session->data['user_token'], 'SSL'));

      }

    }

  }

  //Get order and customer info from Model and emit
  public function emitirNfe(){

    if (isset($this->request->post['selected'])) {

      $this->load->model('sale/order');
      $this->load->model('extension/module/webmaniabrnfe');
      $success = '';
      $error = '';
      foreach ($this->request->post['selected'] as $order_id) {
        $order_info = $this->model_sale_order->getOrder($order_id);
        $products_info = $this->model_sale_order->getOrderProducts($order_id);
        $data = $this->model_extension_module_webmaniabrnfe->getNfeInfo($order_info, $products_info);
        $response = $this->getNFe()->emissaoNotaFiscal( $data );
        if (isset($response->error) || $response->status == 'reprovado'){
          if(isset($response->error)){
              if (empty($error)) {
                  $error .= ' NF-e do pedido #'.$order_id.' não emitida ( '.$response->error.' )';
              }
              else {
                  $error .= '<br><i class="fa fa-close"></i> NF-e do pedido #' . $order_id . ' não emitida ( ' . $response->error . ' )';
              }
          }elseif(isset($response->log->aProt[0]->xMotivo)){
              if (empty($error)) {
                  $error .= ' NF-e do pedido #'.$order_id.' não emitida ( '.$response->log->aProt[0]->xMotivo.' )';
              }
              else {
                  $error .= '<br><i class="fa fa-close"></i> NF-e do pedido #' . $order_id . ' não emitida ( ' . $response->log->aProt[0]->xMotivo . ' )';
              }
          }else{
              if (empty($error)) {
                  $error .= ' NF-e do pedido #'.$order_id.' não emitida';
              }
              else {
                  $error .= '<br><i class="fa fa-close"></i> NF-e do pedido #' . $order_id . ' não emitida';
              }
          }
        }else{

          $order_table = (DB_PREFIX) ? "order" : "`order`";
          $previous_info_query = $this->db->query("SELECT nfe_info FROM " . DB_PREFIX . "$order_table WHERE order_id = $order_id");

          $previous_info = @unserialize($previous_info_query->rows[0]['nfe_info']);
          if(!$previous_info){
            $previous_info = @unserialize(utf8_decode(base64_decode($previous_info_query->rows[0]['nfe_info'])));
          }
          if (!$previous_info) {
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
            'url_danfe_simples'    => (string) $response->danfe_simples,
            'url_danfe_etiqueta'    => (string) $response->danfe_etiqueta,
            'data'         => date('d/m/Y'),
          );

          $previous_info[] = $order_nfe_info;

          $nfe_info_str = base64_encode(utf8_encode(serialize($previous_info)));

          $query = $this->db->query("UPDATE " . DB_PREFIX . "$order_table SET nfe_info = '$nfe_info_str' WHERE order_id = $order_id");
          $query = $this->db->query("UPDATE " . DB_PREFIX . "$order_table SET status_nfe = '1' WHERE order_id = $order_id");

          if (empty($success)) {
              $success .= ' NF-e do pedido #' . $order_id . ' emitida com sucesso';
          }
          else {
              $success .= '<br><i class="fa fa-check-circle"></i> NF-e do pedido #' . $order_id . ' emitida com sucesso';
          }


        }
      }

      if(strlen($error) > 0){
        $this->session->data['error_warning'] = $error;
      }
      if(strlen($success) > 0){
        $this->session->data['success'] = $success;
      }

    }

    $url = new Url(HTTP_SERVER, $this->config->get('config_secure') ? HTTP_SERVER : HTTPS_SERVER);
    $this->response->redirect($url->link('sale/order', 'user_token=' . $this->session->data['user_token'], 'SSL'));

  }

  //Get Sefaz status in case the last check was at least an hour ago



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

  function get_value_from_fields( $data ){

    return $this->NFeFunctions->get_value_from_fields($data["key"], $data["custom_fields_ids"], $data["custom_fields_customer"]);

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
                $order_table = (DB_PREFIX) ? "order" : "`order`";
				$this->db->query("UPDATE " . DB_PREFIX . "$order_table SET nfe_transporte_modalidade_frete = '$modalidade_frete', nfe_transporte_volume = '$volume', nfe_transporte_especie = '$especie', nfe_transporte_peso_bruto = '$peso_bruto', nfe_transporte_peso_liquido = '$peso_liquido' WHERE order_id = '$order_id'");
			}catch(Exception $e){}

		}

  }

  public function get_order_transporte_info( $order_id ){

    if(!$order_id) return array();

    $data = array();

    try{
            $order_table = (DB_PREFIX) ? "order" : "`order`";
			$query_transporte_info = $this->db->query("SELECT nfe_transporte_modalidade_frete, nfe_transporte_volume, nfe_transporte_especie, nfe_transporte_peso_bruto, nfe_transporte_peso_liquido FROM " . DB_PREFIX . "$order_table WHERE order_id = $order_id");

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

  public function imprimirDanfe() {

    $url = new Url(HTTP_SERVER, $this->config->get('config_secure') ? HTTP_SERVER : HTTPS_SERVER);
    $url_redirect = $url->link('sale/order', 'user_token=' . $this->session->data['user_token'], 'SSL');

    if (isset($this->request->post['selected'])) {
      
      $this->load->model('sale/order');
      $this->load->model('extension/module/webmaniabrnfe');

      $links = $this->getLinksToPrint($this->request->post['selected'], 'normal');

      if (!empty($links)) {
        $result = $this->createPdf($links);
      }

    }

    if(!isset($result['result']) || (empty($result['result']) || !file_exists($result['path']))){
      $this->response->redirect($url_redirect);
      die();
    }

    $this->showDanfe($result['path'], $result['file']);
  }

  public function imprimirDanfeEtiqueta() {

    $url = new Url(HTTP_SERVER, $this->config->get('config_secure') ? HTTP_SERVER : HTTPS_SERVER);
    $url_redirect = $url->link('sale/order', 'user_token=' . $this->session->data['user_token'], 'SSL');

    if (isset($this->request->post['selected'])) {
      
      $this->load->model('sale/order');
      $this->load->model('extension/module/webmaniabrnfe');

      $links = $this->getLinksToPrint($this->request->post['selected'], 'etiqueta');

      if (!empty($links)) {
        $result = $this->createPdf($links);
      }

    }

    if(!isset($result['result']) || (empty($result['result']) || !file_exists($result['path']))){
      $this->response->redirect($url_redirect);
      die();
    }

    $this->showDanfe($result['path'], $result['file']);
  }

  public function imprimirDanfeSimples() {

    $url = new Url(HTTP_SERVER, $this->config->get('config_secure') ? HTTP_SERVER : HTTPS_SERVER);
    $url_redirect = $url->link('sale/order', 'user_token=' . $this->session->data['user_token'], 'SSL');

    if (isset($this->request->post['selected'])) {
      
      $this->load->model('sale/order');
      $this->load->model('extension/module/webmaniabrnfe');

      $links = $this->getLinksToPrint($this->request->post['selected'], 'simples');

      if (!empty($links)) {
        $result = $this->createPdf($links);
      }

    }

    if(!isset($result['result']) || (empty($result['result']) || !file_exists($result['path']))){
      $this->response->redirect($url_redirect);
      die();
    }

    $this->showDanfe($result['path'], $result['file']);
  }

  public function getLinksToPrint($ids, $type = 'normal') {

    $order_table = (DB_PREFIX) ? "order" : "`order`";
    $links = array();

    foreach ($ids as $order_id) {

      $query = $this->db->query("SELECT nfe_info FROM " . DB_PREFIX . "$order_table WHERE order_id = $order_id");
      if(isset($query->rows[0]['nfe_info'])){
        $nfe_info = @unserialize($query->rows[0]['nfe_info']);
        if (!$nfe_info) {
          $nfe_info = @unserialize(utf8_decode(base64_decode($query->rows[0]['nfe_info'])));
        }
        if (!$nfe_info) {
          $nfe_info = array();
        }
  
        $data = end($nfe_info);

        if (!$data) {
          continue;
        }

        $url = '';
        if ($type == 'normal') {
          $url = $data['url_danfe'];
        }
        else if ($type == 'simples') {
          $url = ($data['url_danfe_simples']) ? $data['url_danfe_simples'] : str_replace('/danfe/', '/danfe/simples/', $data['url_danfe']);
        }
        else if ($type == 'etiqueta') {
          $url = ($danfe['url_danfe_etiqueta']) ? $data['url_danfe_etiqueta'] : str_replace('/danfe/', '/danfe/etiqueta/', $data['url_danfe']);
        }

        $links[] = array('chave' => $data['chave_acesso'], 'url' => $url);
    
      }
    
    }

    return $links;

  }

  public function createPdf($links) {

    $directory = __DIR__ . '/../pdf/pdf_files/';
    if (!file_exists($directory)) {
      mkdir($directory);
    }

    $pdf = new PDFMerger();

    foreach($links as $link) {

      $fileContent = $this->NFe->curl_get_file_contents($link['url']);
      if ($fileContent) {
        file_put_contents("{$directory}/{$link['chave']}.pdf", $fileContent);
        $pdf->addPDF("{$directory}/{$link['chave']}.pdf", 'all');
      }

    }

    $filename = time()."-".random_int(1, 10000000000);
    $path = "{$directory}/{$filename}.pdf";
		$result = $pdf->merge('file', $path);

    foreach($links as $link) {
      if(file_exists("{$directory}/{$link['chave']}.pdf")) unlink("{$directory}/{$link['chave']}.pdf");
    }

		return array("result" => $result, "file" => "{$filename}.pdf", "path" => $path);

  }

  public function showDanfe($path, $file){
      
    header('Content-Type: application/pdf');
    header("Content-Disposition: inline; filename=$file");
    header('Content-Length: ' . filesize($path));
    header('Pragma: no-cache');
    header('Expires: 0');

    ob_clean();
    flush();
    $handle = fopen($path, "rb");
    while (!feof($handle)) {
      echo fread($handle, 8192);
      flush();
    }
    fclose($handle);

    unlink($path);
  }

  public function createSecureTokenDFe( $data ){

    $password = preg_replace("/[^0-9]/", '', $data['password']);
    $key = hash('sha256', $password . ':' . $data['uuid'], true);
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('AES-256-CBC'));
    $encryptedData = openssl_encrypt(time(), 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    $tokenData = json_encode(['data' => base64_encode($encryptedData), 'iv' => base64_encode($iv)]);
    return urlencode(base64_encode($tokenData));
  
  }

}
