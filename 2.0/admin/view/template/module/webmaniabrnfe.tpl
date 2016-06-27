<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-right">
        <button type="submit" form="form-webmaniabrnfe" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
        <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a></div>
      <h1><?php echo $heading_title; ?></h1>
      <ul class="breadcrumb">
        <?php foreach ($breadcrumbs as $breadcrumb) { ?>
        <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
        <?php } ?>
      </ul>
    </div>
  </div>
  <div class="container-fluid">
    <?php if ($error_warning) { ?>
    <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php } ?>
    <?php if ($success_message) { ?>
    <div class="alert alert-success"><i class="fa fa-exclamation-circle"></i> <?php echo $success_message; ?>
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php } ?>
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $text_edit; ?></h3>
      </div>
      <div class="panel-body">
        <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-webmaniabrnfe" class="form-horizontal">
          <h4><strong>Credenciais de Acesso</strong></h4>
          <div class="form-group">
            <label class="control-label col-sm-2">Consumer Key:</label>
            <div class="col-sm-10">
              <input type="text" class="form-control" name="webmaniabrnfe_consumer_key" value="<?php echo $webmaniabrnfe_consumer_key; ?>"/>
            </div>
          </div>
          <div class="form-group">
            <label class="control-label col-sm-2">Consumer Secret:</label>
            <div class="col-sm-10">
              <input type="text" class="form-control" name="webmaniabrnfe_consumer_secret" value="<?php echo $webmaniabrnfe_consumer_secret; ?>"/>
            </div>
          </div>
          <div class="form-group">
            <label class="control-label col-sm-2">Access Token:</label>
            <div class="col-sm-10">
              <input type="text" class="form-control" name="webmaniabrnfe_access_token" value="<?php echo $webmaniabrnfe_access_token; ?>"/>
            </div>
          </div>
          <div class="form-group">
            <label class="control-label col-sm-2">Access Token Secret:</label>
            <div class="col-sm-10">
              <input type="text" class="form-control" name="webmaniabrnfe_access_token_secret" value="<?php echo $webmaniabrnfe_access_token_secret; ?>"/>
            </div>
          </div>
          <div class="form-group">
            <label class="control-label col-sm-2">Ambiente Sefaz:</label>
            <div class="col-sm-10">
              <?php if($webmaniabrnfe_sefaz_env == 1): ?>
              <input type="radio" name="webmaniabrnfe_sefaz_env" value="1" checked/> Produção<br/>
              <input type="radio" name="webmaniabrnfe_sefaz_env" value="2"/> Desenvolvimento
            <?php elseif($webmaniabrnfe_sefaz_env == 2): ?>
              <input type="radio" name="webmaniabrnfe_sefaz_env" value="1"/> Produção<br/>
              <input type="radio" name="webmaniabrnfe_sefaz_env" value="2" checked/> Desenvolvimento
            <?php else: ?>
              <input type="radio" name="webmaniabrnfe_sefaz_env" value="1"/> Produção<br/>
              <input type="radio" name="webmaniabrnfe_sefaz_env" value="2"/> Desenvolvimento
            <?php endif; ?>
            </div>
          </div>
          <h4><strong>Configuração Padrão</strong></h4>
          <div class="form-group">            
            <div class="form-group">
              <label class="control-label col-sm-2">Natureza da Operação:</label>
              <div class="col-sm-10">
                <input type="text" class="form-control" name="webmaniabrnfe_operation_nature" value="<?php echo $webmaniabrnfe_operation_nature; ?>"/>
              </div>
            </div>
            <div class="form-group">
              <label class="control-label col-sm-2">Classe de Imposto:</label>
              <div class="col-sm-10">
                <input type="text" class="form-control" name="webmaniabrnfe_tax_class" value="<?php echo $webmaniabrnfe_tax_class; ?>"/>
              </div>
            </div>
            <div class="form-group">
              <label class="control-label col-sm-2">Código de Barras EAN:</label>
              <div class="col-sm-10">
                <input type="text" class="form-control" name="webmaniabrnfe_ean_barcode" value="<?php echo $webmaniabrnfe_ean_barcode; ?>"/>
              </div>
            </div>
            <div class="form-group">
              <label class="control-label col-sm-2">Código NCM:</label>
              <div class="col-sm-10">
                <input type="text" class="form-control" name="webmaniabrnfe_ncm_code" value="<?php echo $webmaniabrnfe_ncm_code; ?>"/>
              </div>
            </div>
            <div class="form-group">
              <label class="control-label col-sm-2">Código CEST:</label>
              <div class="col-sm-10">
                <input type="text" class="form-control" name="webmaniabrnfe_cest_code" value="<?php echo $webmaniabrnfe_cest_code; ?>"/>
              </div>
            </div>
            <div class="form-group">
              <label class="control-label col-sm-2">Origem dos Produtos:</label>
              <div class="col-sm-10">
                <select style="margin-top:10px" name="webmaniabrnfe_product_source">
                  <option>Selecionar Origem dos Produtos</option>
                  <?php
                  $options = array(
                  '0' => '0 - Nacional, exceto as indicadas nos códigos 3, 4, 5 e 8',
                  '1' => '1 - Estrangeira - Importação direta, exceto a indicada no código 6',
                  '2' => '2 - Estrangeira - Adquirida no mercado interno, exceto a indicada no código 7',
                  '3' => '3 - Nacional, mercadoria ou bem com Conteúdo de Importação superior a 40% e inferior ou igual a 70%',
                  '4' => '4 - Nacional, cuja produção tenha sido feita em conformidade com os processos produtivos básicos de que tratam as legislações citadas nos Ajustes',
                  '5' => '5 - Nacional, mercadoria ou bem com Conteúdo de Importação inferior ou igual a 40%',
                  '6' => '6 - Estrangeira - Importação direta, sem similar nacional, constante em lista da CAMEX e gás natural',
                  '7' => '7 - Estrangeira - Adquirida no mercado interno, sem similar nacional, constante lista CAMEX e gás natural',
                  '8' => '8 - Nacional, mercadoria ou bem com Conteúdo de Importação superior a 70%',
                  );

                  foreach($options as $value => $option){
                    $selected = '';
                    if($value == $webmaniabrnfe_product_source){
                      $selected = 'selected';
                    }
                    echo '<option value="'.$option.'" '.$selected.'>'.$option.'</option>';
                  }

                  ?>
                </select>
              </div>
            </div>

          </div>

        </form>
      </div>
	</div>
  </div>
</div>

<?php echo $footer; ?>
