(function (Drupal) {
  Drupal.behaviors.invoiceTooltip = {
    attach: function (context) {
      if (typeof tippy === 'function') {
        // Seleccionamos todos los .invoice-amount dentro de 'context'.
        tippy(context.querySelectorAll('.invoice-amount'), {
          placement: 'top',
          animation: 'shift-away',
          allowHTML: false,
        });
      }
    }
  };
})(Drupal);

/*
Añadir este codigo al campo de precio total final en "Editar > Reescribir Resultados > Reescribir la salida de este campo con texto personalizado"
Ahi añadiremos en el cuadro de texto el siguiente código:


<span
  class="invoice-amount"
  data-tippy-content="Subtotal: €{{ total_importe }}&#10;Impuesto: €{{ total_impuesto }}&#10;% Impuesto: {{ tax_percentage|number_format(2) }}%">
  €{{ total_final }}
</span>


*/