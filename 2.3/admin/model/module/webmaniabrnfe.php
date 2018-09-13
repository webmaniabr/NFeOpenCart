<?php
class ModelModuleWebmaniaBRNFe extends Model {

	public function getNfeInfo($order_data, $products_data){

		global $registry;
		require_once (__DIR__.'/../../controller/extension/nfe/functions.php');
		require_once (__DIR__.'/../../controller/extension/module/webmaniabrnfe.php');
		$NFeFunctions = new NFeFunctions;
		$controller = new ControllerExtensionModuleWebmaniaBRNFe($this->registry);

		$this->load->model('setting/setting');
		$this->load->model('catalog/product');
		$this->load->model('customer/customer');
		$this->load->model('sale/order');
		$this->load->model('marketing/coupon');
		$total_discounts = 0;
		$total = 0;
		$coupons = array();
		$order_totals = $this->model_sale_order->getOrderTotals($order_data['order_id']);

		$module_settings = $this->model_setting_setting->getSetting('webmaniabrnfe');
		$envio_email = @$module_settings['webmaniabrnfe_envio_email'];
		$uniq_key = @$module_settings['webmaniabrnfe_uniq_get_key'];

		if(!$envio_email) $email_envio = 'on';

		if(!$uniq_key){

			$uniq_key = md5(uniqid(rand(), true));
			$module_settings['webmaniabrnfe_uniq_get_key'] = $uniq_key;
			$this->model_setting_setting->editSetting('webmaniabrnfe', $module_settings);

		}

		$notification_url = HTTP_CATALOG.'?retorno_nfe='.$uniq_key.'&order_id='.$order_data['order_id'];

		$customer_info = $this->model_customer_customer->getCustomer($order_data['customer_id']);
		$custom_fields_customer = @unserialize($customer_info['custom_field']);
		if(!$custom_fields_customer) $custom_fields_customer = json_decode($customer_info['custom_field'], true);


		$custom_fields_ids = $this->load->controller('extension/module/webmaniabrnfe/getCustomFieldsIds');
		$documento = $NFeFunctions->get_value_from_fields( 'tipo_pessoa', $custom_fields_ids, $custom_fields_customer );

		if($documento == $NFeFunctions->get_value_from_fields( 'pessoa_fisica', $custom_fields_ids, $custom_fields_customer )){
			$tipo_pessoa = 'cpf';
			$document_number = $NFeFunctions->get_value_from_fields( 'cpf', $custom_fields_ids, $custom_fields_customer );
		}elseif($documento == $NFeFunctions->get_value_from_fields( 'pessoa_juridica', $custom_fields_ids, $custom_fields_customer )){
			$tipo_pessoa = 'cnpj';
			$document_number = $NFeFunctions->get_value_from_fields( 'cnpj', $custom_fields_ids, $custom_fields_customer );
			$insc_est = $NFeFunctions->get_value_from_fields( 'insc_est', $custom_fields_ids, $custom_fields_customer );
			$razao_social = $NFeFunctions->get_value_from_fields( 'razao_social', $custom_fields_ids, $custom_fields_customer );
		}

		$address_number = $order_data['shipping_custom_field'][$NFeFunctions->get_value_from_fields( 'numero', $custom_fields_ids, $custom_fields_customer )];
		$complemento = $order_data['shipping_custom_field'][$NFeFunctions->get_value_from_fields( 'complemento', $custom_fields_ids, $custom_fields_customer )];

		$shipping_total = 0;
		foreach($order_totals as $order_total){

			if($order_total['code'] == 'shipping'){
				$shipping_total = $order_total['value'];
			}

			elseif($order_total['code'] == 'coupon'){
				$total_discounts += abs($order_total['value']);

				//Get coupon code based on title. Default Opencart Format: Coupon (code);
				$title = $order_total['title'];
				$pos1 = strpos( $title, '(' );
				$pos2 = strpos( $title, ')' );
				$coupon_code = substr($order_total['title'], $pos1+1, ($pos2-$pos1)-1);
				$coupon = $this->model_marketing_coupon->getCouponByCode($coupon_code);
				if($coupon['type'] == 'P'){
					$coupons[] = $coupon['discount'];
				}
			}

			elseif($order_total['code'] == 'sub_total'){
				$sub_total = $order_total['value'];
			}

			elseif($order_total['code'] == 'total'){
				$total = $order_total['value'];
			}

		}

		$data = array(
			'ID' => (int)$order_data['order_id'], // Número do pedido
			'url_notificacao' => $notification_url,
			'operacao' => 1, // Tipo de Operação da Nota Fiscal
			'natureza_operacao' => $module_settings['webmaniabrnfe_operation_nature'], // Natureza da Operação
			'modelo' => 1, // Modelo da Nota Fiscal (NF-e ou NFC-e)
			'emissao' => 1, // Tipo de Emissão da NF-e
			'finalidade' => 1, // Finalidade de emissão da Nota Fiscal
			'ambiente' => @(int)$module_settings['webmaniabrnfe_sefaz_env'], // Identificação do Ambiente do Sefaz //1 for production, 2 for development
		);

		$data['pedido'] = array(
			'presenca' => 2, // Indicador de presença do comprador no estabelecimento comercial no momento da operação
			'pagamento' => 0,
			'modalidade_frete' => 0, // Modalidade do frete
			'frete' => number_format($shipping_total, 2, '.', ''), // Total do frete
			'desconto' => number_format($total_discounts, 2, '.', ''), // Total do desconto
			'total' => number_format($order_data['total'], 2, '.', ''), // Total do pedido
		);
		
		$payment_code = $order_data['payment_code'];
		if(isset($module_settings['webmaniabrnfe_payment_'.$payment_code]) && $module_settings['webmaniabrnfe_payment_'.$payment_code]){
			$data['pedido']['forma_pagamento'] = $module_settings['webmaniabrnfe_payment_'.$payment_code];
		}

		//Informações COmplementares ao Fisco
		$fiscoinf = $module_settings['webmaniabrnfe_fisco_inf'];

		if(!empty($fiscoinf) && strlen($fiscoinf) <= 2000){
			$data['pedido']['informacoes_fisco'] = $fiscoinf;
		}

		//Informações Complementares ao Consumidor
		$consumidorinf = $module_settings['webmaniabrnfe_cons_inf'];

		if(!empty($consumidorinf) && strlen($consumidorinf) <= 2000){
			$data['pedido']['informacoes_complementares'] = $consumidorinf;
		}

		//Produtos
		foreach ($products_data as $product){

			$product_id = $product['product_id'];

			$ignorar_nfe= $this->db->query('SELECT ignorar_nfe FROM '. DB_PREFIX .'product WHERE product_id = ' . (int)$product_id);
			if($ignorar_nfe->row['ignorar_nfe'] == 1){

				$data['pedido']['total'] -= $product['total'];

				foreach($coupons as $percentage){
					$data['pedido']['total'] += ($percentage/100) * $product['total'];
					$data['pedido']['desconto'] -= ($percentage/100) * $product['total'];
				}
				continue;
			}

			$product_info = $this->model_catalog_product->getProduct($product_id);


			/*
			* Specific product values
			*/
			$codigo_gtin_row = $this->db->query('SELECT ean_barcode FROM '. DB_PREFIX .'product WHERE product_id = ' . (int)$product_id);
			$codigo_gtin = $codigo_gtin_row->row['ean_barcode'];
			
			$gtin_tributavel_row = $this->db->query('SELECT gtin_tributavel FROM '. DB_PREFIX .'product WHERE product_id = ' . (int)$product_id);
			$gtin_tributavel = $gtin_tributavel_row->row['gtin_tributavel'];

			$codigo_ncm_row = $this->db->query('SELECT ncm_code FROM '. DB_PREFIX .'product WHERE product_id = ' . (int)$product_id);
			$codigo_ncm = $codigo_ncm_row->row['ncm_code'];

			$codigo_cest_row = $this->db->query('SELECT cest_code FROM '. DB_PREFIX .'product WHERE product_id = ' . (int)$product_id);
			$codigo_cest = $codigo_cest_row->row['cest_code'];
			
			$cnpj_fabricante_row = $this->db->query('SELECT cnpj_fabricante FROM '. DB_PREFIX .'product WHERE product_id = ' . (int)$product_id);
			$cnpj_fabricante = $cnpj_fabricante_row->row['cnpj_fabricante'];
			
			$ind_escala_row = $this->db->query('SELECT ind_escala FROM '. DB_PREFIX .'product WHERE product_id = ' . (int)$product_id);
			$ind_escala = $ind_escala_row->row['ind_escala'];

			$origem_row = $this->db->query('SELECT product_source FROM '. DB_PREFIX .'product WHERE product_id = ' . (int)$product_id);
			$origem = $origem_row->row['product_source'];

			$imposto_row = $this->db->query('SELECT classe_imposto FROM '. DB_PREFIX .'product WHERE product_id = ' . (int)$product_id);
			$imposto = $imposto_row->row['classe_imposto'];
			$peso = $product_info['weight'];

			$kg = explode('.', $peso);
			if (strlen($kg[0]) >= 3) {

				$peso = $peso / 1000;

			}

			if (!$peso) $peso = '0.100';
			$peso = number_format($peso, 3, '.', '');
			if (!$codigo_gtin) $codigo_gtin = $module_settings['webmaniabrnfe_ean_barcode'];
			if (!$gtin_tributavel) $gtin_tributavel = $module_settings['webmaniabrnfe_gtin_tributavel'];

			if (!$codigo_ncm){

				$categories = $this->model_catalog_product->getProductCategories($product_id);

				foreach($categories as $cat_id){

					try{
						$ncm_row = $this->db->query("SELECT category_ncm FROM " . DB_PREFIX . "category WHERE category_id = '" . (int)$cat_id . "'");
						$codigo_ncm = $ncm_row->row['category_ncm'];
					}catch(Exception $e){}

						if($codigo_ncm) break;

				}


			}

			if (!$codigo_ncm) $codigo_ncm = $module_settings['webmaniabrnfe_ncm_code'];
			if (!$codigo_cest) $codigo_cest = $module_settings['webmaniabrnfe_cest_code'];
			
			if(!$cnpj_fabricante) $cnpj_fabricante = $module_settings['webmaniabrnfe_cnpj_fabricante'];
			if(!$ind_escala) $ind_escala = $module_settings['webmaniabrnfe_ind_escala'];
			
			
			if (!is_numeric($origem) || (int)$origem == -1) $origem = $module_settings['webmaniabrnfe_product_source'];
			if (!$imposto) $imposto = $module_settings['webmaniabrnfe_tax_class'];
			$data['produtos'][] = array(
				'nome' => $product['name'], // Nome do produto
				'sku' => $product_info['sku'], // Código identificador - SKU
				'gtin' => $codigo_gtin, // Código EAN
				'gtin_tributavel' => $gtin_tributavel,
				'ncm' => $codigo_ncm, // Código NCM
				'cest' => $codigo_cest, // Código CEST
				'cnpj_fabricante' => $cnpj_fabricante,
				'ind_escala' => $ind_escala,
				'quantidade' => $product['quantity'], // Quantidade de itens
				'unidade' => 'UN', // Unidade de medida da quantidade de itens
				'peso' => $peso, // Peso em KG. Ex: 800 gramas = 0.800 KG
				'origem' => (int)$origem,//Origem do produto
				'subtotal' => number_format($product['price'], 2, '.', ''), // Preço unitário do produto - sem descontos
				'total' => number_format($product['total'], 2, '.', ''), // Preço total (quantidade x preço unitário) - sem descontos
				'classe_imposto' => $imposto // Referência do imposto cadastrado
			);
		}


		if ($tipo_pessoa == 'cpf'){
			$data['cliente'] = array(
				'cpf' => $controller->cpf($document_number), // (pessoa fisica) Número do CPF
				'nome_completo' => $order_data['customer'], // (pessoa fisica) Nome completo
				'endereco' => $order_data['shipping_address_1'],//$order_data['shipping_address_1'], // Endereço de entrega dos produtos
				'complemento' => $complemento, //$address->other, // Complemento do endereço de entrega
				'numero' => $address_number, //$address_custom['nfe_number'], // Número do endereço de entrega
				'bairro' => $order_data['shipping_address_2'], // Bairro do endereço de entrega
				'cidade' => $order_data['shipping_city'],//$order_data['shipping_city'], // Cidade do endereço de entrega
				'uf' => $order_data['shipping_zone_code'], // Estado do endereço de entrega
				'cep' => $controller->cep($order_data['shipping_postcode']),//$order_data['shipping_postcode'], // CEP do endereço de entrega
				'telefone' => $order_data['telephone'],//$order_data['telephone'], // Telefone do cliente
				'email' => ($envio_email == 'on' ? $order_data['email'] : '') // E-mail do cliente para envio da NF-e
			);
		}else if($tipo_pessoa == 'cnpj'){
			$data['cliente'] = array(
				'cnpj' => $controller->cnpj($document_number), // (pessoa jurídica) Número do CNPJ
				'razao_social' => $razao_social, // (pessoa jurídica) Razão Social
				'ie' => $insc_est, // (pessoa jurídica) Número da Inscrição Estadual
				'endereco' => $order_data['shipping_address_1'], // Endereço de entrega dos produtos
				'complemento' => $complemento, // Complemento do endereço de entrega
				'numero' => $address_number, // Número do endereço de entrega
				'bairro' => $order_data['shipping_address_2'], // Bairro do endereço de entrega
				'cidade' => $order_data['shipping_city'], // Cidade do endereço de entrega
				'uf' => $order_data['shipping_zone_code'], // Estado do endereço de entrega
				'cep' => $controller->cep($order_data['shipping_postcode']), // CEP do endereço de entrega
				'telefone' => $order_data['telephone'], // Telefone do cliente
				'email' => ($envio_email == 'on' ? $order_data['email'] : '') // E-mail do cliente para envio da NF-e
			);
		}

		if($module_settings['webmaniabrnfe_transp_include'] == 'on'){

			$methods = $module_settings['webmaniabrnfe_carriers'];
			$methods = json_decode(stripslashes(html_entity_decode($methods)), true);
			
			
			foreach($methods as $method){
				
				if($method['method'].'.'.$method['method'] == $order_data['shipping_code']){
					$data['transporte'] = array(
						'cnpj'         => $method['cnpj'],
						'razao_social' => $method['razao_social'],
						'ie'           => $method['ie'],
						'endereco'     => $method['address'],
						'uf'           => $method['uf'],
						'cidade'       => $method['city'],
						'cep'          => $method['cep'],
					);
					
					$transporte_info = $this->load->controller('extension/module/webmaniabrnfe/get_order_transporte_info', $order_data['order_id']);
	
					$transporte_keys = array(
						'nfe_volume'       => 'volume',
						'nfe_especie'      => 'especie',
						'nfe_peso_bruto'   => 'peso_bruto',
						'nfe_peso_liquido' => 'peso_liquido'
					);
	
					foreach($transporte_keys as $array_key => $api_key){
						$value = $transporte_info[$array_key];
						if($value){
							$data['transporte'][$api_key] = $value;
						}
					}
	
					if($transporte_info['modalidade_frete']){
						$data['pedido']['modalidade_frete'] = $transporte_info['modalidade_frete'];
					}
				
				}
				
			}
			
		}
		
		
		return $data;

	}
}
?>
