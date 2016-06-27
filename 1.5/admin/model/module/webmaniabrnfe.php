<?php
class ModelModuleWebmaniaBRNfe extends Model {
	public function getNfeInfo($order_data, $products_data){

		//print_r($order_totals); die();
		//print_r($order_data);
		//print_r($products_data);
		$this->load->model('setting/setting');
		$this->load->model('catalog/product');
		$this->load->model('sale/customer');
		$this->load->model('sale/order');
		$this->load->model('sale/order');
		$total_discounts = 0;
		$total = 0;
		$order_totals = $this->model_sale_order->getOrderTotals($order_data['order_id']);
		$module_settings = $this->model_setting_setting->getSetting('webmaniabrnfe');
		$customer_info = $this->model_sale_customer->getCustomer($order_data['customer_id']);
		$address_row = $this->db->query("SELECT address_number FROM " . DB_PREFIX . "address WHERE address_id='" .(int)$customer_info['address_id']. "' AND customer_id='".$order_data['customer_id']."'");
		$address_number = $address_row->row['address_number'];

		$shipping_total = 0;
		foreach($order_totals as $order_total){
			if($order_total['code'] == 'shipping'){
				$shipping_total = $order_total['value'];
			}
			if($order_total['code'] == 'coupon'){
				$total_discounts += abs($order_total['value']);
			}

			if($order_total['code'] == 'sub_total'){
				$total = $order_total['value'];
			}
		}

		$data = array(
        'ID' => (int)$order_data['order_id'], // Número do pedido
        'operacao' => 1, // Tipo de Operação da Nota Fiscal
        'natureza_operacao' => $module_settings['webmaniabrnfe_operation_nature'], // Natureza da Operação
        'modelo' => 1, // Modelo da Nota Fiscal (NF-e ou NFC-e)
        'emissao' => 1, // Tipo de Emissão da NF-e
        'finalidade' => 1, // Finalidade de emissão da Nota Fiscal
        'ambiente' => (int)$module_settings['webmaniabrnfe_sefaz_env'], // Identificação do Ambiente do Sefaz //1 for production, 2 for development
     );

		//print_r($products_data);
		//die();

		foreach ($products_data as $product){
			$product_id = $product['product_id'];
			$product_info = $this->model_catalog_product->getProduct($product_id);
			$product_discounts = $this->model_catalog_product->getProductDiscounts($product_id);
			if($product['price'] != $product_info['price']){
				$discount = $product_info['price'] - $product['price'];
				$total_discounts += ($discount*$product['quantity']);
			}
			//print_r($product_discounts);

			/*
			* Specific product values
			*/
			$codigo_ean_row = $this->db->query('SELECT ean_barcode FROM '. DB_PREFIX .'product WHERE product_id = ' . (int)$product_id);
			$codigo_ean = $codigo_ean_row->row['ean_barcode'];

			$codigo_ncm_row = $this->db->query('SELECT ncm_code FROM '. DB_PREFIX .'product WHERE product_id = ' . (int)$product_id);
			$codigo_ncm = $codigo_ncm_row->row['ncm_code'];

			$codigo_cest_row = $this->db->query('SELECT cest_code FROM '. DB_PREFIX .'product WHERE product_id = ' . (int)$product_id);
			$codigo_cest = $codigo_cest_row->row['cest_code'];

			$origem_row = $this->db->query('SELECT product_source FROM '. DB_PREFIX .'product WHERE product_id = ' . (int)$product_id);
			$origem = $origem_row->row['product_source'];

			$imposto_row = $this->db->query('SELECT classe_imposto FROM '. DB_PREFIX .'product WHERE product_id = ' . (int)$product_id);
			$imposto = $imposto_row->row['classe_imposto'];
			$peso = $product_info['weight'];



			//print_r($product_info);
			if (!$peso) $peso = '0.100';
			if (!$codigo_ean) $codigo_ean = $module_settings['webmaniabrnfe_ean_barcode'];
			if (!$codigo_ncm) $codigo_ncm = $module_settings['webmaniabrnfe_ncm_code'];
			if (!$codigo_cest) $codigo_cest = $module_settings['webmaniabrnfe_cest_code'];
			if (!is_numeric($origem) || (int)$origem == -1) $origem = $module_settings['webmaniabrnfe_product_source'];
			if (!$imposto) $imposto = $module_settings['webmaniabrnfe_tax_class'];
			$data['produtos'][] = array(
				'nome' => $product['name'], // Nome do produto
				'sku' => $product_info['sku'], // Código identificador - SKU
				'ean' => $codigo_ean, // Código EAN
				'ncm' => $codigo_ncm, // Código NCM
				'cest' => $codigo_cest, // Código CEST
				'quantidade' => $product['quantity'], // Quantidade de itens
				'unidade' => 'UN', // Unidade de medida da quantidade de itens
				'peso' => $peso, // Peso em KG. Ex: 800 gramas = 0.800 KG
				'origem' => (int)$origem,//Origem do produto
				'subtotal' => number_format($product_info['price'], 2), // Preço unitário do produto - sem descontos
				'total' => number_format($product_info['price']*$product['quantity'], 2), // Preço total (quantidade x preço unitário) - sem descontos
				'classe_imposto' => $imposto // Referência do imposto cadastrado
			);
		}

		 if ($customer_info['document_type'] == 'cpf'){
				 $data['cliente'] = array(
						 'cpf' => $customer_info['document_number'], // (pessoa fisica) Número do CPF
						 'nome_completo' => $order_data['customer'], // (pessoa fisica) Nome completo
						 'endereco' => $order_data['shipping_address_1'],//$order_data['shipping_address_1'], // Endereço de entrega dos produtos
						 'complemento' => '',//$address->other, // Complemento do endereço de entrega
						 'numero' => $address_number, //$address_custom['nfe_number'], // Número do endereço de entrega
						 'bairro' => $order_data['shipping_address_2'], // Bairro do endereço de entrega
						 'cidade' => $order_data['shipping_city'],//$order_data['shipping_city'], // Cidade do endereço de entrega
						 'uf' => $order_data['shipping_zone_code'], // Estado do endereço de entrega
						 'cep' => $order_data['shipping_postcode'],//$order_data['shipping_postcode'], // CEP do endereço de entrega
						 'telefone' => $order_data['telephone'],//$order_data['telephone'], // Telefone do cliente
						 'email' => $order_data['email'] // E-mail do cliente para envio da NF-e
				 );
		 }else if($customer_info['document_type'] == 'cnpj'){
			 $data['cliente'] = array(
				 'cnpj' => $customer_info['document_number'], // (pessoa jurídica) Número do CNPJ
				 'razao_social' => $customer_info['razao_social'], // (pessoa jurídica) Razão Social
				 'ie' => $customer_info['pj_ie'], // (pessoa jurídica) Número da Inscrição Estadual
				 'endereco' => $order_data['shipping_address_1'], // Endereço de entrega dos produtos
				 'complemento' => '',//$address->other, // Complemento do endereço de entrega
				 'numero' => $address_number, // Número do endereço de entrega
				 'bairro' => $order_data['shipping_address_2'], // Bairro do endereço de entrega
				 'cidade' => $order_data['shipping_city'], // Cidade do endereço de entrega
				 'uf' => $order_data['shipping_zone_code'], // Estado do endereço de entrega
				 'cep' => $order_data['shipping_postcode'], // CEP do endereço de entrega
				 'telefone' => $order_data['telephone'], // Telefone do cliente
				 'email' => $order_data['email'] // E-mail do cliente para envio da NF-e
			 );
		 }
		 $data['pedido'] = array(
         'pagamento' => 0, // Indicador da forma de pagamento
         'presenca' => 2, // Indicador de presença do comprador no estabelecimento comercial no momento da operação
         'modalidade_frete' => 0, // Modalidade do frete
         'frete' => number_format($shipping_total, 2), // Total do frete
         'desconto' => number_format($total_discounts, 2), // Total do desconto
         'total' => number_format($order_data['total'], 2), // Total do pedido
     );
		 //print_r($data);

		//die();
		return $data;
  }
}
?>