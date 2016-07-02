<?php
class ControllerModuleWebmaniaBrNfe extends Controller {
	private $error = array();

	public function index() {

		$this->language->load('module/webmaniabrnfe');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('webmaniabrnfe', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->redirect($this->url->link('module/webmaniabrnfe', 'token=' . $this->session->data['token'], 'SSL'));
		}

		// Assign the language data for parsing it to view
		$this->data['heading_title'] = $this->language->get('heading_title');

		$this->data['text_edit']    = $this->language->get('text_edit');
		$this->data['text_enabled'] = $this->language->get('text_enabled');
		$this->data['text_disabled'] = $this->language->get('text_disabled');
		$this->data['text_content_top'] = $this->language->get('text_content_top');
		$this->data['text_content_bottom'] = $this->language->get('text_content_bottom');
		$this->data['text_column_left'] = $this->language->get('text_column_left');
		$this->data['text_column_right'] = $this->language->get('text_column_right');

		$this->data['entry_code'] = $this->language->get('entry_code');
		$this->data['entry_layout'] = $this->language->get('entry_layout');
		$this->data['entry_position'] = $this->language->get('entry_position');
		$this->data['entry_status'] = $this->language->get('entry_status');
		$this->data['entry_sort_order'] = $this->language->get('entry_sort_order');

		$this->data['button_save'] = $this->language->get('button_save');
		$this->data['button_edit'] = $this->language->get('button_save');
		$this->data['button_cancel'] = $this->language->get('button_cancel');
		$this->data['button_add_module'] = $this->language->get('button_add_module');
		$this->data['button_remove'] = $this->language->get('button_remove');

		if (isset($this->error['warning'])) {
			$this->data['error_warning'] = $this->error['warning'];
		} else {
			$this->data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
				$this->data['success_message'] = $this->session->data['success'];
		} else {
				$this->data['success_message'] = '';
		}

		$this->data['breadcrumbs'] = array();

		$this->data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => false
		);

		$this->data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_module'),
			'href'      => $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => ' :: '
		);

		$this->data['breadcrumbs'][] = array(
			'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link('module/webmaniabrnfe', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => ' :: '
		);

		$this->data['action'] = $this->url->link('module/webmaniabrnfe', 'token=' . $this->session->data['token'], 'SSL');

		$this->data['cancel'] = $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL');

		$this->data['token'] = $this->session->data['token'];

		$this->data['modules'] = array();



		$settings_fields = array(
			'consumer_key',
			'consumer_secret',
			'access_token',
			'access_token_secret',
			'operation_nature',
			'tax_class',
			'ean_barcode',
			'ncm_code',
			'cest_code',
			'sefaz_env',
			'product_source');
		foreach($settings_fields as $field){
			if (isset($this->request->post['webmaniabrnfe_'.$field])) {
					$this->data['webmaniabrnfe_'.$field] = $this->request->post['webmaniabrnfe_'.$field];
			} elseif($this->config->get('webmaniabrnfe_'.$field)) {
					$this->data['webmaniabrnfe_'.$field] = $this->config->get('webmaniabrnfe_'.$field);
			}else{
					$this->data['webmaniabrnfe_'.$field] = '';
			}
		}

		$this->template = 'module/webmaniabrnfe.tpl';
		$this->children = array(
			'common/header',
			'common/footer'
		);

		$this->response->setOutput($this->render());
	}

	public function install(){

		$store_id = $this->config->get('config_store_id');
		$this->load->model('setting/setting');
		$this->model_setting_setting->editSettingValue('config', 'config_guest_checkout', 0, $store_id);


		$query_existing_column = $this->db->query("SHOW COLUMNS FROM " . DB_PREFIX . "customer LIKE 'document_type'");
		if($query_existing_column->num_rows == 0){
			$query = $this->db->query("ALTER TABLE  " . DB_PREFIX . "customer ADD COLUMN document_type VARCHAR( 5 )");
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

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'module/webmaniabrnfe')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->error) {
			return true;
		} else {
			return false;
		}
	}

	public function emitirnfe(){
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


		$this->redirect($this->url->link('sale/order', 'token=' . $this->session->data['token'] . $url, 'SSL'));
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

	function validaCPF($cpf = null){
    if(is_array($cpf)) $cpf = $cpf[0];
    if(empty($cpf)) {
      $this->output = false;
      return $this->output;
    }
    // Elimina possivel mascara
    $cpf = preg_replace("/[^0-9]/","", $cpf);
    $cpf = str_pad($cpf, 11, '0', STR_PAD_LEFT);

    if(strlen($cpf) != 11){
      $this->output = false;
    }else if($cpf == '00000000000' ||
    $cpf == '11111111111' ||
    $cpf == '22222222222' ||
    $cpf == '33333333333' ||
    $cpf == '44444444444' ||
    $cpf == '55555555555' ||
    $cpf == '66666666666' ||
    $cpf == '77777777777' ||
    $cpf == '88888888888' ||
    $cpf == '99999999999'){
      $this->output = false;
      return $this->output;
    }else{
      for($t = 9; $t < 11; $t++){
        for ($d = 0, $c = 0; $c < $t; $c++){
          $d += $cpf{$c} * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf{$c} != $d){
          $this->output = false;
          return $this->output;
        }
      }
      $this->output = true;
      return $this->output;
    }
  }

  function validaCNPJ($cnpj = null) {
    if(is_array($cnpj)) $cnpj = $cnpj[0];
    $cnpj = sprintf( '%014s', preg_replace( '{\D}', '', $cnpj ) );

    if ( 14 != ( strlen( $cnpj ) ) || ( 0 == intval( substr( $cnpj, -4 ) ) ) ) {
      $this->output = false;
      return $this->output;
    }

    for ( $t = 11; $t < 13; ) {
      for ( $d = 0, $p = 2, $c = $t; $c >= 0; $c--, ( $p < 9 ) ? $p++ : $p = 2 ) {
        $d += $cnpj[ $c ] * $p;
      }

      if ( $cnpj[ ++$t ] != ( $d = ( ( 10 * $d ) % 11 ) % 10 ) ) {
        $this->output = false;
        return $this->output;
      }
    }

    $this->output = true;
    return $this->output;
  }
}
?>
