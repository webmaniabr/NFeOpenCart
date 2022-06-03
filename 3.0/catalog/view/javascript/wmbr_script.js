jQuery(document).ready(function($){
  console.log('hello');

  $('input[name="webmaniabrnfe_intermediador_cnpj"]').mask('99.999.999/9999-99');

  update_payment_desc_label = function() {
      
    var descs_active = $('.webmaniabrnfe-payment-desc').filter(function() { 
      return $(this).css('display') !== 'none'; 
    }).size();

    if (descs_active > 0) {
      $('.payment-desc-title').show();
    }
    else {
      $('.payment-desc-title').hide();
    } 

  }

  update_payment_desc_label();

  // Show payment desc field if payment method is 99
  $('.webmaniabrnfe-payment-methods-sel').change(function(element) {
    
    var payment_desc = $(event.target).parent().parent().find('.webmaniabrnfe-payment-desc');
    
    if (element.target.value == 99) {
      $(payment_desc).show();
      $('.payment-desc-title').show();
    }
    else {
      $(payment_desc).val('');
      $(payment_desc).hide();
    }

    update_payment_desc_label();
    
  });

  $(document).on('change', 'input[name="doctype"]', function(){
    var docType = $(this).val();
    if(docType == 'cpf'){
      $('.cnpj-group').fadeToggle(300, 'swing', function(){
        if($('#cpf-group').is(':visible') === false){
          $('#cpf-group').fadeToggle();
        }
      });
    }else if(docType == 'cnpj'){
      if($('#cpf-group').is(':visible') === true){
        $('#cpf-group').fadeToggle(300, 'swing', function(){
          $('.cnpj-group').fadeToggle();
        });
      }
    }
  });

});
