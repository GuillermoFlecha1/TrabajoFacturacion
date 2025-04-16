(function ($, Drupal) {
  // Behavior para enviar los IDs seleccionados al hacer submit.
  Drupal.behaviors.facturaBatchSubmit = {
    attach: function (context, settings) {
      $('form#factura-batch-action-form', context)
        .once('facturaBatchSubmit')
        .on('submit', function () {
          var selected = [];
          $('.factura-checkbox:checked').each(function () {
            selected.push($(this).val());
          });
          console.log('Checkbox seleccionados:', selected);
          $('#edit-selected-invoices').val(selected.join(','));
        });
    }
  };

  // Behavior para el checkbox "Seleccionar todos".
  Drupal.behaviors.facturaCheckboxSelector = {
    attach: function (context, settings) {
      const $selectAll = $('#factura-checkbox-select-all', context);
      const $checkboxes = $('.factura-checkbox', context);
      if ($selectAll.length && $checkboxes.length) {
        $selectAll.once('factura-select-all').on('change', function () {
          const checked = $(this).is(':checked');
          $checkboxes.prop('checked', checked);
        });
      }
    }
  };
})(jQuery, Drupal);
