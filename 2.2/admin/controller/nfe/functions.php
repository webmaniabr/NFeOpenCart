<?php

class NFeFunctions {

  public function isInstalled( $class, $is_admin = null ){

    unset($class->session->data['status_sefaz']);

    if ($is_admin){

        // Verify if curl command exist
        if (!function_exists('curl_version')){

              $class->session->data['status_sefaz'] = '<strong>NF-e:</strong> Necessário instalar o comando cURL no servidor, entre em contato com a sua hospedagem ou administrador do servidor.';
              return false;

        }

    }

    $is_installed = $class->db->query("SELECT code FROM " . DB_PREFIX . "extension WHERE code = 'webmaniabrnfe'");
    if($is_installed->num_rows) return true; else return false;

  }

  function cpf( $string ){

		if (!$string) return;
		$string = self::clear( $string );
		$string = self::mask($string,'###.###.###-##');
		return $string;

	}

	function cnpj( $string ){

		if (!$string) return;
		$string = self::clear( $string );
		$string = self::mask($string,'##.###.###/####-##');
		return $string;

	}

	function cep( $string ){

		if (!$string) return;
		$string = self::clear( $string );
		$string = self::mask($string,'#####-###');
		return $string;

	}

	function clear( $string ) {

        $string = str_replace( array(',', '-', '!', '.', '/', '?', '(', ')', ' ', '$', 'R$', '€'), '', $string );
        return $string;

	}

	function mask($val, $mask) {
	   $maskared = '';
	   $k = 0;
	   for($i = 0; $i<=strlen($mask)-1; $i++){
           if($mask[$i] == '#'){
               if(isset($val[$k]))
                   $maskared .= $val[$k++];
           }
           else {
               if(isset($mask[$i])) $maskared .= $mask[$i];
           }
	   }
	   return $maskared;
	}

    function is_cpf($cpf = null){

        if(is_array($cpf)) $cpf = $cpf[0];
        $cpf = preg_replace( '/[^0-9]/', '', $cpf );

        if ( 11 != strlen( $cpf ) || preg_match( '/^([0-9])\1+$/', $cpf ) ) {
          return false;
        }

        $digit = substr( $cpf, 0, 9 );

        for ( $j = 10; $j <= 11; $j++ ) {
            $sum = 0;

            for( $i = 0; $i< $j-1; $i++ ) {
                $sum += ( $j - $i ) * ( (int) $digit[ $i ] );
            }

            $summod11 = $sum % 11;
            $digit[ $j - 1 ] = $summod11 < 2 ? 0 : 11 - $summod11;
        }

        return $digit[9] == ( (int) $cpf[9] ) && $digit[10] == ( (int) $cpf[10] );

    }

    function is_cnpj($cnpj = null) {

        if(is_array($cnpj)) $cnpj = $cnpj[0];
        $cnpj = sprintf( '%014s', preg_replace( '{\D}', '', $cnpj ) );

        if ( 14 != ( strlen( $cnpj ) ) || ( 0 == intval( substr( $cnpj, -4 ) ) ) ) {
          return false;
        }

        for ( $t = 11; $t < 13; ) {
          for ( $d = 0, $p = 2, $c = $t; $c >= 0; $c--, ( $p < 9 ) ? $p++ : $p = 2 ) {
            $d += $cnpj[ $c ] * $p;
          }

          if ( $cnpj[ ++$t ] != ( $d = ( ( 10 * $d ) % 11 ) % 10 ) ) {
            return false;
          }
        }

        return true;

    }

