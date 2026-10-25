<?php
namespace Opencart\Admin\Controller\Extension\Webmaniabrnfe\Module;

class Webmaniabrnfe extends \Opencart\System\Engine\Controller {

    private array $error = [];
    public ?object $NFe = null;
    public ?array $module_settings = null;
    private ?object $NFeFunctions = null;

    public function __construct($registry) {

        parent::__construct($registry);

        require_once __DIR__ . '/../nfe/NFe.php';
        require_once __DIR__ . '/../nfe/functions.php';
        require_once __DIR__ . '/../pdf/PDFMerger.php';

        $this->NFeFunctions = new \NFeFunctions();

        if (!$this->NFeFunctions->isInstalled($this, true)) {
            return false;
        }
        if (!$this->checkAuthentication()) {
            return false;
        }

        $this->NFe = $this->getNFe();
    }

    public function index(): void {

        $this->load->language('extension/webmaniabrnfe/module/webmaniabrnfe');
        if ($this->user->hasPermission('modify', 'extension/webmaniabrnfe/module/webmaniabrnfe')) {
            $this->getCustomFieldsIds();
            if ($this->config->get('module_webmaniabrnfe_status')) { $this->registerEvents(); }
        }

        $this->document->setTitle($this->language->get('heading_title'));
        $this->document->addStyle('../extension/webmaniabrnfe/admin/view/stylesheet/wmbr_style.css');
        $this->document->addScript('../extension/webmaniabrnfe/catalog/view/javascript/jquery.mask.min.js');
        $this->document->addScript('../extension/webmaniabrnfe/admin/view/javascript/wmbr_scripts.js');

        // Load the Setting Model
        $this->load->model('setting/setting');

        // Save settings if valid
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $saved = $this->model_setting_setting->getSetting('webmaniabrnfe');
      foreach (array('consumer_key', 'consumer_secret', 'access_token', 'access_token_secret', 'uniq_get_key') as $field) {
          $key = 'webmaniabrnfe_' . $field;
          if ($field === 'uniq_get_key' || !isset($this->request->post[$key]) || $this->request->post[$key] === '' || !is_string($this->request->post[$key])) {
              $this->request->post[$key] = isset($saved[$key]) ? $saved[$key] : '';
          }
      }
      // Forms and older templates may submit only part of the settings; retain omitted mappings.
      $this->model_setting_setting->editSetting('webmaniabrnfe', array_replace(is_array($saved) ? $saved : [], $this->request->post));

            if (isset($this->request->post['module_webmaniabrnfe_status'])) {
                if ($this->request->post['module_webmaniabrnfe_status'] === "1") {
                    $this->activeModule();
                } else {
                    $this->disableModule();
                }
            }
            
            // Displays the success text on data save
            $this->session->data['success'] = $this->language->get('text_success');

            // Redirect to the Module Listing
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
        }

        // Assign the language data for parsing it to view
        $language_data = [
            'heading_title' => 'heading_title',
            'text_edit' => 'text_edit',
            'text_enabled' => 'text_enabled',
            'text_disabled' => 'text_disabled',
            'text_content_top' => 'text_content_top',
            'text_content_bottom' => 'text_content_bottom',
            'text_column_left' => 'text_column_left',
            'text_column_right' => 'text_column_right',
            'entry_code' => 'entry_code',
            'entry_layout' => 'entry_layout',
            'entry_position' => 'entry_position',
            'entry_status' => 'entry_status',
            'entry_sort_order' => 'entry_sort_order',
            'button_save' => 'button_save',
            'button_edit' => 'button_edit',
            'button_cancel' => 'button_cancel',
            'button_add_module' => 'button_add_module',
            'button_remove' => 'button_remove',
        ];

        $data = [];
        foreach ($language_data as $key => $value) {
            $data[$key] = $this->language->get($value);
        }

        // Add warnings
        $data['error_warning'] = $this->error['warning'] ?? '';

        //Add success
        $data['success_message'] = $this->session->data['success'] ?? '';

        // This Block returns the error code if any
        $data['error_code'] = $this->error['code'] ?? '';

