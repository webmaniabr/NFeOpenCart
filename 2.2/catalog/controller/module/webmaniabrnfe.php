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

}
?>
