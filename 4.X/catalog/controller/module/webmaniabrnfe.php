<?php
namespace Opencart\Catalog\Controller\Extension\Webmaniabrnfe\Module;

class Webmaniabrnfe extends \Opencart\System\Engine\Controller {

    private $NFeFunctions;

    public function __construct($registry) {

        parent::__construct($registry);
        
        require_once __DIR__ . '/../../../admin/controller/nfe/functions.php';
        $this->NFeFunctions = new \NFeFunctions();
    }

    public function is_cpf(?string $cpf = null): bool {

        return $this->NFeFunctions->is_cpf($cpf);
    }

    public function is_cnpj(?string $cnpj = null): bool {

        return $this->NFeFunctions->is_cnpj($cnpj);
    }

    public function getCustomFieldsIds(): array {

        return $this->NFeFunctions->getCustomFieldsIds($this, 'frontend');
    }

    public function isInstalled(): bool {

        return $this->NFeFunctions->isInstalled($this);
    }

    public function catalogHeader(&$route = null, &$args = null): void {
        $this->listen_notification();
    }

    public function catalogFooter(&$route, &$data, &$output): void {
        $page = $this->request->get['route'] ?? '';
        if (!$this->config->get('module_webmaniabrnfe_status') || !is_string($output) || !is_string($page) ||
            !in_array($page, ['account/register', 'account/edit', 'account/address', 'account/address.form', 'account/address/add', 'account/address/edit', 'checkout/checkout'], true)) { return; }
        $settings = [
            'fields' => array_map('intval', array_intersect_key($this->getCustomFieldsIds(), array_flip(['tipo_pessoa', 'pessoa_fisica', 'pessoa_juridica', 'cpf', 'cnpj', 'razao_social', 'insc_est']))),
            'mask' => $this->config->get('webmaniabrnfe_mask_fields') === 'on',
            'postcode' => $this->config->get('webmaniabrnfe_fill_address') === 'on',
            'country' => 0, 'zones' => [],
        ];
        if ($settings['postcode'] || $settings['mask']) {
            $country = $this->db->query("SELECT country_id FROM `" . DB_PREFIX . "country` WHERE iso_code_2 = 'BR' AND status = 1");
            $settings['country'] = (int) ($country->row['country_id'] ?? 0);
            $zones = $this->db->query('SELECT zone_id, code FROM `' . DB_PREFIX . 'zone` WHERE country_id = ' . $settings['country'] . ' AND status = 1');
            foreach ($zones->rows as $zone) { $settings['zones'][(string) $zone['code']] = (int) $zone['zone_id']; }
        }
        // Only field IDs and feature settings reach the browser; fiscal API credentials stay server-side.
        $json = json_encode($settings, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $output .= '<script type="application/json" id="wmbr-nfe-checkout-config">' . $json . '</script>';
        if ($settings['mask']) { $output .= '<script src="extension/webmaniabrnfe/catalog/view/javascript/jquery.mask.min.js"></script>'; }
        $output .= '<script src="extension/webmaniabrnfe/catalog/view/javascript/checkout-fields.js"></script>';
    }

    public function customerOrderInfo(&$route, &$data, &$output): void {
        if (!$this->config->get('module_webmaniabrnfe_status') || !$this->customer->isLogged() || !is_string($output)) { return; }
        $id = (int) ($data['order_id'] ?? 0);
        if ($id < 1) { return; }
        $query = $this->db->query('SELECT nfe_info, custom_field FROM `' . DB_PREFIX . 'order` WHERE order_id = ' . $id . ' AND customer_id = ' . (int) $this->customer->getId());
        if (!$query->num_rows) { return; }
        $document = $this->NFeFunctions->orderDocument($query->row['custom_field'] ?? '', $this->getCustomFieldsIds());
        $html = '<div class="card mb-3"><div class="card-header">Notas fiscais</div><div class="card-body">';
        $count = 0;
        foreach (\WebmaniaNFeSecurity::invoices($query->row['nfe_info'] ?? '') as $invoice) {
            ++$count;
            $html .= '<p>NF-e ' . htmlspecialchars($invoice['n_nfe'], ENT_QUOTES, 'UTF-8') . ': ' . htmlspecialchars($invoice['status'], ENT_QUOTES, 'UTF-8');
            foreach (['url_danfe' => 'DANFE', 'url_xml' => 'XML'] as $key => $label) {
                $url = $this->NFeFunctions->documentLink($invoice, $key, $document);
                if ($url !== '') { $html .= ' <a target="_blank" rel="noopener noreferrer" href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">' . $label . '</a>'; }
            }
            $html .= '</p>';
        }
        $html .= '</div></div>';
        if ($count) { $output = preg_replace_callback('/<div\s+id="content"[^>]*>/i', static function ($match) use ($html) { return $match[0] . $html; }, $output, 1); }
    }

    /** Reject invalid fiscal data before the native registration/update controller writes it. */
    public function validateAccount(&$route, &$args) {
        if (!$this->config->get('module_webmaniabrnfe_status') || ($this->request->server['REQUEST_METHOD'] ?? '') !== 'POST') { return; }
        $ids = $this->getCustomFieldsIds();
        if (empty($ids['tipo_pessoa']) || empty($ids['cpf']) || empty($ids['cnpj'])) { return; }
        $group = $this->customer->isLogged() ? $this->customer->getGroupId() : (int) ($this->request->post['customer_group_id'] ?? $this->config->get('config_customer_group_id'));
        // Native registration treats an empty/zero group as the configured default.
        if (!$group) { $group = (int) $this->config->get('config_customer_group_id'); }
        $assigned = $this->db->query('SELECT custom_field_id FROM `' . DB_PREFIX . 'custom_field_customer_group` WHERE custom_field_id = ' . (int) $ids['tipo_pessoa'] . ' AND customer_group_id = ' . (int) $group);
        if (!$assigned->num_rows) { return; }
        $raw = $this->request->post['custom_field'] ?? [];
        $fields = is_array($raw) ? $raw : [];
        $nested = isset($fields['account']) && is_array($fields['account']);
        if ($nested) { $fields = $fields['account']; }
        $read = static function ($key) use ($fields, $ids): string {
            $value = isset($ids[$key]) ? ($fields[$ids[$key]] ?? '') : '';
            return is_scalar($value) ? trim((string) $value) : '';
        };
        $errors = [];
        $type = $read('tipo_pessoa');
        if (!empty($ids['pessoa_fisica']) && $type === (string) $ids['pessoa_fisica']) {
            $document = \WebmaniaNFeSecurity::normalizeDocument($read('cpf'));
            if (!preg_match('/\A[0-9]{11}\z/', $document) || !$this->NFeFunctions->is_cpf($document)) {
                $errors['custom_field_' . (int) $ids['cpf']] = 'Informe um CPF válido.';
            } else { $fields[$ids['cpf']] = $document; }
        } elseif (!empty($ids['pessoa_juridica']) && $type === (string) $ids['pessoa_juridica']) {
            $document = \WebmaniaNFeSecurity::normalizeDocument($read('cnpj'));
            if (!\WebmaniaNFeSecurity::validCnpj($document)) {
                $errors['custom_field_' . (int) $ids['cnpj']] = 'Informe um CNPJ válido.';
            } else { $fields[$ids['cnpj']] = $document; }
            if (!empty($ids['razao_social']) && $read('razao_social') === '') {
                $errors['custom_field_' . (int) $ids['razao_social']] = 'Informe a razão social.';
            }
        } else { $errors['custom_field_' . (int) $ids['tipo_pessoa']] = 'Selecione o tipo de pessoa.'; }
        if ($errors) {
            $this->registry->set('wmbr_nfe_validation_errors', (object) ['errors' => $errors]);
            $route = \NFeFunctions::controllerRoute('validationFailure');
            // 4.0 dispatches the returned Action; 4.1 dispatches the changed route.
            return new \Opencart\System\Engine\Action($route);
        }
        if ($nested) { $this->request->post['custom_field']['account'] = $fields; }
        else { $this->request->post['custom_field'] = $fields; }
    }

    public function validationFailure(): void {
        $state = $this->registry->has('wmbr_nfe_validation_errors') ? $this->registry->get('wmbr_nfe_validation_errors') : null;
        $errors = is_object($state) ? ($state->errors ?? null) : null;
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['error' => is_array($errors) ? $errors : ['warning' => 'Dados fiscais inválidos.']]));
    }

    public function listen_notification(): void {
        $server = $this->request->server;
        $query = $this->request->get;
        $post = $this->request->post;
        if (!isset($server['REQUEST_METHOD']) || $server['REQUEST_METHOD'] !== 'POST' ||
            !isset($query['retorno_nfe'], $query['order_id'], $post['uuid']) ||
            !is_scalar($query['order_id']) || !ctype_digit((string) $query['order_id']) || (int) $query['order_id'] < 1 ||
            !\WebmaniaNFeSecurity::validUuid($post['uuid'])) { return; }
        $this->load->model('setting/setting');
        $settings = $this->model_setting_setting->getSetting('webmaniabrnfe');
        $secret = isset($settings['webmaniabrnfe_uniq_get_key']) ? $settings['webmaniabrnfe_uniq_get_key'] : '';
        if (!\WebmaniaNFeSecurity::equals($secret, $query['retorno_nfe'])) { return; }
        require_once __DIR__ . '/../../../admin/controller/nfe/NFe.php';
        $order_id = (int) $query['order_id'];
        $table = '`' . DB_PREFIX . 'order`';
        $result = $this->db->query('SELECT nfe_info FROM ' . $table . ' WHERE order_id = ' . $order_id);
        $invoices = \WebmaniaNFeSecurity::invoices(isset($result->row['nfe_info']) ? $result->row['nfe_info'] : '');
        foreach ($invoices as $key => $invoice) {
            if (!is_array($invoice) || !isset($invoice['uuid']) || $invoice['uuid'] !== $post['uuid']) { continue; }
            $api = new \NFe(array(
                'consumer_key' => isset($settings['webmaniabrnfe_consumer_key']) ? $settings['webmaniabrnfe_consumer_key'] : '',
                'consumer_secret' => isset($settings['webmaniabrnfe_consumer_secret']) ? $settings['webmaniabrnfe_consumer_secret'] : '',
                'oauth_access_token' => isset($settings['webmaniabrnfe_access_token']) ? $settings['webmaniabrnfe_access_token'] : '',
                'oauth_access_token_secret' => isset($settings['webmaniabrnfe_access_token_secret']) ? $settings['webmaniabrnfe_access_token_secret'] : ''));
            $response = $api->consultaNotaFiscal($post['uuid']);
            if (isset($response->error) || !isset($response->uuid) || $response->uuid !== $post['uuid'] ||
                (isset($response->ID) && (!is_scalar($response->ID) || (string) $response->ID !== (string) $order_id))) { return; }
            $invoices[$key]['status'] = $response->status;
            $encoded = base64_encode(\WebmaniaNFeSecurity::legacyEncode(serialize($invoices)));
            $this->db->query('UPDATE ' . $table . " SET nfe_info = '" . $this->db->escape($encoded) . "' WHERE order_id = " . $order_id);
            return;
        }

}

}
?>
