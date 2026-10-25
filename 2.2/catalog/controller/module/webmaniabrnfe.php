<?php

class ControllerModuleWebmaniaBRNFe extends Controller {
    protected $NFeFunctions;

  function __construct( $registry ){

    $this->registry = $registry;
    require_once (__DIR__.'/../../../admin/controller/nfe/functions.php');
    $this->NFeFunctions = new NFeFunctions;

  }

  function is_cpf( $cpf = null ){

    return $this->NFeFunctions->is_cpf( $cpf );

  }

  function is_cnpj( $cnpj = null ){

    return $this->NFeFunctions->is_cnpj( $cnpj );

  }

  function getCustomFieldsIds(){

    return $this->NFeFunctions->getCustomFieldsIds( $this, 'frontend' );

  }

  function isInstalled(){

    return $this->NFeFunctions->isInstalled( $this );

  }

  function listen_notification(){
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
