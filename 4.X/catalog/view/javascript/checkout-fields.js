(function ($) {
    'use strict';
    $(function () {
        var node = document.getElementById('wmbr-nfe-checkout-config');
        if (!node) { return; }
        var config;
        try { config = JSON.parse(node.textContent); } catch (error) { return; }
        var ids = config.fields || {};
        var scheduled = false;

        function fields(id) {
            if (!id) { return $(); }
            return $('[name="custom_field[' + id + ']"], [name="custom_field[account][' + id + ']"]');
        }
        function toggle(input, visible, required) {
            input.each(function () {
                var field = $(this);
                field.closest('.custom-field, .form-group').toggle(visible).toggleClass('required', required && visible);
                field.prop('required', required && visible);
            });
        }
        function initialize() {
            scheduled = false;
            var person = fields(ids.tipo_pessoa);
            if (person.length) {
                var selected = person.is('select') ? person.val() : person.filter(':checked').val();
                if (!selected && ids.pessoa_fisica) {
                    if (person.is('select')) { person.val(String(ids.pessoa_fisica)); }
                    else { person.filter('[value="' + ids.pessoa_fisica + '"]').prop('checked', true); }
                    selected = String(ids.pessoa_fisica);
                }
                var company = String(selected) === String(ids.pessoa_juridica);
                var available = person.is(':visible');
                toggle(fields(ids.cpf), available && !company, !company);
                toggle(fields(ids.cnpj), available && company, company);
                toggle(fields(ids.razao_social), available && company, company);
                toggle(fields(ids.insc_est), available && company, false);
            }
            if (config.mask && $.fn.mask) {
                [[fields(ids.cpf), '000.000.000-00'], [fields(ids.cnpj), 'AA.AAA.AAA/AAAA-00']].forEach(function (pair) {
                    pair[0].each(function () {
                        var field = $(this);
                        if (!field.data('wmbrMask')) { field.mask(pair[1]); field.data('wmbrMask', true); }
                    });
                });
                $('[name="postcode"]').each(function () {
                    var field = $(this), country = field.closest('form').find('[name="country_id"]').val();
                    if (String(country) === String(config.country)) {
                        if (!field.data('wmbrMask')) { field.mask('00000-000'); field.data('wmbrMask', true); }
                    } else if (field.data('wmbrMask')) { field.unmask(); field.removeData('wmbrMask'); }
                });
            }
        }
        $(document).on('change.wmbrNfe', '[name^="custom_field"]', initialize);
        $(document).on('change.wmbrNfe', '[name="country_id"]', initialize);
        $(document).ajaxComplete(initialize);
        $(document).on('input.wmbrNfe', '[name^="custom_field"]', function () {
            if (fields(ids.cnpj).is(this)) { this.value = this.value.toUpperCase(); }
        });
        // OpenCart replaces registration and address forms after AJAX requests.
        if (window.MutationObserver) {
            new MutationObserver(function () {
                if (!scheduled) { scheduled = true; window.requestAnimationFrame(initialize); }
            }).observe(document.body, { childList: true, subtree: true });
        }
        if (config.postcode && config.country) {
            $(document).on('change.wmbrNfe blur.wmbrNfe', '[name="postcode"]', function () {
                var postcode = $(this), form = postcode.closest('form');
                var country = form.find('[name="country_id"]');
                var cep = postcode.val().replace(/\D/g, '');
                if (String(country.val()) !== String(config.country) || !/^\d{8}$/.test(cep) || postcode.data('wmbrCep') === cep) { return; }
                postcode.data('wmbrCep', cep);
                // Optional lookup sends only the postcode; no API credentials or customer fields.
                $.getJSON('https://viacep.com.br/ws/' + cep + '/json/').done(function (address) {
                    if (!address || address.erro || postcode.val().replace(/\D/g, '') !== cep || String(country.val()) !== String(config.country)) { return; }
                    [['address_1', 'logradouro'], ['address_2', 'bairro'], ['city', 'localidade']].forEach(function (pair) {
                        if (typeof address[pair[1]] === 'string') { form.find('[name="' + pair[0] + '"]').val(address[pair[1]]); }
                    });
                    if (config.zones[address.uf]) { form.find('[name="zone_id"]').val(String(config.zones[address.uf])).trigger('change'); }
                }).fail(function () { postcode.removeData('wmbrCep'); });
            });
        }
        initialize();
    });
}(jQuery));
