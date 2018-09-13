jQuery(document).ready(function(){
  
  $('.btn--confirm-carrier').click(function(){
    
    if( ! validateCarrierForm() ){
      alert('Preencha todas as informações para adicionar a transportadora');
      return;
    }
    
    var inputValue = $('input[name="webmaniabrnfe_carriers"]').val();
    
    if(!inputValue){
      inputValue = [];
    }else{
      inputValue = JSON.parse(inputValue);
    }
    
    
    var id = $('input[name="current-edit"').val();
    
    var data = {
      method       : $('select[name="webmaniabrnfe_transp_method"]').val(),
      razao_social : $('input[name="webmaniabrnfe_transp_rs"]').val(),
      cnpj         : $('input[name="webmaniabrnfe_transp_cnpj"]').val(),
      ie           : $('input[name="webmaniabrnfe_transp_ie"]').val(),
      address      : $('input[name="webmaniabrnfe_transp_address"]').val(),
      cep          : $('input[name="webmaniabrnfe_transp_cep"]').val(),
      city         : $('input[name="webmaniabrnfe_transp_city"]').val(),
      uf           : $('input[name="webmaniabrnfe_transp_uf"]').val(),
    };
    
    if(id){
      
      for(var i = 0; i < inputValue.length; i++){
        if(inputValue[i].id == id){
          
          inputValue[i].method       = data.method;
          inputValue[i].razao_social = data.razao_social;
          inputValue[i].cnpj         = data.cnpj;
          inputValue[i].ie           = data.ie;
          inputValue[i].address      = data.address;
          inputValue[i].cep          = data.cep;
          inputValue[i].city         = data.city;
          inputValue[i].uf           = data.uf;
          
          $('.carrier-item[data-id="'+id+'"]').find('p').html(data.razao_social+' (<br/><span>Editar</span>)');
          
        }
      }
      
    }else{
      
      data.id = ID();
      addCarrierToList(data);
      inputValue.push(data);
      
    }
    
    $('input[name="webmaniabrnfe_carriers"]').val(JSON.stringify(inputValue));
    $('#add-carrier-modal').modal('hide');
  
  });
  
  $('.carriers-list').on('click', '.carrier-item', function(e){
    
    if($(e.target).hasClass('delete')) return;
    
    var id = $(this).attr('data-id');
    var data = getDataByID(id);
    
    $('select[name="webmaniabrnfe_transp_method"]').val(data.method).change();
    $('input[name="webmaniabrnfe_transp_rs"]').val(data.razao_social);
    $('input[name="webmaniabrnfe_transp_cnpj"]').val(data.cnpj);
    $('input[name="webmaniabrnfe_transp_ie"]').val(data.ie);
    $('input[name="webmaniabrnfe_transp_address"]').val(data.address);
    $('input[name="webmaniabrnfe_transp_cep"]').val(data.cep);
    $('input[name="webmaniabrnfe_transp_city"]').val(data.city);
    $('input[name="webmaniabrnfe_transp_uf"]').val(data.uf);
    
    $('.modal-title').html('Editar Transportadora');
    $('.btn--confirm-carrier').html('Salvar');
    $('input[name="current-edit').val(id);
    
    $('#add-carrier-modal').modal('show');

  });
  
  $('#add-carrier-modal').on('hidden.bs.modal', function(){
    resetModal();
  });
  
  
  $('.carriers-list').on('click', '.delete', function(){
    
    if(!confirm('Tem certeza que deseja remover esta transportadora?')) return;
    
    var id = $(this).parents('.carrier-item').attr('data-id');
    var inputValue = $.parseJSON($('input[name="webmaniabrnfe_carriers"]').val());
    
    for(var i = 0; i < inputValue.length; i++){
      if(inputValue[i].id == id){
        inputValue.splice(i, 1);
      }
    }
    
    if(inputValue.length == 0){
      $('input[name="webmaniabrnfe_carriers"]').val('');
    }else{
      $('input[name="webmaniabrnfe_carriers"]').val(JSON.stringify(inputValue));
    }
    
    $(this).parents('.carrier-item').remove();
    
  });
  
  function resetModal(){
    
    $('select[name="webmaniabrnfe_transp_method"]').val('').change();
    $('input[name="webmaniabrnfe_transp_rs"]').val('');
    $('input[name="webmaniabrnfe_transp_cnpj"]').val('');
    $('input[name="webmaniabrnfe_transp_ie"]').val('');
    $('input[name="webmaniabrnfe_transp_address"]').val('');
    $('input[name="webmaniabrnfe_transp_cep"]').val('');
    $('input[name="webmaniabrnfe_transp_city"]').val('');
    $('input[name="webmaniabrnfe_transp_uf"]').val('');
    $('input[name="current-edit').val('');
    
    $('.modal-title').html('Nova Transportadora');
    $('.btn--confirm-carrier').html('Adicionar');
    
  }
  
  function validateCarrierForm(){
    
    var data = {
      id           : ID(),
      method       : $('select[name="webmaniabrnfe_transp_method"]').val(),
      razao_social : $('input[name="webmaniabrnfe_transp_rs"]').val(),
      cnpj         : $('input[name="webmaniabrnfe_transp_cnpj"]').val(),
      ie           : $('input[name="webmaniabrnfe_transp_ie"]').val(),
      address      : $('input[name="webmaniabrnfe_transp_address"]').val(),
      cep          : $('input[name="webmaniabrnfe_transp_cep"]').val(),
      city         : $('input[name="webmaniabrnfe_transp_city"]').val(),
      uf           : $('input[name="webmaniabrnfe_transp_uf"]').val(),
    };
    
    for(var key in data){
      if(!data[key]){
        return false;
      }
    }
    
    return true;
    
  }
  
  function addCarrierToList(data){
    
    var $carrier = $('<div class="carrier-item" data-id="'+data.id+'"></div>');
    var carrier_name = $('option[value="'+data.method+'"]').html();
    
    $carrier.append('<p>'+data.razao_social+' <br/>(<span>Editar</span>)</p>');
    $carrier.append('<span class="delete">x</span>');
    
    $carrier.appendTo('.carriers-list');
    
  }
  
  function getDataByID(id){
    
    var arr = $.parseJSON($('input[name="webmaniabrnfe_carriers"]').val());
    
    for(var i = 0; i < arr.length ; i++){
      if(arr[i].id == id){
        return arr[i];
      }  
    }
    
  }
  
  function ID() {
    return '_' + Math.random().toString(36).substr(2, 9);
  };
  
  
  
});