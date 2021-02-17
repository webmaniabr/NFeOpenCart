<?php

class ControllerModuleWebmaniaBRNFe extends Controller {

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
    
    if($_SERVER['REQUEST_METHOD'] === 'POST' && $_GET['retorno_nfe'] && $_GET['order_id']){

      $this->load->model('setting/setting');
      $module_settings = $this->model_setting_setting->getSetting('webmaniabrnfe');

      if($_GET['retorno_nfe'] == $module_settings['webmaniabrnfe_uniq_get_key']){

        $order_id = (int)$_GET['order_id'];
        $order_table = (DB_PREFIX) ? "order" : "`order`";
        $raw_nfe_info = $this->db->query("SELECT nfe_info FROM " . DB_PREFIX . "$order_table WHERE order_id = $order_id");
        $nfe_info = unserialize($raw_nfe_info->row['nfe_info']);
        if(!$nfe_info) $nfe_info = array();

        foreach($nfe_info as $key => $nfe){

          $uuid = $nfe['uuid'];
          
          $current_status = $nfe['status'];
          $received_status = $_POST['status'];
          
          if($uuid == $_POST['uuid'] && $current_status != $received_status){
            $nfe_info[$key]['status'] = $received_status;
            $nfe_info_str = serialize($nfe_info);
            $this->db->query("UPDATE " . DB_PREFIX . "$order_table SET nfe_info = '$nfe_info_str' WHERE order_id = $order_id");
            break;
          }

        }

      }

    }

  }
}
?>
