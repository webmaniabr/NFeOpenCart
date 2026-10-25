<?php
namespace Opencart\Admin\Controller\Extension\Webmaniabrnfe\Event;

require_once __DIR__ . '/../nfe/Security.php';
require_once __DIR__ . '/../nfe/functions.php';

/** Native OpenCart 4 events replace the legacy vQmod modifications. */
class Nfe extends \Opencart\System\Engine\Controller {
    private function active(): bool {
        return (bool) $this->config->get('module_webmaniabrnfe_status');
    }

    private function escape($value): string {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    private function link(string $method): string {
        return $this->url->link(\NFeFunctions::controllerRoute($method), 'user_token=' . (string) ($this->session->data['user_token'] ?? ''), true);
    }

    public function orderInfo(&$route, &$data, &$output): void {
        if (!$this->active() || !is_string($output)) { return; }
        $id = (int) ($data['order_id'] ?? $this->request->get['order_id'] ?? 0);
        if ($id < 1) { return; }
        $query = $this->db->query('SELECT nfe_info, custom_field, nfe_transporte_modalidade_frete, nfe_transporte_volume, nfe_transporte_especie, nfe_transporte_peso_bruto, nfe_transporte_peso_liquido FROM `' . DB_PREFIX . 'order` WHERE order_id = ' . $id);
        if (!$query->num_rows) { return; }
        $functions = new \NFeFunctions();
        $document = $functions->orderDocument($query->row['custom_field'] ?? '', $functions->getCustomFieldsIds($this, 'frontend'));
        $html = '<div class="container-fluid"><div class="card mb-3"><div class="card-header">Webmania NF-e</div><div class="card-body">';
        foreach (\WebmaniaNFeSecurity::invoices($query->row['nfe_info'] ?? '') as $invoice) {
            $html .= '<p>NF-e ' . $this->escape($invoice['n_nfe']) . ': ' . $this->escape($invoice['status']);
            foreach (array('url_danfe' => 'DANFE', 'url_xml' => 'XML') as $key => $label) {
                $url = $functions->documentLink($invoice, $key, $document);
                if ($url !== '') { $html .= ' <a target="_blank" rel="noopener noreferrer" href="' . $this->escape($url) . '">' . $label . '</a>'; }
            }
            if ($this->user->hasPermission('modify', 'extension/webmaniabrnfe/module/webmaniabrnfe') && $invoice['chave_acesso'] !== '') {
                $url = $this->link('updateNFe') . '&' . http_build_query(['atualizar' => 1, 'order_id' => $id, 'chave_acesso' => $invoice['chave_acesso']]);
                $html .= ' <a href="' . $this->escape($url) . '">Atualizar status</a>';
            }
            $html .= '</p>';
        }
        if ($this->user->hasPermission('modify', 'extension/webmaniabrnfe/module/webmaniabrnfe')) {
            foreach (array('emitirNfe' => 'Emitir NF-e', 'imprimirDanfe' => 'Imprimir DANFE', 'imprimirDanfeSimples' => 'DANFE simples', 'imprimirDanfeEtiqueta' => 'DANFE etiqueta') as $method => $label) {
                $html .= '<form method="post" action="' . $this->escape($this->link($method)) . '" class="d-inline"><input type="hidden" name="selected[]" value="' . $id . '"><button class="btn btn-outline-primary mb-2" type="submit">' . $label . '</button></form> ';
            }
            $html .= '<form method="post" action="' . $this->escape($this->link('saveTransport')) . '"><input type="hidden" name="order_id" value="' . $id . '"><div class="row">';
            foreach (array('modalidade_frete' => 'Modalidade do frete', 'volume' => 'Volumes', 'especie' => 'Espécie', 'peso_bruto' => 'Peso bruto', 'peso_liquido' => 'Peso líquido') as $key => $label) {
                $html .= '<div class="col"><label>' . $label . '<input class="form-control" name="webmaniabrnfe_' . $key . '" value="' . $this->escape($query->row['nfe_transporte_' . $key] ?? '') . '"></label></div>';
            }
            $html .= '</div><button class="btn btn-secondary mt-2" type="submit">Salvar transporte</button></form>';
        }
        $html .= '</div></div></div>';
        $output = preg_replace_callback('/<div\s+id="content"[^>]*>/i', static function ($match) use ($html) { return $match[0] . $html; }, $output, 1);
    }

    public function orderList(&$route, &$data, &$output): void {
        if (!$this->active() || !is_string($output)) { return; }
        $ids = [];
        foreach (($data['orders'] ?? []) as $order) { if (!empty($order['order_id'])) { $ids[] = (int) $order['order_id']; } }
        $states = [];
        if ($ids) {
            $query = $this->db->query('SELECT order_id, status_nfe FROM `' . DB_PREFIX . 'order` WHERE order_id IN (' . implode(',', $ids) . ')');
            foreach ($query->rows as $row) { $states[(int) $row['order_id']] = !empty($row['status_nfe']); }
        }
        $output = preg_replace_callback('/(<thead\b[^>]*>.*?)(<\/tr>)/is', static function ($match) {
            return $match[1] . '<th class="text-start">NF-e</th>' . $match[2];
        }, $output, 1);
        $output = preg_replace_callback('/(<tr\b[^>]*>)(.*?)(<\/tr>)/is', static function ($match) use ($states) {
            if (!preg_match('/<input\b[^>]*name="selected\[\]"[^>]*value="([0-9]+)"/i', $match[2], $id)) { return $match[0]; }
            $state = !empty($states[(int) $id[1]]) ? 'Emitida' : 'Não emitida';
            return $match[1] . $match[2] . '<td class="text-start wmbr-nfe-status">' . $state . '</td>' . $match[3];
        }, $output);
        if (!$ids) { $output = preg_replace_callback('/colspan="([0-9]+)"/', static function ($match) { return 'colspan="' . ((int) $match[1] + 1) . '"'; }, $output); }
        if (!$this->user->hasPermission('modify', 'extension/webmaniabrnfe/module/webmaniabrnfe')) { return; }
        $html = '<div class="mb-3" id="wmbr-nfe-bulk">';
        foreach (['success' => 'success', 'error_warning' => 'danger'] as $key => $class) {
            if (!empty($this->session->data[$key]) && is_string($this->session->data[$key])) {
                $message = str_replace(['<br>', '<br/>', '<br />'], "\n", $this->session->data[$key]);
                $html .= '<div class="alert alert-' . $class . '">' . nl2br($this->escape(strip_tags($message))) . '</div>';
                unset($this->session->data[$key]);
            }
        }
        foreach (array('emitirNfe' => 'Emitir NF-e selecionadas', 'imprimirDanfe' => 'DANFE', 'imprimirDanfeSimples' => 'DANFE simples', 'imprimirDanfeEtiqueta' => 'DANFE etiqueta') as $method => $label) {
            $html .= '<button type="button" class="btn btn-outline-primary me-1" data-wmbr-action="' . $this->escape($this->link($method)) . '">' . $label . '</button>';
        }
        $html .= '</div><script>document.querySelectorAll("#wmbr-nfe-bulk [data-wmbr-action]").forEach(function(button){button.addEventListener("click",function(){var selected=document.querySelectorAll("#form-order input[name=\"selected[]\"]:checked");if(!selected.length){return;}var form=document.createElement("form");form.method="post";form.action=button.dataset.wmbrAction;selected.forEach(function(item){var input=document.createElement("input");input.type="hidden";input.name="selected[]";input.value=item.value;form.appendChild(input);});document.body.appendChild(form);form.submit();});});</script>';
        $output = $html . $output;
    }

    private function fields(string $entity): array {
        return $entity === 'product' ? array('classe_imposto' => 'Classe de imposto', 'ean_barcode' => 'Código EAN', 'gtin_tributavel' => 'GTIN tributável', 'ncm_code' => 'NCM', 'cest_code' => 'CEST', 'product_source' => 'Origem do produto', 'cnpj_fabricante' => 'CNPJ do fabricante', 'ind_escala' => 'Indicador de escala', 'ignorar_nfe' => 'Ignorar na NF-e (0 ou 1)') : array('category_ncm' => 'NCM da categoria');
    }

    private function fieldLength(string $key): int {
        return $key === 'cnpj_fabricante' ? 20 : (in_array($key, array('ind_escala', 'ignorar_nfe'), true) ? 5 : 15);
    }

    private function entityForm(string $entity, array $data, &$output): void {
        if (!$this->active() || !is_string($output)) { return; }
        $id = (int) ($data[$entity . '_id'] ?? $this->request->get[$entity . '_id'] ?? 0);
        $values = array();
        if ($id) { $values = $this->db->query('SELECT * FROM `' . DB_PREFIX . $entity . '` WHERE `' . $entity . '_id` = ' . $id)->row; }
        $html = '<div class="card mb-3"><div class="card-header">Webmania NF-e</div><div class="card-body row">';
        foreach ($this->fields($entity) as $key => $label) {
            $html .= '<div class="col-sm-4 mb-2"><label>' . $label . '<input class="form-control" maxlength="' . $this->fieldLength($key) . '" name="' . $key . '" value="' . $this->escape($values[$key] ?? '') . '"></label></div>';
        }
        $html .= '</div></div>';
        $pattern = '/<form\b[^>]*\bid="form-' . $entity . '"[^>]*>/i';
        $output = preg_replace_callback($pattern, static function ($match) use ($html) { return $match[0] . $html; }, $output, 1);
    }

    public function productForm(&$route, &$data, &$output): void { $this->entityForm('product', $data, $output); }
    public function categoryForm(&$route, &$data, &$output): void { $this->entityForm('category', $data, $output); }

    private function entitySaved(string $entity, string $route, array $args, $output): void {
        if (!$this->active() || !$this->user->hasPermission('modify', 'catalog/' . $entity)) { return; }
        $editing = strpos($route, '/edit') !== false || strpos($route, '.edit') !== false || strpos($route, '|edit') !== false;
        $id = (int) ($editing ? ($args[0] ?? 0) : $output);
        $data = $editing ? ($args[1] ?? array()) : ($args[0] ?? array());
        if (!$id || !is_array($data)) { return; }
        $values = array();
        foreach ($this->fields($entity) as $key => $label) {
            if (isset($data[$key]) && is_scalar($data[$key])) { $values[] = '`' . $key . "` = '" . $this->db->escape(mb_substr((string) $data[$key], 0, $this->fieldLength($key), 'UTF-8')) . "'"; }
        }
        if ($values) { $this->db->query('UPDATE `' . DB_PREFIX . $entity . '` SET ' . implode(', ', $values) . ' WHERE `' . $entity . '_id` = ' . $id); }
    }

    public function productSaved(&$route, &$args, &$output): void { $this->entitySaved('product', $route, $args, $output); }
    public function categorySaved(&$route, &$args, &$output): void { $this->entitySaved('category', $route, $args, $output); }
}
