<?php

class ControllerModuleWebmaniaBRNFe extends Controller {
  function validaCPF($cpf = null){
    if(is_array($cpf)) $cpf = $cpf[0];
    if(empty($cpf)) {
      $this->output = false;
      return $this->output;
    }
    // Elimina possivel mascara
    $cpf = preg_replace("/[^0-9]/","", $cpf);
    $cpf = str_pad($cpf, 11, '0', STR_PAD_LEFT);

    if(strlen($cpf) != 11){
      $this->output = false;
    }else if($cpf == '00000000000' ||
    $cpf == '11111111111' ||
    $cpf == '22222222222' ||
    $cpf == '33333333333' ||
    $cpf == '44444444444' ||
    $cpf == '55555555555' ||
    $cpf == '66666666666' ||
    $cpf == '77777777777' ||
    $cpf == '88888888888' ||
    $cpf == '99999999999'){
      $this->output = false;
      return $this->output;
    }else{
      for($t = 9; $t < 11; $t++){
        for ($d = 0, $c = 0; $c < $t; $c++){
          $d += $cpf{$c} * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf{$c} != $d){
          $this->output = false;
          return $this->output;
        }
      }
      $this->output = true;
      return $this->output;
    }
  }

  function validaCNPJ($cnpj = null) {
    if(is_array($cnpj)) $cnpj = $cnpj[0];
    $cnpj = sprintf( '%014s', preg_replace( '{\D}', '', $cnpj ) );

    if ( 14 != ( strlen( $cnpj ) ) || ( 0 == intval( substr( $cnpj, -4 ) ) ) ) {
      $this->output = false;
      return $this->output;
    }

    for ( $t = 11; $t < 13; ) {
      for ( $d = 0, $p = 2, $c = $t; $c >= 0; $c--, ( $p < 9 ) ? $p++ : $p = 2 ) {
        $d += $cnpj[ $c ] * $p;
      }

      if ( $cnpj[ ++$t ] != ( $d = ( ( 10 * $d ) % 11 ) % 10 ) ) {
        $this->output = false;
        return $this->output;
      }
    }

    $this->output = true;
    return $this->output;
  }
}
?>