    function getCustomFieldsIds( $class, $amb = null ){

      $i = 0;
      $custom_fields = array();
      $saved_fields = $class->db->query("SELECT value FROM ".DB_PREFIX."setting WHERE code = 'webmaniabrnfe' AND `key` = 'webmaniabrnfe_saved_fields'");
      if ($saved_fields->num_rows == 0) $saved_fields = array();
      else { $saved_fields = $saved_fields->row['value']; $saved_fields = unserialize($saved_fields); }
      $query = $class->db->query("SELECT a.custom_field_id, a.name, b.sort_order, b.location FROM ".DB_PREFIX."custom_field_description AS a INNER JOIN ".DB_PREFIX."custom_field AS b ON a.custom_field_id = b.custom_field_id ORDER by b.sort_order ASC");
      $new_position_account = 6;
      $new_position_address = 4;

      if ($query->num_rows > 0){

          foreach ($query->rows as $row){

              $field_id = $row['custom_field_id'];
              $name = $row['name'];
              $position = $row['sort_order'];
              $location = $row['location'];

              if (strpos($name, 'Tipo de Pessoa') !== false) {

                  $tipo_pessoa = true;
                  $custom_fields['tipo_pessoa'] = $field_id;
                  if ($position != 1) $class->db->query("UPDATE ".DB_PREFIX."custom_field SET sort_order = 1 WHERE custom_field_id = '$field_id'");

                  $id_pessoa_fisica = $class->db->query("SELECT custom_field_value_id FROM " . DB_PREFIX . "custom_field_value_description WHERE name = 'Pessoa Física'");
                  $id_pessoa_juridica = $class->db->query("SELECT custom_field_value_id FROM " . DB_PREFIX . "custom_field_value_description WHERE name = 'Pessoa Jurídica'");
                  $id_pessoa_fisica = $id_pessoa_fisica->row['custom_field_value_id'];
                  $id_pessoa_juridica = $id_pessoa_juridica->row['custom_field_value_id'];

                  if ($id_pessoa_fisica) $pessoa_fisica = true;
                  if ($id_pessoa_juridica) $pessoa_juridica = true;

                  if (!$id_pessoa_fisica || !$id_pessoa_juridica) $class->session->data['error_warning'] == 'Por favor, configure corretamente o campo Tipo de Pessoa possuindo as opções de escolha de Pessoa Física e Pessoa Jurídica';

                  $custom_fields['pessoa_fisica'] = $id_pessoa_fisica;
                  $custom_fields['pessoa_juridica'] = $id_pessoa_juridica;

              } elseif (strpos($name, 'CPF') !== false) {

                  $cpf = true;
                  $custom_fields['cpf'] = $field_id;
                  if ($position != 2) $class->db->query("UPDATE ".DB_PREFIX."custom_field SET sort_order = 2 WHERE custom_field_id = '$field_id'");

              } elseif (strpos($name, 'Complemento') !== false) {

                  $complemento = true;
                  $custom_fields['complemento'] = $field_id;
                  if ($position != 2) $class->db->query("UPDATE ".DB_PREFIX."custom_field SET sort_order = 2 WHERE custom_field_id = '$field_id'");

              } elseif (strpos($name, 'CNPJ') !== false) {

                  $cnpj = true;
                  $custom_fields['cnpj'] = $field_id;
                  if ($position != 3) $class->db->query("UPDATE ".DB_PREFIX."custom_field SET sort_order = 3 WHERE custom_field_id = '$field_id'");

              } elseif (strpos($name, 'Número') !== false || strpos($name, 'Nº') !== false || strpos($name, 'N.') !== false || (strpos($name, 'Número') !== false && strlen($name) == 1)) {

                  $numero = true;
                  $custom_fields['numero'] = $field_id;
                  if ($position != 3) $class->db->query("UPDATE ".DB_PREFIX."custom_field SET sort_order = 3 WHERE custom_field_id = '$field_id'");

              } elseif (strpos($name, 'Inscrição Estadual') !== false || strpos($name, 'I.E') !== false) {

                  $ie = true;
                  $custom_fields['insc_est'] = $field_id;
                  if ($position != 4) $class->db->query("UPDATE ".DB_PREFIX."custom_field SET sort_order = 4 WHERE custom_field_id = '$field_id'");

              } elseif (strpos($name, 'Razão Social') !== false) {

                  $razao_social = true;
                  $custom_fields['razao_social'] = $field_id;
                  if ($position != 5) $class->db->query("UPDATE ".DB_PREFIX."custom_field SET sort_order = 5 WHERE custom_field_id = '$field_id'");

              } else {

                  if ($location == 'account') {

                      if ($new_position_account != $position){

                          $class->db->query("UPDATE ".DB_PREFIX."custom_field SET sort_order = $new_position_account WHERE custom_field_id = '$field_id'");

                      }

                      $new_position_account++;

                  }

                  if ($location == 'address') {

                      if ($new_position_address != $position){

                          $class->db->query("UPDATE ".DB_PREFIX."custom_field SET sort_order = $new_position_address WHERE custom_field_id = '$field_id'");

                      }

                      $new_position_address++;

                  }

              }

              $i++;

          }

      }

      if ($amb == 'backend'){

          $get_fields = $class->getCustomFields();


          if (!isset($tipo_pessoa)){

              $class->load->model('sale/custom_field');
              $class->model_sale_custom_field->addCustomField($get_fields['Tipo de Pessoa']);
              $id_tipo_pessoa = $class->db->query("SELECT custom_field_id FROM " . DB_PREFIX . "custom_field_description WHERE name LIKE '%Tipo de Pessoa%'");
              $id_pessoa_fisica = $class->db->query("SELECT custom_field_value_id FROM " . DB_PREFIX . "custom_field_value_description WHERE name = 'Pessoa Física'");
              $id_pessoa_juridica = $class->db->query("SELECT custom_field_value_id FROM " . DB_PREFIX . "custom_field_value_description WHERE name = 'Pessoa Jurídica'");
              $id_tipo_pessoa = $id_tipo_pessoa->row['custom_field_id'];
              $id_pessoa_fisica = $id_pessoa_fisica->row['custom_field_value_id'];
              $id_pessoa_juridica = $id_pessoa_juridica->row['custom_field_value_id'];

              $custom_fields['tipo_pessoa'] = $id_tipo_pessoa;
              $custom_fields['pessoa_fisica'] = $id_pessoa_fisica;
              $custom_fields['pessoa_juridica'] = $id_pessoa_juridica;

          }

          if (!isset($cpf)){

              $class->load->model('sale/custom_field');
              $class->model_sale_custom_field->addCustomField($get_fields['CPF']);
              $id_cpf = $class->db->query("SELECT custom_field_id FROM " . DB_PREFIX . "custom_field_description WHERE name = 'CPF'" );
              $id_cpf = $id_cpf->row['custom_field_id'];

              $custom_fields['cpf'] = $id_cpf;

          }

          if (!isset($cnpj)){

              $class->load->model('sale/custom_field');
              $class->model_sale_custom_field->addCustomField($get_fields['CNPJ']);
              $id_cnpj = $class->db->query("SELECT custom_field_id FROM " . DB_PREFIX . "custom_field_description WHERE name = 'CNPJ'" );
              $id_cnpj = $id_cnpj->row['custom_field_id'];

               $custom_fields['cnpj'] = $id_cnpj;

          }

          if (!isset($complemento)){

              $class->load->model('sale/custom_field');
              $class->model_sale_custom_field->addCustomField($get_fields['Complemento']);
              $id_complemento = $class->db->query("SELECT custom_field_id FROM " . DB_PREFIX . "custom_field_description WHERE name LIKE '%Complemento%'" );
              $id_complemento = $id_complemento->row['custom_field_id'];

               $custom_fields['complemento'] = $id_complemento;

          }

          if (!isset($numero)){

              $class->load->model('sale/custom_field');
              $class->model_sale_custom_field->addCustomField($get_fields['Número']);
              $id_numero = $class->db->query("SELECT custom_field_id FROM " . DB_PREFIX . "custom_field_description WHERE name LIKE '%Número%'" );
              $id_numero = $id_numero->row['custom_field_id'];

              $custom_fields['numero'] = $id_numero;

          }

          if (!isset($ie)){

              $class->load->model('sale/custom_field');
              $class->model_sale_custom_field->addCustomField($get_fields['Inscrição Estadual']);
              $id_ie = $class->db->query("SELECT custom_field_id FROM " . DB_PREFIX . "custom_field_description WHERE name LIKE '%Inscrição Estadual%'" );
              $id_ie = $id_ie->row['custom_field_id'];

              $custom_fields['insc_est'] = $id_ie;

          }

          if (!isset($razao_social)){

              $class->load->model('sale/custom_field');
              $class->model_sale_custom_field->addCustomField($get_fields['Razão Social']);
              $id_razao_social = $class->db->query("SELECT custom_field_id FROM " . DB_PREFIX . "custom_field_description WHERE name LIKE '%Razão Social%'" );
              $id_razao_social = $id_razao_social->row['custom_field_id'];

              $custom_fields['razao_social'] = $id_razao_social;

          }

          if (!$saved_fields){

              if (!$saved_fields) $saved_fields = array();
              $this->save_fields( $class, $saved_fields, $custom_fields );

          } else {

              $saved_fields = array_reverse($saved_fields);

              foreach ($custom_fields as $key => $value){

                  if ($saved_fields[0][$key] != $value){

                      $this->save_fields( $class, $saved_fields, $custom_fields, false );
                      break;

                  }

              }

          }

      }

      $custom_fields['log'] = $saved_fields;

      return $custom_fields;

    }

