<?php echo $header; ?>
<div id="content">
  <div class="breadcrumb">
    <?php foreach ($breadcrumbs as $breadcrumb) { ?>
    <?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
    <?php } ?>
  </div>
  <?php if ($error_warning) { ?>
  <div class="warning"><?php echo $error_warning; ?></div>
  <?php } ?>
  <?php if ($success_message) { ?>
  <div class="success"><?php echo $success_message; ?></div>
  <?php } ?>
  <div class="box">
    <div class="heading">
      <h1><?php echo $heading_title; ?></h1>
      <div class="buttons"><a onclick="$('#form').submit();" class="button"><?php echo $button_save; ?></a><a href="<?php echo $cancel; ?>" class="button"><?php echo $button_cancel; ?></a></div>
    </div>
    <div class="content">
      <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form">
        <h4 class="wmbr-module-title"><strong>Credenciais de Acesso</strong></h4>
        <div class="form-group">
          <label class="control-label bigger-font col-sm-2">Consumer Key:</label>
          <div class="col-sm-10">
            <input type="text" class="form-control padded-input" name="webmaniabrnfe_consumer_key" value="<?php echo $webmaniabrnfe_consumer_key; ?>"/>
          </div>
        </div>
        <div class="form-group">
          <label class="control-label bigger-font col-sm-2">Consumer Secret:</label>
          <div class="col-sm-10">
            <input type="text" class="form-control padded-input" name="webmaniabrnfe_consumer_secret" value="<?php echo $webmaniabrnfe_consumer_secret; ?>"/>
          </div>
        </div>
        <div class="form-group">
          <label class="control-label bigger-font col-sm-2">Access Token:</label>
          <div class="col-sm-10">
            <input type="text" class="form-control padded-input" name="webmaniabrnfe_access_token" value="<?php echo $webmaniabrnfe_access_token; ?>"/>
          </div>
        </div>
        <div class="form-group">
          <label class="control-label bigger-font col-sm-2">Access Token Secret:</label>
          <div class="col-sm-10">
            <input type="text" class="form-control padded-input" name="webmaniabrnfe_access_token_secret" value="<?php echo $webmaniabrnfe_access_token_secret; ?>"/>
          </div>
        </div>
        <div class="form-group">
          <label class="control-label bigger-font col-sm-2">Ambiente Sefaz:</label>
          <div class="col-sm-10">
            <?php if($webmaniabrnfe_sefaz_env == 1) : ?>
            <input type="radio" name="webmaniabrnfe_sefaz_env" value="1" checked/> Produção<br/>
            <input type="radio" name="webmaniabrnfe_sefaz_env" value="2"/> Desenvolvimento
          <?php elseif($webmaniabrnfe_sefaz_env == 2) : ?>
            <input type="radio" name="webmaniabrnfe_sefaz_env" value="1"/> Produção<br/>
            <input type="radio" name="webmaniabrnfe_sefaz_env" value="2" checked/> Desenvolvimento
          <?php else: ?>
            <input type="radio" name="webmaniabrnfe_sefaz_env" value="1"/> Produção<br/>
            <input type="radio" name="webmaniabrnfe_sefaz_env" value="2"/> Desenvolvimento
          <?php endif; ?>
          </div>
        </div>
        <h4 class="wmbr-module-title"><strong>Configuração Padrão</strong></h4>
        <div class="form-group">
          
          <div class="form-group">
            <label class="control-label bigger-font col-sm-2">Natureza da Operação:</label>
            <div class="col-sm-10">
              <input type="text" class="form-control padded-input" name="webmaniabrnfe_operation_nature" value="<?php echo $webmaniabrnfe_operation_nature; ?>"/>
            </div>
          </div>
          <div class="form-group">
            <label class="control-label bigger-font col-sm-2">Classe de Imposto:</label>
            <div class="col-sm-10">
              <input type="text" class="form-control padded-input" name="webmaniabrnfe_tax_class" value="<?php echo $webmaniabrnfe_tax_class; ?>"/>
            </div>
          </div>
          <div class="form-group">
            <label class="control-label bigger-font col-sm-2">Código de Barras EAN:</label>
            <div class="col-sm-10">
              <input type="text" class="form-control padded-input" name="webmaniabrnfe_ean_barcode" value="<?php echo $webmaniabrnfe_ean_barcode; ?>"/>
            </div>
          </div>
          <div class="form-group">
            <label class="control-label bigger-font col-sm-2">Código NCM:</label>
            <div class="col-sm-10">
              <input type="text" class="form-control padded-input" name="webmaniabrnfe_ncm_code" value="<?php echo $webmaniabrnfe_ncm_code; ?>"/>
            </div>
          </div>
          <div class="form-group">
            <label class="control-label bigger-font col-sm-2">Código CEST:</label>
            <div class="col-sm-10">
              <input type="text" class="form-control padded-input" name="webmaniabrnfe_cest_code" value="<?php echo $webmaniabrnfe_cest_code; ?>"/>
            </div>
          </div>
          <div class="form-group">
            <label class="control-label bigger-font col-sm-2">Origem dos Produtos:</label>
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
                  echo '<option value="'.$value.'" '.$selected.'>'.$option.'</option>';
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

<?php echo $footer; ?>
