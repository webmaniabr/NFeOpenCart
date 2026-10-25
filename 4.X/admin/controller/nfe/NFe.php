<?php
require_once __DIR__ . '/Security.php';

class NFe {

    private string $consumerKey;
    private string $consumerSecret;
    private string $accessToken;
    private string $accessTokenSecret;

    public function __construct(array $vars) {
        $this->consumerKey = isset($vars['consumer_key']) && is_scalar($vars['consumer_key']) ? (string) $vars['consumer_key'] : '';
        $this->consumerSecret = isset($vars['consumer_secret']) && is_scalar($vars['consumer_secret']) ? (string) $vars['consumer_secret'] : '';
        $this->accessToken = isset($vars['oauth_access_token']) && is_scalar($vars['oauth_access_token']) ? (string) $vars['oauth_access_token'] : '';
        $this->accessTokenSecret = isset($vars['oauth_access_token_secret']) && is_scalar($vars['oauth_access_token_secret']) ? (string) $vars['oauth_access_token_secret'] : '';
    }

    public function statusSefaz(): mixed {
        $data = [];
        $response = $this->connect_webmaniabr('GET', 'https://webmaniabr.com/api/1/nfe/sefaz/', $data);
        if (isset($response->error)) return $response;
        return isset($response->status) && $response->status == 'online';
    }

    public function validadeCertificado(): mixed {
        $data = [];
        $response = $this->connect_webmaniabr('GET', 'https://webmaniabr.com/api/1/nfe/certificado/', $data);
        if (isset($response->error)) return $response;
        return isset($response->expiration) && is_scalar($response->expiration) ? $response->expiration : '';
    }

    public function emissaoNotaFiscal(array $data): mixed {
        return $this->connect_webmaniabr('POST', 'https://webmaniabr.com/api/1/nfe/emissao/', $data);
    }

    public function consultaNotaFiscal(string $chave): mixed {
        $data = ['chave' => $chave];
        return $this->connect_webmaniabr('GET', 'https://webmaniabr.com/api/1/nfe/consulta/', $data);
    }

    public function cancelarNotaFiscal(string $chave, string $motivo): mixed {
        $data = [
            'chave' => $chave,
            'motivo' => $motivo
        ];
        return $this->connect_webmaniabr('PUT', 'https://webmaniabr.com/api/1/nfe/cancelar/', $data);
    }

    public function inutilizarNumeracao(string $sequencia, string $motivo): mixed {
        $data = [
            'sequencia' => $sequencia,
            'motivo' => $motivo
        ];
        return $this->connect_webmaniabr('PUT', 'https://webmaniabr.com/api/1/nfe/inutilizar/', $data);
    }

    private function connect_webmaniabr(string $request, string $endpoint, array $data): mixed {
        return WebmaniaNFeSecurity::jsonRequest($request, $endpoint, $data, array('consumer_key' => $this->consumerKey, 'consumer_secret' => $this->consumerSecret, 'access_token' => $this->accessToken, 'access_token_secret' => $this->accessTokenSecret));
}

    /**
     * @param string $url
     * @return string|false
     */
    public function curl_get_file_contents(string $url) {
        return WebmaniaNFeSecurity::download($url, array('consumer_key' => $this->consumerKey, 'consumer_secret' => $this->consumerSecret, 'access_token' => $this->accessToken, 'access_token_secret' => $this->accessTokenSecret));
}
}