    function save_fields( $class, $saved_fields, $custom_fields, $insert = true ){

        $saved_fields[] = $custom_fields;
        $saved_fields = serialize($saved_fields);

        if ($insert) $class->db->query("INSERT INTO ".DB_PREFIX."setting (`store_id`, `code`, `key`, `value`) VALUES ('0', 'webmaniabrnfe', 'webmaniabrnfe_saved_fields', '$saved_fields')");

        else {

            $query = $class->db->query("SELECT setting_id FROM ".DB_PREFIX."setting WHERE code = 'webmaniabrnfe' AND `key` = 'webmaniabrnfe_saved_fields'");
            $setting_id = $query->row['setting_id'];
            $class->db->query("UPDATE ".DB_PREFIX."setting SET value = '$saved_fields' WHERE setting_id = '$setting_id'");

        }

    }

    // Create custom fields array (ready for insert model)
    function getCustomFields( $class ){

      $custom_fields = array(
        'Tipo de Pessoa' => array(
          'location' => 'account',
          'type' => 'radio',
          'value' => '1',
          'validation' => '',
          'custom_field_customer_group' => array(
            array(
              'customer_group_id' => 1,
              'required' => 1,
            )
          ),
          'status' => 1,
          'sort_order' => 1,
          'custom_field_value' => array(
            'Pessoa Física' => array(
              'sort_order' => 1
            ),
            'Pessoa Jurídica' => array(
              'sort_order' => 2
            ),
          ),
        ),
        'CPF' => array(
          'type' => 'text',
          'value' => '',
          'validation' => '',
          'location' => 'account',
          'status' => 1,
          'sort_order' => 2,
          'custom_field_customer_group' => array(
            array(
              'customer_group_id' => 1,
              'required' => 1,
            )
          ),
        ),
        'CNPJ' => array(
          'type' => 'text',
          'value' => '',
          'validation' => '',
          'location' => 'account',
          'status' => 1,
          'sort_order' => 3,
          'custom_field_customer_group' => array(
            array(
              'customer_group_id' => 1,
              'required' => 1,
            )
          ),
        ),
        'Razão Social' => array(
          'type' => 'text',
          'value' => '',
          'validation' => '',
          'location' => 'account',
          'status' => 1,
          'sort_order' => 4,
          'custom_field_customer_group' => array(
            array(
              'customer_group_id' => 1,
              'required' => 1,
            )
          ),
        ),
        'Inscrição Estadual' => array(
          'type' => 'text',
          'value' => '',
          'validation' => '',
          'location' => 'account',
          'status' => 1,
          'sort_order' => 5,
          'custom_field_customer_group' => array(
            array(
              'customer_group_id' => 1,
              'required' => 1,
            )
          ),
        ),
        'Número' => array(
          'type' => 'text',
          'value' => '',
          'validation' => '/[0-9]/',
          'location' => 'address',
          'status' => 1,
          'sort_order' => 2,
          'custom_field_customer_group' => array(
            array(
              'customer_group_id' => 1,
              'required' => 1,
            )
          ),
        ),
        'Complemento' => array(
          'type' => 'text',
          'value' => '',
          'validation' => '',
          'location' => 'address',
          'status' => 1,
          'sort_order' => 2,
          'custom_field_customer_group' => array(
            array(
              'customer_group_id' => 1,
              'required' => '',
            )
          ),
        ),
      );

      $languages = $class->getLanguages();

      if(isset($languages->rows) && is_array($languages->rows) && count($languages->rows) > 0){

        foreach($languages->rows as $language){

          foreach($custom_fields as $name => &$custom_field){

            $custom_field['custom_field_description'][$language['language_id']] = array('name' => $name);

              if(isset($custom_field['custom_field_value'])){

                  foreach($custom_field['custom_field_value'] as $value_name => &$value_arr){

                    $value_arr['custom_field_value_description'][$language['language_id']] = array('name' => $value_name);

                  }

            }

          }

        }

      }

      return $custom_fields;

    }

    // Get fields from default or Log
    function get_value_from_fields( $key, $custom_fields_ids, $custom_fields_customer ){

        if ($key == 'pessoa_fisica' || $key == 'pessoa_juridica' || $key == 'numero' || $key == 'complemento') {

            $a = $custom_fields_ids[$key];
            $k = array_search($a, $custom_fields_ids);
            return $custom_fields_ids[$k];

        }


        if (isset($custom_fields_customer[$custom_fields_ids[$key]])) return $custom_fields_customer[$custom_fields_ids[$key]];

        if ($custom_fields_ids['log']){

            foreach ($custom_fields_ids['log'] as $log){

                if ($key == 'pessoa_fisica' || $key == 'pessoa_juridica' || $key == 'numero' || $key == 'complemento') {

                    $a = $log[$key];
                    $k = array_search($a, $log);
                    return $log[$k];

                }
                else { if (isset($custom_fields_customer[$log[$key]])) return $custom_fields_customer[$log[$key]]; }

            }

            return false;

        }

    }

}
