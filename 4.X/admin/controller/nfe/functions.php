<?php
require_once __DIR__ . '/Security.php';

class NFeFunctions {

    public function orderDocument($raw, array $ids): string {
        $fields = is_array($raw) ? $raw : \WebmaniaNFeSecurity::decode($raw);
        $type = $this->get_value_from_fields('tipo_pessoa', $ids, $fields);
        $key = (string) $type === (string) ($ids['pessoa_juridica'] ?? '') ? 'cnpj' : 'cpf';
        $document = $this->get_value_from_fields($key, $ids, $fields);
        return \WebmaniaNFeSecurity::normalizeDocument($document);
    }

    public function documentLink(array $invoice, string $key, string $fallback = ''): string {
        $url = $invoice[$key] ?? '';
        if (!\WebmaniaNFeSecurity::validUrl($url, true)) { return ''; }
        $uuid = $invoice['uuid'] ?? '';
        $password = !empty($invoice['documento']) ? $invoice['documento'] : $fallback;
        // Follow the published Webmania DFeToken algorithm for existing protected documents.
        $password = preg_replace('/[^0-9]/', '', is_scalar($password) ? (string) $password : '');
        if ($password === '' || !\WebmaniaNFeSecurity::validUuid($uuid) || !function_exists('openssl_encrypt')) { return $url; }
        $iv = random_bytes(openssl_cipher_iv_length('AES-256-CBC'));
        $encrypted = openssl_encrypt((string) time(), 'AES-256-CBC', hash('sha256', $password . ':' . $uuid, true), OPENSSL_RAW_DATA, $iv);
        if ($encrypted === false) { return $url; }
        $token = rawurlencode(base64_encode(json_encode(['data' => base64_encode($encrypted), 'iv' => base64_encode($iv)])));
        return $url . (strpos($url, '?') === false ? '?' : '&') . 'token=' . $token;
    }
    public static function controllerRoute(string $method): string {
        return 'extension/webmaniabrnfe/module/webmaniabrnfe' . (version_compare(VERSION, '4.0.2.0', '>=') ? '.' : '|') . $method;
    }


    public function isInstalled(object $class, ?bool $is_admin = null): bool {

        unset($class->session->data['status_sefaz']);

        if ($is_admin) {
            // Verify if curl command exist
            if (!function_exists('curl_version')) {
                $class->session->data['status_sefaz'] = '<strong>NF-e:</strong> Necessário instalar o comando cURL no servidor, entre em contato com a sua hospedagem ou administrador do servidor.';
                return false;
            }
        }

        $is_installed = $class->db->query("SELECT code FROM " . DB_PREFIX . "extension WHERE code = 'webmaniabrnfe'");
        return $is_installed->num_rows > 0;
    }

    public function cpf(?string $string): ?string {

        if (!$string) return null;
        $string = self::clear($string);
        $string = self::mask($string, '###.###.###-##');
        return $string;
    }

    public function cnpj(?string $string): ?string {
        $string = \WebmaniaNFeSecurity::normalizeDocument($string);
        if (!preg_match('/\A[A-Z0-9]{12}[0-9]{2}\z/', $string)) { return null; }
        return self::mask($string, '##.###.###/####-##');
    }

    public function cep(?string $string): ?string {

        if (!$string) return null;
        $string = self::clear($string);
        $string = self::mask($string, '#####-###');
        return $string;
    }

    public function clear(string $string): string {
        $string = is_scalar($string) ? (string) $string : '';

        return str_replace([',', '-', '!', '.', '/', '?', '(', ')', ' ', '$', 'R$', '€'], '', $string);
    }

    public function mask(string $val, string $mask): string {
        $val = is_scalar($val) ? (string) $val : '';

        $maskared = '';
        $k = 0;
        for ($i = 0; $i <= strlen($mask) - 1; $i++) {
            if ($mask[$i] == '#') {
                if (isset($val[$k]))
                    $maskared .= $val[$k++];
            } else {
                if (isset($mask[$i])) $maskared .= $mask[$i];
            }
        }
        return $maskared;
    }

