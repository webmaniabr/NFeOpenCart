jQuery(document).ready(function($){
    console.log('hello');
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
