(function (Drupal) {
  Drupal.behaviors.invoiceTooltip = {
    attach: function (context) {
      if (typeof tippy === 'function') {
        tippy(context.querySelectorAll('.invoice-amount'), {
          placement: 'top',
          animation: 'shift-away',
          allowHTML: false
        });
      }
    }
  };
})(Drupal);