    public function is_cpf(mixed $cpf = null): bool {
        if (is_array($cpf)) { $cpf = isset($cpf[0]) && is_scalar($cpf[0]) ? $cpf[0] : ''; }
        if (!is_scalar($cpf)) { return false; }
        $cpf = (string) $cpf;


        
        $cpf = preg_replace('/[^0-9]/', '', (string)$cpf);

        if (11 != strlen($cpf) || preg_match('/^([0-9])\1+$/', $cpf)) {
            return false;
        }

        $digit = substr($cpf, 0, 9);

        for ($j = 10; $j <= 11; $j++) {
            $sum = 0;

            for ($i = 0; $i < $j - 1; $i++) {
                $sum += ($j - $i) * ((int)$digit[$i]);
            }

            $summod11 = $sum % 11;
            $digit[$j - 1] = $summod11 < 2 ? 0 : 11 - $summod11;
        }

        return $digit[9] == ((int)$cpf[9]) && $digit[10] == ((int)$cpf[10]);
    }

    public function is_cnpj(mixed $cnpj = null): bool {
        if (is_array($cnpj)) { $cnpj = isset($cnpj[0]) && is_scalar($cnpj[0]) ? $cnpj[0] : ''; }
        return \WebmaniaNFeSecurity::validCnpj($cnpj);
    }