        // Making of Breadcrumbs to be displayed on site
        $data['breadcrumbs'] = [];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
            'separator' => false
        ];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], true),
            'separator' => ' :: '
        ];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/webmaniabrnfe/module/webmaniabrnfe', 'user_token=' . $this->session->data['user_token'], true),
            'separator' => ' :: '
        ];

        $data['action'] = $this->url->link('extension/webmaniabrnfe/module/webmaniabrnfe', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

        $settings_fields = [
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
        ];

        foreach ($settings_fields as $field) {
            if (isset($this->request->post['webmaniabrnfe_' . $field])) {
                $data['webmaniabrnfe_' . $field] = $this->request->post['webmaniabrnfe_' . $field];
            } else {
                $data['webmaniabrnfe_' . $field] = $this->config->get('webmaniabrnfe_' . $field);
            }
        }

        $data['module_webmaniabrnfe_status'] = $this->request->post['module_webmaniabrnfe_status'] ?? $this->config->get('module_webmaniabrnfe_status');
        $data['webmaniabrnfe_carriers_d'] = json_decode(stripslashes(html_entity_decode($data['webmaniabrnfe_carriers'] ?? '')), true);

        // Saved credentials are never echoed back; blank fields keep them (see the POST handler above).
        $data['webmaniabrnfe_credentials_saved'] = true;
        foreach (array('consumer_key', 'consumer_secret', 'access_token', 'access_token_secret') as $field) {
        if (!$this->config->get('webmaniabrnfe_' . $field)) { $data['webmaniabrnfe_credentials_saved'] = false; }
        $data['webmaniabrnfe_' . $field] = '';
    }
    $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        //Load shipping methods
        $this->load->model('setting/extension');

        //Get all installed payment methods
        $results = $this->model_setting_extension->getExtensionsByType('payment');

        $payment_methods = [];

        foreach ($results as $payment) {
            $this->load->language('extension/payment/' . $payment['code']);
            $payment_methods[$payment['code']] = $this->language->get('heading_title');

            if (isset($this->request->post['payment_' . $payment['code']])) {
                $data['webmaniabrnfe_payment_' . $payment['code']] = $this->request->post['webmaniabrnfe_payment_' . $payment['code']];
                $data['webmaniabrnfe_payment_' . $payment['code'] . '_desc'] = $this->request->post['webmaniabrnfe_payment_' . $payment['code'] . '_desc'];
            } else {
                $data['webmaniabrnfe_payment_' . $payment['code']] = $this->config->get('webmaniabrnfe_payment_' . $payment['code']);
                $data['webmaniabrnfe_payment_' . $payment['code'] . '_desc'] = $this->config->get('webmaniabrnfe_payment_' . $payment['code'] . '_desc');
            }
        }

        //Get all installed shipping methods
        $results = $this->model_setting_extension->getExtensionsByType('shipping');
        $methods = [];

        foreach ($results as $shipping_method) {
            $this->load->language('extension/shipping/' . $shipping_method['code']);
            $methods[$shipping_method['code']] = $this->language->get('heading_title');
        }

        $data['methods'] = $methods;
        $data['payment_methods'] = $payment_methods;

        //Reload module language
        $this->load->language('extension/webmaniabrnfe/module/webmaniabrnfe');

        $this->response->setOutput($this->load->view('extension/webmaniabrnfe/module/webmaniabrnfe', $data));
    }

    /**
     * Clean cache vqMod
     */
    public function cleanCache(): void {
        // OpenCart 4 uses native events; no vQmod cache is needed.
}

    /**
     * Install
     */
    private function eventMap(): array {
        return array(
            'wmbr_nfe_header' => array('catalog/controller/common/header/before', 'catalogHeader', 'module'),
            'wmbr_nfe_footer' => array('catalog/view/common/footer/after', 'catalogFooter', 'module'),
            'wmbr_nfe_customer_order' => array('catalog/view/account/order_info/after', 'customerOrderInfo', 'module'),
            'wmbr_nfe_account_register' => array('catalog/controller/account/register.register/before', 'validateAccount', 'module'),
            'wmbr_nfe_account_edit' => array('catalog/controller/account/edit.save/before', 'validateAccount', 'module'),
            'wmbr_nfe_checkout_register' => array('catalog/controller/checkout/register.save/before', 'validateAccount', 'module'),
            'wmbr_nfe_order_info' => array('admin/view/sale/order_info/after', 'orderInfo', 'event'),
            'wmbr_nfe_order_list' => array('admin/view/sale/order_list/after', 'orderList', 'event'),
            'wmbr_nfe_product_form' => array('admin/view/catalog/product_form/after', 'productForm', 'event'),
            'wmbr_nfe_category_form' => array('admin/view/catalog/category_form/after', 'categoryForm', 'event'),
            'wmbr_nfe_product_add' => array('admin/model/catalog/product/addProduct/after', 'productSaved', 'event'),
            'wmbr_nfe_product_edit' => array('admin/model/catalog/product/editProduct/after', 'productSaved', 'event'),
            'wmbr_nfe_category_add' => array('admin/model/catalog/category/addCategory/after', 'categorySaved', 'event'),
            'wmbr_nfe_category_edit' => array('admin/model/catalog/category/editCategory/after', 'categorySaved', 'event')
        );
    }

    private function registerEvents(): void {
        $this->load->model('setting/event');
        $separator = version_compare(VERSION, '4.0.2.0', '>=') ? '.' : '|';
        foreach ($this->eventMap() as $code => $info) {
            if (version_compare(VERSION, '4.1.0.0', '>=')) { $info[0] = preg_replace('#/(addProduct|editProduct|addCategory|editCategory)/after$#', '.$1/after', $info[0]); }
            $this->model_setting_event->deleteEventByCode($code);
            $route = $info[2] === 'event' ? 'extension/webmaniabrnfe/event/nfe' : 'extension/webmaniabrnfe/module/webmaniabrnfe';
            $this->model_setting_event->addEvent(array('code' => $code, 'description' => 'Webmania NF-e', 'trigger' => $info[0], 'action' => $route . $separator . $info[1], 'status' => 1, 'sort_order' => 0));
        }
    }

    public function install(): void {
        if (!$this->user->hasPermission('modify', 'extension/webmaniabrnfe/module/webmaniabrnfe') && !$this->user->hasPermission('modify', 'extension/module')) { return; }


        $this->getCustomFieldsIds();
        $store_id = (int) $this->config->get('config_store_id');
        $this->load->model('setting/setting');
        $this->model_setting_setting->editValue('config', 'config_checkout_guest', 0, $store_id);

        // Check each column separately so interrupted installations can be resumed.
        $columns = array(
            'category' => array('category_ncm' => 'VARCHAR(15)'),
            'product' => array(
                'classe_imposto' => 'VARCHAR(15)', 'ean_barcode' => 'VARCHAR(15)',
                'gtin_tributavel' => 'VARCHAR(15)', 'ncm_code' => 'VARCHAR(15)',
                'cest_code' => 'VARCHAR(15)', 'product_source' => "VARCHAR(15) DEFAULT '-1'",
                'ignorar_nfe' => "VARCHAR(5) DEFAULT '0'", 'cnpj_fabricante' => 'VARCHAR(20)', 'ind_escala' => 'VARCHAR(5)'
            ),
            'order' => array(
                'status_nfe' => 'TINYINT NOT NULL DEFAULT 0', 'nfe_info' => 'MEDIUMTEXT',
                'nfe_transporte_modalidade_frete' => 'VARCHAR(15)', 'nfe_transporte_volume' => 'VARCHAR(15)',
                'nfe_transporte_especie' => 'VARCHAR(15)', 'nfe_transporte_peso_bruto' => 'VARCHAR(15)', 'nfe_transporte_peso_liquido' => 'VARCHAR(15)'
            )
        );
        foreach ($columns as $table => $definitions) {
            foreach ($definitions as $column => $definition) {
                $exists = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . $table . "` LIKE '" . $column . "'");
                if (!$exists->num_rows) {
                    $this->db->query('ALTER TABLE `' . DB_PREFIX . $table . '` ADD `' . $column . '` ' . $definition);
                }
            }
        }
        $this->activeModule();
    }

    public function uninstall(): void {
        if (!$this->user->hasPermission('modify', 'extension/webmaniabrnfe/module/webmaniabrnfe') && !$this->user->hasPermission('modify', 'extension/module')) { return; }
        $this->disableModule();
        $this->load->model('setting/event');
        foreach (array_keys($this->eventMap()) as $code) { $this->model_setting_event->deleteEventByCode($code); }

}

    private function disableModule(): void {
        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting('module_webmaniabrnfe', array('module_webmaniabrnfe_status' => '0'));

}

    private function activeModule(): void {
        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting('module_webmaniabrnfe', array('module_webmaniabrnfe_status' => '1'));
        $this->registerEvents();

}

    /* Function that validates the data when Save Button is pressed */
    protected function validate(): bool {

        // Block to check the user permission to manipulate the module
        if (!$this->user->hasPermission('modify', 'extension/webmaniabrnfe/module/webmaniabrnfe')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        // Block returns true if no error is found, else false if any error detected
        return !$this->error;
    }

    //Languages for custom_field_description
    public function getLanguages(): object {
        return $this->db->query("SELECT language_id FROM " . DB_PREFIX . "language");
    }

    public function getModuleSettings(): ?array {

        if (is_null($this->module_settings)) {
            $this->load->model('setting/setting');
            $this->module_settings = $this->model_setting_setting->getSetting('webmaniabrnfe');
        }

        return $this->module_settings;
    }

    //Check if authentication values are set in module configuration
    public function checkAuthentication(): bool {

        $settings = $this->getModuleSettings();
        $settings_arr = [
            'webmaniabrnfe_access_token',
            'webmaniabrnfe_access_token_secret',
            'webmaniabrnfe_consumer_key',
            'webmaniabrnfe_consumer_secret',
        ];

        foreach ($settings_arr as $setting_val) {
            if (!isset($settings[$setting_val]) || empty($settings[$setting_val])) {
                $this->session->data['status_sefaz'] = '<strong>Opencart NF-e:</strong> Informe as credenciais de acesso da aplicação em Extensões > Módulos > WebmaniaBR NF-e.';
                return false;
            }
        }

        return true;
    }

    //Get NFe object and create in case it doesnt exist
    public function getNFe(): ?object {

        if (!is_object($this->NFe)) {
            $module_settings = $this->getModuleSettings();

            $map = [
                'oauth_access_token' => 'webmaniabrnfe_access_token',
                'oauth_access_token_secret' => 'webmaniabrnfe_access_token_secret',
                'consumer_key' => 'webmaniabrnfe_consumer_key',
                'consumer_secret' => 'webmaniabrnfe_consumer_secret'
            ];

            $settings = [];

            foreach ($map as $oauth => $setting_key) {
                $settings[$oauth] = $module_settings[$setting_key] ?? '';
            }

            $this->NFe = new \NFe($settings);
        }

        return $this->NFe;
    }

    public function updateNFe(): void {
        if (!$this->user->hasPermission('modify', 'extension/webmaniabrnfe/module/webmaniabrnfe')) { return; }


        if (isset($this->request->get['atualizar']) && isset($this->request->get['chave_acesso'])) {
            $chave_acesso = is_string($this->request->get['chave_acesso']) ? $this->db->escape($this->request->get['chave_acesso']) : '';
            $order_id = (int)($this->request->get['order_id'] ?? 0);

            if (!$order_id || empty($chave_acesso)) {
                $this->session->data['error_warning'] = '<p>Erro: Parâmetros inválidos.</p>';
                return;
            }

            try {
                $response = $this->getNFe()->consultaNotaFiscal($chave_acesso);

                if (isset($response->error)) {
                    $this->session->data['error_warning'] = '<p>Erro: ' . htmlspecialchars((string)$response->error, ENT_QUOTES, 'UTF-8') . '</p>';
                    return;
                }

                $new_status = $this->db->escape((string)$response->status);
                $order_table = DB_PREFIX ? "order" : "`order`";
                $query_nfe_data = $this->db->query("SELECT nfe_info FROM " . DB_PREFIX . "$order_table WHERE order_id = " . (int)$order_id);
                
                $nfe_data = [];
                if (isset($query_nfe_data->row['nfe_info']) && !empty($query_nfe_data->row['nfe_info'])) {
                    $nfe_data = \WebmaniaNFeSecurity::invoices(isset($query_nfe_data->row['nfe_info']) ? $query_nfe_data->row['nfe_info'] : '');
                    if (!$nfe_data) {
                        $nfe_data = [];
                    }
                }

                foreach ($nfe_data as &$order_nfe) {
                    if (($order_nfe['chave_acesso'] ?? '') === $chave_acesso) {
                        $order_nfe['status'] = $new_status;
                    }
                }

                $nfe_data_str = $this->db->escape(base64_encode(\WebmaniaNFeSecurity::legacyEncode(serialize($nfe_data))));

                $this->db->query("UPDATE " . DB_PREFIX . "$order_table SET nfe_info = '" . $nfe_data_str . "' WHERE order_id = " . (int)$order_id);
                $this->session->data['success'] = '<p><i class="fa fa-check-circle"></i> NF-e atualizada com sucesso</p>';

                $this->response->redirect($this->url->link('sale/order/info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $order_id, true));
            } catch (\Exception $e) {
                $this->session->data['error_warning'] = '<p>Erro: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
            }
        }
    }

    //Get order and customer info from Model and emit
    public function emitirNfe(): void {
        if (!$this->user->hasPermission('modify', 'extension/webmaniabrnfe/module/webmaniabrnfe')) { return; }


        if (isset($this->request->post['selected']) && is_array($this->request->post['selected'])) {

            $this->load->model('sale/order');
            $this->load->model('extension/webmaniabrnfe/module/webmaniabrnfe');
            $success = '';
            $error = '';
            foreach ($this->request->post['selected'] as $order_id) {
        if (!is_scalar($order_id) || !ctype_digit((string) $order_id)) { continue; }
        $order_id = (int) $order_id;
                $order_info = $this->model_sale_order->getOrder($order_id);
                if (!$order_info) {
                    // Unknown/deleted order: report it instead of sending an empty NF-e request.
                    $error .= (empty($error) ? '' : '<br><i class="fa fa-close"></i>') . ' NF-e do pedido #' . $order_id . ' não emitida';
                    continue;
                }
                $products_info = $this->model_sale_order->getProducts($order_id);
                $data = $this->model_extension_webmaniabrnfe_module_webmaniabrnfe->getNfeInfo($order_info, $products_info);
                $response = $this->getNFe()->emissaoNotaFiscal($data);
                if (isset($response->error) || $response->status == 'reprovado') {
                    if (isset($response->error)) {
                        if (empty($error)) {
                            $error .= ' NF-e do pedido #' . $order_id . ' não emitida ( ' . $response->error . ' )';
                        } else {
                            $error .= '<br><i class="fa fa-close"></i> NF-e do pedido #' . $order_id . ' não emitida ( ' . $response->error . ' )';
                        }
                    } elseif (isset($response->log->aProt[0]->xMotivo) && is_scalar($response->log->aProt[0]->xMotivo)) {
                        // SEFAZ rejection text is rendered as HTML in the order list; encode it like ->error.
                        $motivo = htmlspecialchars((string) $response->log->aProt[0]->xMotivo, ENT_QUOTES, 'UTF-8');
                        if (empty($error)) {
                            $error .= ' NF-e do pedido #' . $order_id . ' não emitida ( ' . $motivo . ' )';
                        } else {
                            $error .= '<br><i class="fa fa-close"></i> NF-e do pedido #' . $order_id . ' não emitida ( ' . $motivo . ' )';
                        }
                    } else {
                        if (empty($error)) {
                            $error .= ' NF-e do pedido #' . $order_id . ' não emitida';
                        } else {
                            $error .= '<br><i class="fa fa-close"></i> NF-e do pedido #' . $order_id . ' não emitida';
                        }
                    }
                } else {

                    $order_table = (DB_PREFIX) ? "order" : "`order`";
                    $previous_info_query = $this->db->query("SELECT nfe_info FROM " . DB_PREFIX . "$order_table WHERE order_id = " . $order_id);

                    $previous_info = \WebmaniaNFeSecurity::invoices(isset($previous_info_query->rows[0]['nfe_info']) ? $previous_info_query->rows[0]['nfe_info'] : '');
                    if (!$previous_info) {
                        $previous_info = [];
                    }

                    $order_nfe_info = [
                        'uuid' => (string)$response->uuid,
                        'status' => (string)$response->status,
                        'chave_acesso' => $response->chave,
                        'n_recibo' => (int)$response->recibo,
                        'n_nfe' => (int)$response->nfe,
                        'n_serie' => (int)$response->serie,
                        'url_xml' => (string)$response->xml,
                        'url_danfe' => (string)$response->danfe,
                        'url_danfe_simples' => (string)$response->danfe_simples,
                        'url_danfe_etiqueta' => (string)$response->danfe_etiqueta,
                        'documento' => (string)($data['cliente']['cpf'] ?? $data['cliente']['cnpj'] ?? ''),
                        'data' => date('d/m/Y'),
                    ];

                    $previous_info[] = $order_nfe_info;

                    $nfe_info_str = base64_encode(\WebmaniaNFeSecurity::legacyEncode(serialize($previous_info)));

                    $this->db->query("UPDATE " . DB_PREFIX . "$order_table SET nfe_info = '$nfe_info_str' WHERE order_id = " . $order_id);
                    $this->db->query("UPDATE " . DB_PREFIX . "$order_table SET status_nfe = '1' WHERE order_id = " . $order_id);

                    if (empty($success)) {
                        $success .= ' NF-e do pedido #' . $order_id . ' emitida com sucesso';
                    } else {
                        $success .= '<br><i class="fa fa-check-circle"></i> NF-e do pedido #' . $order_id . ' emitida com sucesso';
                    }
                }
            }

            if (strlen($error) > 0) {
                $this->session->data['error_warning'] = $error;
            }
            if (strlen($success) > 0) {
                $this->session->data['success'] = $success;
            }
        }

        $this->response->redirect($this->url->link('sale/order', 'user_token=' . $this->session->data['user_token'], true));
    }

    //Get certificate expiration once a day
    public function displayMessageCertificado(): void {

        if (!isset($this->session->data['certificado_last_check'])) {
            $validade = $this->getNFe()->validadeCertificado();
            $this->session->data['certificado_last_check'] = time();
            if (!is_object($validade) && $validade > 1 && $validade < 45) {
                $this->session->data['validade_certificado'] = 'Faltam ' . $validade . ' dias para expirar seu certificado digital.';
            } else {
                if (isset($this->session->data['validade_certificado'])) {
                    unset($this->session->data['validade_certificado']);
                }
            }
        } else {
            $current = time();
            $last_check = $this->session->data['certificado_last_check'];
            if (($current - $last_check) > 86400) {
                unset($this->session->data['certificado_last_check']);
            }
        }
    }

    public function get_value_from_fields(array $data): mixed {
        return $this->NFeFunctions->get_value_from_fields($data["key"], $data["custom_fields_ids"], $data["custom_fields_customer"]);
    }

    public function is_cpf(?string $cpf = null): bool {
        return $this->NFeFunctions->is_cpf($cpf);
    }

    public function is_cnpj(?string $cnpj = null): bool {
        return $this->NFeFunctions->is_cnpj($cnpj);
    }

    public function cpf(?string $string = null): ?string {
        return $this->NFeFunctions->cpf($string);
    }

    public function cnpj(?string $string = null): ?string {
        return $this->NFeFunctions->cnpj($string);
    }

    public function cep(?string $string = null): ?string {
        return $this->NFeFunctions->cep($string);
    }

    public function getCustomFields(): array {
        return $this->NFeFunctions->getCustomFields($this);
    }

    public function getCustomFieldsIds(): array {
        return $this->NFeFunctions->getCustomFieldsIds($this, 'backend');
    }

    //Check if module is installed
    public function isInstalled(): bool {
        return $this->NFeFunctions->isInstalled($this, true);
    }

    public function saveTransport(): void {
        $request = $this->request->post;
        $request['nfe-save-transporte-info'] = 1;
        $this->save_order_transporte_info($request);
        $separator = version_compare(VERSION, '4.0.2.0', '>=') ? '.' : '|';
        $this->response->redirect($this->url->link('sale/order' . $separator . 'info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . (int) ($request['order_id'] ?? 0), true));
    }

    protected function save_order_transporte_info(array $request): void {
        if (!$this->user->hasPermission('modify', 'extension/webmaniabrnfe/module/webmaniabrnfe')) { return; }
        if (!is_array($request) || empty($request['nfe-save-transporte-info']) || empty($request['order_id']) ||
            !is_scalar($request['order_id']) || !ctype_digit((string) $request['order_id'])) { return; }
        $fields = array('modalidade_frete', 'volume', 'especie', 'peso_bruto', 'peso_liquido');
        $values = array();
        foreach ($fields as $field) {
            $key = 'webmaniabrnfe_' . $field;
            $value = isset($request[$key]) && is_scalar($request[$key]) ? (string) $request[$key] : '';
            $values[] = 'nfe_transporte_' . $field . " = '" . $this->db->escape($value) . "'";
        }
        $this->db->query('UPDATE `' . DB_PREFIX . 'order` SET ' . implode(', ', $values) . ' WHERE order_id = ' . (int) $request['order_id']);

}

    public function get_order_transporte_info(int $order_id): array {

        if (!$order_id) return [];

        $data = [];

        try {
            $order_table = (DB_PREFIX) ? "order" : "`order`";
            $query_transporte_info = $this->db->query("SELECT nfe_transporte_modalidade_frete, nfe_transporte_volume, nfe_transporte_especie, nfe_transporte_peso_bruto, nfe_transporte_peso_liquido FROM " . DB_PREFIX . "$order_table WHERE order_id = " . $order_id);

            $transporte_info = $query_transporte_info->row;

            $data['nfe_volume'] = $transporte_info['nfe_transporte_volume'] ?? '';
            $data['nfe_especie'] = $transporte_info['nfe_transporte_especie'] ?? '';
            $data['nfe_peso_bruto'] = $transporte_info['nfe_transporte_peso_bruto'] ?? '';
            $data['nfe_peso_liquido'] = $transporte_info['nfe_transporte_peso_liquido'] ?? '';
            $data['modalidade_frete'] = $transporte_info['nfe_transporte_modalidade_frete'] ?? '';

            return $data;

        } catch (\Exception $e) {

            $data['nfe_volume'] = '';
            $data['nfe_especie'] = '';
            $data['nfe_peso_bruto'] = '';
            $data['nfe_peso_liquido'] = '';
            $data['nfe_modalidade_frete'] = '';

            return $data;
        }
    }

    public function imprimirDanfe(): void {
        if (!$this->user->hasPermission('modify', 'extension/webmaniabrnfe/module/webmaniabrnfe')) { return; }


        $url_redirect = $this->url->link('sale/order', 'user_token=' . $this->session->data['user_token'], true);

        if (isset($this->request->post['selected']) && is_array($this->request->post['selected'])) {

            $this->load->model('sale/order');
            $this->load->model('extension/webmaniabrnfe/module/webmaniabrnfe');

            $links = $this->getLinksToPrint($this->request->post['selected'], 'normal');

            if (!empty($links)) {
                $result = $this->createPdf($links);
            }
        }

        if (!isset($result['result']) || (empty($result['result']) || !file_exists($result['path']))) {
            $this->response->redirect($url_redirect);
            die();
        }

        $this->showDanfe($result['path'], $result['file']);
    }

    public function imprimirDanfeEtiqueta(): void {
        if (!$this->user->hasPermission('modify', 'extension/webmaniabrnfe/module/webmaniabrnfe')) { return; }


        $url_redirect = $this->url->link('sale/order', 'user_token=' . $this->session->data['user_token'], true);

        if (isset($this->request->post['selected']) && is_array($this->request->post['selected'])) {

            $this->load->model('sale/order');
            $this->load->model('extension/webmaniabrnfe/module/webmaniabrnfe');

            $links = $this->getLinksToPrint($this->request->post['selected'], 'etiqueta');

            if (!empty($links)) {
                $result = $this->createPdf($links);
            }
        }

        if (!isset($result['result']) || (empty($result['result']) || !file_exists($result['path']))) {
            $this->response->redirect($url_redirect);
            die();
        }

        $this->showDanfe($result['path'], $result['file']);
    }

    public function imprimirDanfeSimples(): void {
        if (!$this->user->hasPermission('modify', 'extension/webmaniabrnfe/module/webmaniabrnfe')) { return; }


        $url_redirect = $this->url->link('sale/order', 'user_token=' . $this->session->data['user_token'], true);

        if (isset($this->request->post['selected']) && is_array($this->request->post['selected'])) {

            $this->load->model('sale/order');
            $this->load->model('extension/webmaniabrnfe/module/webmaniabrnfe');

            $links = $this->getLinksToPrint($this->request->post['selected'], 'simples');

            if (!empty($links)) {
                $result = $this->createPdf($links);
            }
        }

        if (!isset($result['result']) || (empty($result['result']) || !file_exists($result['path']))) {
            $this->response->redirect($url_redirect);
            die();
        }

        $this->showDanfe($result['path'], $result['file']);
    }

    public function getLinksToPrint(array $ids, string $type = 'normal'): array {

        $order_table = (DB_PREFIX) ? "order" : "`order`";
        $links = [];

        foreach ($ids as $order_id) {
        $order_id = (int) $order_id;

            $query = $this->db->query("SELECT nfe_info FROM " . DB_PREFIX . "$order_table WHERE order_id = " . (int)$order_id);
            if (isset($query->rows[0]['nfe_info'])) {
                $nfe_info = \WebmaniaNFeSecurity::invoices(isset($query->rows[0]['nfe_info']) ? $query->rows[0]['nfe_info'] : '');
                if (!$nfe_info) {
                    $nfe_info = [];
                }

                $data = end($nfe_info);

                if (!$data) {
                    continue;
                }

                $url = '';
                if ($type == 'normal') {
                    $url = $data['url_danfe'];
                } elseif ($type == 'simples') {
                    $url = ($data['url_danfe_simples']) ? $data['url_danfe_simples'] : str_replace('/danfe/', '/danfe/simples/', $data['url_danfe']);
                } elseif ($type == 'etiqueta') {
                    $url = ($data['url_danfe_etiqueta']) ? $data['url_danfe_etiqueta'] : str_replace('/danfe/', '/danfe/etiqueta/', $data['url_danfe']);
                }

                $links[] = ['chave' => $data['chave_acesso'], 'url' => $url];
            }
        }

        return $links;
    }

    protected function createPdf(array $links): array {
        $directory = \WebmaniaNFeSecurity::privateDirectory();
        $pdf = new \PDFMerger();
        $sources = array();
        foreach ($links as $link) {
            if (!is_array($link) || !isset($link['url'])) { continue; }
            $content = $this->getNFe()->curl_get_file_contents($link['url']);
            if ($content === false) { continue; }
            $source = $directory . \WebmaniaNFeSecurity::randomToken() . '.pdf';
            if (file_put_contents($source, $content) !== false) { $sources[] = $source; $pdf->addPDF($source, 'all'); }
        }
        $filename = \WebmaniaNFeSecurity::randomToken() . '.pdf';
        $path = $directory . $filename;
        $result = false;
        try { if ($sources) { $result = $pdf->merge('file', $path); } }
        catch (\Exception $e) { $result = false; }
        catch (\Throwable $e) { $result = false; }
        foreach ($sources as $source) { if (is_file($source)) { unlink($source); } }
        if (!$result) { if (is_file($path)) { unlink($path); } rmdir($directory); }
        return array('result' => $result, 'file' => $filename, 'path' => $path);

}

    protected function showDanfe(string $path, string $file): void {

        $this->response->addHeader('Content-Type: application/pdf');
        $this->response->addHeader('Content-Disposition: inline; filename=' . $file);
        $this->response->addHeader('Content-Length: ' . filesize($path));
        $this->response->addHeader('Pragma: no-cache');
        $this->response->addHeader('Expires: 0');

        $content = file_get_contents($path);
        $this->response->setOutput($content);
        if (file_exists($path)) {
            unlink($path);
            rmdir(dirname($path));
        }
    }

    public function createSecureTokenDFe(array $data): string {

        $password = preg_replace("/[^0-9]/", '', $data['password']);
        $key = hash('sha256', $password . ':' . $data['uuid'], true);
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('AES-256-CBC'));
        $encryptedData = openssl_encrypt(time(), 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        $tokenData = json_encode(['data' => base64_encode($encryptedData), 'iv' => base64_encode($iv)]);
        return urlencode(base64_encode($tokenData));
    }
}
