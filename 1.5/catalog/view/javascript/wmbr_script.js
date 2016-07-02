jQuery(document).ready(function($){

  //Mask Fields
  $('#cpf').mask('999.999.999-99');
  $('#cnpj').mask('99.999.999/9999-99');

  //General Correios Init
  correios.init( 'qS4SKlmAXR21h7wrBMcs0SZyXauLqo5m', 'nkKkInYJ5QvogYn1xj4lk7w3hkhA8qzruoKzuLf6UyBtSIJL' );
  $('input[name="postcode"]').correios( 'input[name="address_1"]', 'input[name="address_2"]', 'input[name="city"]', 'input[name="zone_id"]', '.correios-loading' );

  //Correios Init on Add Address
  $(document).on('add-address', function(e, address_row){
    $('input[name="address['+address_row+'][postcode]"]').parents('tr').insertAfter($('input[name="address['+address_row+'][tax_id]').parents('tr'));
    correios.init( 'qS4SKlmAXR21h7wrBMcs0SZyXauLqo5m', 'nkKkInYJ5QvogYn1xj4lk7w3hkhA8qzruoKzuLf6UyBtSIJL' );
    $('input[name="address['+address_row+'][postcode]"]').correios( 'input[name="address['+address_row+'][address_1]"]', 'input[name="address['+address_row+'][address_2]"]', 'input[name="address['+address_row+'][city]"]', 'input[name="address['+address_row+'][zone_id]"]', '.correios-loading' );

    $('#tab-address-'+address_row).prepend($('<div class="correios-loading"></div>'));
  });

  //Correios Init on Admin Add Customer
  $(document).on('start-correios', function(){
    if($('input[name="postcode"]').length > 0){
      correios.init( 'qS4SKlmAXR21h7wrBMcs0SZyXauLqo5m', 'nkKkInYJ5QvogYn1xj4lk7w3hkhA8qzruoKzuLf6UyBtSIJL' );
      $('input[name="postcode"]').correios( 'input[name="address_1"]', 'input[name="address_2"]', 'input[name="city"]', 'input[name="zone_id"]', '.correios-loading' );
    }
  });

  //Control document type display
  $(document).on('change', 'input[name="doctype"]', function(){
    console.log('changing');
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