    public function getCustomFieldsIds(object $class, ?string $amb = null): array {

        $i = 0;
        $custom_fields = [];
        $saved_fields = $class->db->query("SELECT value FROM " . DB_PREFIX . "setting WHERE code = 'webmaniabrnfe' AND `key` = 'webmaniabrnfe_saved_fields'");
        if ($saved_fields->num_rows == 0) $saved_fields = [];
        else {
            $saved_fields = $saved_fields->row['value'];
            $saved_fields = \WebmaniaNFeSecurity::decode($saved_fields);
        }
        $query = $class->db->query("SELECT a.custom_field_id, a.name, b.sort_order, b.location FROM " . DB_PREFIX . "custom_field_description AS a INNER JOIN " . DB_PREFIX . "custom_field AS b ON a.custom_field_id = b.custom_field_id ORDER by b.sort_order ASC");
        $new_position_account = 6;
        $new_position_address = 4;

        // "Found" flags must survive the whole loop: resetting them per row made every
        // field look present as soon as the store had any custom field, so install never
        // created the missing fiscal fields.
        $tipo_pessoa = false;
        $cpf = false;
        $complemento = false;
        $cnpj = false;
        $numero = false;
        $ie = false;
        $razao_social = false;
        $pessoa_fisica = false;
        $pessoa_juridica = false;

        if ($query->num_rows > 0) {

            foreach ($query->rows as $row) {

                $field_id = $row['custom_field_id'];
                $name = $row['name'];
                $position = $row['sort_order'];
                $location = $row['location'];

                if (strpos($name, 'Tipo de Pessoa') !== false) {

                    $tipo_pessoa = true;
                    $custom_fields['tipo_pessoa'] = $field_id;
                    if ($position != 1) $class->db->query("UPDATE " . DB_PREFIX . "custom_field SET sort_order = 1 WHERE custom_field_id = '$field_id'");

                    $id_pessoa_fisica = $class->db->query("SELECT custom_field_value_id FROM " . DB_PREFIX . "custom_field_value_description WHERE name = 'Pessoa Física'");
                    $id_pessoa_juridica = $class->db->query("SELECT custom_field_value_id FROM " . DB_PREFIX . "custom_field_value_description WHERE name = 'Pessoa Jurídica'");
                    $id_pessoa_fisica = $id_pessoa_fisica->row['custom_field_value_id'] ?? null;
                    $id_pessoa_juridica = $id_pessoa_juridica->row['custom_field_value_id'] ?? null;

                    if ($id_pessoa_fisica) $pessoa_fisica = true;
                    if ($id_pessoa_juridica) $pessoa_juridica = true;

                    if (!$id_pessoa_fisica || !$id_pessoa_juridica) $class->session->data['error_warning'] = 'Por favor, configure corretamente o campo Tipo de Pessoa possuindo as opções de escolha de Pessoa Física e Pessoa Jurídica';

                    $custom_fields['pessoa_fisica'] = $id_pessoa_fisica;
                    $custom_fields['pessoa_juridica'] = $id_pessoa_juridica;

                } elseif (strpos($name, 'CPF') !== false) {

                    $cpf = true;
                    $custom_fields['cpf'] = $field_id;
                    if ($position != 2) $class->db->query("UPDATE " . DB_PREFIX . "custom_field SET sort_order = 2 WHERE custom_field_id = '$field_id'");

                } elseif (strpos($name, 'Complemento') !== false) {

                    $complemento = true;
                    $custom_fields['complemento'] = $field_id;
                    if ($position != 2) $class->db->query("UPDATE " . DB_PREFIX . "custom_field SET sort_order = 2 WHERE custom_field_id = '$field_id'");

                } elseif (strpos($name, 'CNPJ') !== false) {

                    $cnpj = true;
                    $custom_fields['cnpj'] = $field_id;
                    if ($position != 3) $class->db->query("UPDATE " . DB_PREFIX . "custom_field SET sort_order = 3 WHERE custom_field_id = '$field_id'");

                } elseif (strpos($name, 'Número') !== false || strpos($name, 'Nº') !== false || strpos($name, 'N.') !== false || (strpos($name, 'Número') !== false && strlen($name) == 1)) {

                    $numero = true;
                    $custom_fields['numero'] = $field_id;
                    if ($position != 3) $class->db->query("UPDATE " . DB_PREFIX . "custom_field SET sort_order = 3 WHERE custom_field_id = '$field_id'");

                } elseif (strpos($name, 'Inscrição Estadual') !== false || strpos($name, 'I.E') !== false) {

                    $ie = true;
                    $custom_fields['insc_est'] = $field_id;
                    if ($position != 4) $class->db->query("UPDATE " . DB_PREFIX . "custom_field SET sort_order = 4 WHERE custom_field_id = '$field_id'");

                } elseif (strpos($name, 'Razão Social') !== false) {

                    $razao_social = true;
                    $custom_fields['razao_social'] = $field_id;
                    if ($position != 5) $class->db->query("UPDATE " . DB_PREFIX . "custom_field SET sort_order = 5 WHERE custom_field_id = '$field_id'");

                } else {

                    if ($location == 'account') {

                        if ($new_position_account != $position) {

                            $class->db->query("UPDATE " . DB_PREFIX . "custom_field SET sort_order = $new_position_account WHERE custom_field_id = '$field_id'");
                        }

                        $new_position_account++;
                    }

                    if ($location == 'address') {

                        if ($new_position_address != $position) {

                            $class->db->query("UPDATE " . DB_PREFIX . "custom_field SET sort_order = $new_position_address WHERE custom_field_id = '$field_id'");
                        }

                        $new_position_address++;
                    }
                }

                $i++;
            }
        }

        if ($amb == 'backend') {

            $get_fields = $class->getCustomFields();

            if (!$tipo_pessoa) {

                $class->load->model('customer/custom_field');
                $class->model_customer_custom_field->addCustomField($get_fields['Tipo de Pessoa']);
                $id_tipo_pessoa = $class->db->query("SELECT custom_field_id FROM " . DB_PREFIX . "custom_field_description WHERE name LIKE '%Tipo de Pessoa%'");
                $id_pessoa_fisica = $class->db->query("SELECT custom_field_value_id FROM " . DB_PREFIX . "custom_field_value_description WHERE name = 'Pessoa Física'");
                $id_pessoa_juridica = $class->db->query("SELECT custom_field_value_id FROM " . DB_PREFIX . "custom_field_value_description WHERE name = 'Pessoa Jurídica'");
                $id_tipo_pessoa = $id_tipo_pessoa->row['custom_field_id'] ?? null;
                $id_pessoa_fisica = $id_pessoa_fisica->row['custom_field_value_id'] ?? null;
                $id_pessoa_juridica = $id_pessoa_juridica->row['custom_field_value_id'] ?? null;

                $custom_fields['tipo_pessoa'] = $id_tipo_pessoa;
                $custom_fields['pessoa_fisica'] = $id_pessoa_fisica;
                $custom_fields['pessoa_juridica'] = $id_pessoa_juridica;
            }

            if (!$cpf) {

                $class->load->model('customer/custom_field');
                $class->model_customer_custom_field->addCustomField($get_fields['CPF']);
                $id_cpf = $class->db->query("SELECT custom_field_id FROM " . DB_PREFIX . "custom_field_description WHERE name = 'CPF'");
                $id_cpf = $id_cpf->row['custom_field_id'] ?? null;

                $custom_fields['cpf'] = $id_cpf;
            }

            if (!$cnpj) {

                $class->load->model('customer/custom_field');
                $class->model_customer_custom_field->addCustomField($get_fields['CNPJ']);
                $id_cnpj = $class->db->query("SELECT custom_field_id FROM " . DB_PREFIX . "custom_field_description WHERE name = 'CNPJ'");
                $id_cnpj = $id_cnpj->row['custom_field_id'] ?? null;

                $custom_fields['cnpj'] = $id_cnpj;
            }

            if (!$complemento) {

                $class->load->model('customer/custom_field');
                $class->model_customer_custom_field->addCustomField($get_fields['Complemento']);
                $id_complemento = $class->db->query("SELECT custom_field_id FROM " . DB_PREFIX . "custom_field_description WHERE name LIKE '%Complemento%'");
                $id_complemento = $id_complemento->row['custom_field_id'] ?? null;

                $custom_fields['complemento'] = $id_complemento;
            }

            if (!$numero) {

                $class->load->model('customer/custom_field');
                $class->model_customer_custom_field->addCustomField($get_fields['Número']);
                $id_numero = $class->db->query("SELECT custom_field_id FROM " . DB_PREFIX . "custom_field_description WHERE name LIKE '%Número%'");
                $id_numero = $id_numero->row['custom_field_id'] ?? null;

                $custom_fields['numero'] = $id_numero;
            }

            if (!$ie) {

                $class->load->model('customer/custom_field');
                $class->model_customer_custom_field->addCustomField($get_fields['Inscrição Estadual']);
                $id_ie = $class->db->query("SELECT custom_field_id FROM " . DB_PREFIX . "custom_field_description WHERE name LIKE '%Inscrição Estadual%'");
                $id_ie = $id_ie->row['custom_field_id'] ?? null;

                $custom_fields['insc_est'] = $id_ie;
            }

            if (!$razao_social) {

                $class->load->model('customer/custom_field');
                $class->model_customer_custom_field->addCustomField($get_fields['Razão Social']);
                $id_razao_social = $class->db->query("SELECT custom_field_id FROM " . DB_PREFIX . "custom_field_description WHERE name LIKE '%Razão Social%'");
                $id_razao_social = $id_razao_social->row['custom_field_id'] ?? null;

                $custom_fields['razao_social'] = $id_razao_social;
            }

            foreach (array('cpf', 'cnpj', 'razao_social', 'insc_est') as $field) {
                $id = (int) ($custom_fields[$field] ?? 0);
                if ($id > 0) {
                    $class->db->query('UPDATE `' . DB_PREFIX . 'custom_field_customer_group` SET `required` = 0 WHERE `custom_field_id` = ' . $id . ' AND `required` <> 0');
                }
            }

            if (!$saved_fields) {

                if (!$saved_fields) $saved_fields = [];
                $this->save_fields($class, $saved_fields, $custom_fields);

            } else {

                $saved_fields = array_reverse($saved_fields);

                foreach ($custom_fields as $key => $value) {

                    if (($saved_fields[0][$key] ?? null) != $value) {

                        $this->save_fields($class, $saved_fields, $custom_fields, false);
                        break;
                    }
                }
            }
        }

        $custom_fields['log'] = $saved_fields;

        return $custom_fields;
    }

