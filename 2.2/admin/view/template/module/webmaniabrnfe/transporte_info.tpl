<div class="panel panel-default">
  <div class="panel-heading">
    <h3 class="panel-title">Informações de Transporte (NF-e)</h3>
  </div>
  <div class="panel-body">
    <p>Informações complementares na emissão de Nota Fiscal para pedidos enviados via Transportadora</p>
    <?php $action = $_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']; ?>
    <form class="form-horizontal" action="<?php echo $action ?>" method="POST">
      <div class="form-group">
        <label class="control-label col-sm-1">Modalidade do frete</label>
        <div class="col-sm-10">
          <select class="form-control" name="webmaniabrnfe_modalidade_frete">
              <?php

              $options = array(
                0 => 'Por conta do emitente',
                1 => 'Por conta do destinatário/remetente',
                2 => 'Por conta de terceiros',
                9 => 'Sem frete'
              );

              foreach($options as $val => $label){
              ($val == $modalidade_frete ? $selected = 'selected' : $selected = '');
              echo '<option value="'.$val.'" '.$selected.'>'.$label.'</option>';
              }
              ?>
          </select>
        </div>
      </div>
      <div class="form-group">
        <h4 style="padding-left:15px">Volumes transportados</h4>
      </div>
      <div class="form-group">
        <label class="control-label col-sm-1">Volumes</label>
        <div class="col-sm-10">
          <input type="text" class="form-control" name="webmaniabrnfe_volume" value="<?php echo $nfe_volume; ?>"/>
        </div>
      </div>
      <div class="form-group">
        <label class="control-label col-sm-1">Espécie</label>
        <div class="col-sm-10">
          <input type="text" class="form-control" name="webmaniabrnfe_especie" value="<?php echo $nfe_especie; ?>"/>
        </div>
      </div>
      <div class="form-group">
        <label class="control-label col-sm-1">Peso Bruto</label>
        <div class="col-sm-10">
          <input type="text" class="form-control" name="webmaniabrnfe_peso_bruto" value="<?php echo $nfe_peso_bruto; ?>" />
        </div>
      </div>
      <div class="form-group">
        <label class="control-label col-sm-1">Peso Líquido</label>
        <div class="col-sm-10">
          <input type="text" class="form-control" name="webmaniabrnfe_peso_liquido" value="<?php echo $nfe_peso_liquido; ?>" />
        </div>
      </div>
      <input type="submit" value="Salvar" class="btn btn-primary" />
      <input type="hidden" name="nfe-save-transporte-info" value="save-info" />
    </form>
  </div>
</div>