    public function save_fields(object $class, array $saved_fields, array $custom_fields, bool $insert = true): void {

        $saved_fields[] = $custom_fields;
        $saved_fields = serialize($saved_fields);

        if ($insert) {
            $class->db->query("INSERT INTO " . DB_PREFIX . "setting (`store_id`, `code`, `key`, `value`) VALUES ('0', 'webmaniabrnfe', 'webmaniabrnfe_saved_fields', '" . $class->db->escape($saved_fields) . "')");
        } else {

            $query = $class->db->query("SELECT setting_id FROM " . DB_PREFIX . "setting WHERE code = 'webmaniabrnfe' AND `key` = 'webmaniabrnfe_saved_fields'");
            $setting_id = $query->row['setting_id'] ?? null;
            if ($setting_id) {
                $class->db->query("UPDATE " . DB_PREFIX . "setting SET value = '" . $class->db->escape($saved_fields) . "' WHERE setting_id = '" . (int)$setting_id . "'");
            }
        }
    }

    // Create custom fields array (ready for insert model)
    public function getCustomFields(object $class): array {

        $custom_fields = [
            'Tipo de Pessoa' => [
                'location' => 'account',
                'type' => 'radio',
                'value' => '1',
                'validation' => '',
                'custom_field_customer_group' => [
                    [
                        'customer_group_id' => 1,
                        'required' => 1,
                    ]
                ],
                'status' => 1,
                'sort_order' => 1,
                'custom_field_value' => [
                    'Pessoa Física' => [
                        'sort_order' => 1
                    ],
                    'Pessoa Jurídica' => [
                        'sort_order' => 2
                    ],
                ],
            ],
            'CPF' => [
                'type' => 'text',
                'value' => '',
                'validation' => '',
                'location' => 'account',
                'status' => 1,
                'sort_order' => 2,
                'custom_field_customer_group' => [
                    [
                        'customer_group_id' => 1,
                        'required' => 0,
                    ]
                ],
            ],
            'CNPJ' => [
                'type' => 'text',
                'value' => '',
                'validation' => '',
                'location' => 'account',
                'status' => 1,
                'sort_order' => 3,
                'custom_field_customer_group' => [
                    [
                        'customer_group_id' => 1,
                        'required' => 0,
                    ]
                ],
            ],
            'Razão Social' => [
                'type' => 'text',
                'value' => '',
                'validation' => '',
                'location' => 'account',
                'status' => 1,
                'sort_order' => 4,
                'custom_field_customer_group' => [
                    [
                        'customer_group_id' => 1,
                        'required' => 0,
                    ]
                ],
            ],
            'Inscrição Estadual' => [
                'type' => 'text',
                'value' => '',
                'validation' => '',
                'location' => 'account',
                'status' => 1,
                'sort_order' => 5,
                'custom_field_customer_group' => [
                    [
                        'customer_group_id' => 1,
                        'required' => 0,
                    ]
                ],
            ],
            'Número' => [
                'type' => 'text',
                'value' => '',
                'validation' => '/[0-9]/',
                'location' => 'address',
                'status' => 1,
                'sort_order' => 2,
                'custom_field_customer_group' => [
                    [
                        'customer_group_id' => 1,
                        'required' => 1,
                    ]
                ],
            ],
            'Complemento' => [
                'type' => 'text',
                'value' => '',
                'validation' => '',
                'location' => 'address',
                'status' => 1,
                'sort_order' => 2,
                'custom_field_customer_group' => [
                    [
                        'customer_group_id' => 1,
                        'required' => 0,
                    ]
                ],
            ],
        ];

        foreach ($custom_fields as &$field) {
            foreach ($field['custom_field_customer_group'] as &$group) {
                // OpenCart marks the field required when the key exists, even if its value is 0.
                if (empty($group['required'])) { unset($group['required']); }
            }
            unset($group);
            if (isset($field['custom_field_value'])) {
                foreach ($field['custom_field_value'] as &$value) { $value['custom_field_value_id'] = 0; }
                unset($value);
            }
        }
        unset($field);

        $languages = $class->getLanguages();

        if (isset($languages->rows) && is_array($languages->rows) && count($languages->rows) > 0) {

            foreach ($languages->rows as $language) {

                foreach ($custom_fields as $name => &$custom_field) {

                    $custom_field['custom_field_description'][$language['language_id']] = ['name' => $name];

                    if (isset($custom_field['custom_field_value'])) {

                        foreach ($custom_field['custom_field_value'] as $value_name => &$value_arr) {

                            $value_arr['custom_field_value_description'][$language['language_id']] = ['name' => $value_name];
                        }
                        unset($value_arr);
                    }
                }
                unset($custom_field);
            }
        }

        return $custom_fields;
    }

    // Get fields from default or Log
    public function get_value_from_fields(string $key, array $custom_fields_ids, ?array $custom_fields_customer): mixed {

        $custom_fields_customer_fix = [];

        if ($custom_fields_customer) {
            foreach ($custom_fields_customer as $key_value => $field) {
                $custom_fields_customer_fix[$key_value . '_id'] = $field;
            }
        }

        if (in_array($key, ['pessoa_fisica', 'pessoa_juridica', 'numero', 'complemento'])) {

            $a = $custom_fields_ids[$key] ?? null;
            $k = array_search($a, $custom_fields_ids);
            return $custom_fields_ids[$k] ?? null;
        }

        if (isset($custom_fields_customer_fix[($custom_fields_ids[$key] ?? '') . '_id'])) {
            return $custom_fields_customer_fix[($custom_fields_ids[$key] ?? '') . '_id'];
        }

        if ($custom_fields_ids['log'] ?? null) {

            foreach ($custom_fields_ids['log'] as $log) {

                if (in_array($key, ['pessoa_fisica', 'pessoa_juridica', 'numero', 'complemento'])) {

                    $a = $log[$key] ?? null;
                    $k = array_search($a, $log);
                    return $log[$k] ?? null;

                } else {
                    if (isset($custom_fields_customer[$log[$key] ?? ''])) {
                        return $custom_fields_customer[$log[$key]];
                    }
                }
            }

            return false;
        }

        return null;
    }
}
