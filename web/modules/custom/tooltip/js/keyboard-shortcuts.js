(function (Drupal, drupalSettings, $) {
    Drupal.behaviors.invoiceKeyboard = {
      attach: function (context) {
        var $table = $('table.views-table', context);
        if (!$table.length) {
          return;
        }
  
        var $rows = $table.find('tbody tr');
        if (!$rows.length) {
          return;
        }
  
        var idx = 0;
        selectRow(idx);
  
        // Listener global para keydown, capturando primero las flechas
        $(document).off('keydown.invoice').on('keydown.invoice', function (e) {
          // No interferir si el foco está en un input o textarea
          if ($(e.target).is('input, textarea, [contenteditable]')) {
            return;
          }
  
          // Interceptar flechas para evitar scroll
          if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            e.stopPropagation();
  
            if (e.key === 'ArrowDown' && idx < $rows.length - 1) {
              idx++;
              selectRow(idx);
            }
            else if (e.key === 'ArrowUp' && idx > 0) {
              idx--;
              selectRow(idx);
            }
            return;
          }
  
          // Otras teclas de acción
          switch (e.key.toLowerCase()) {
            case 'e':
              e.preventDefault();
              triggerAction('Editar');
              break;
            case 'v':
              e.preventDefault();
              triggerAction('Visualizar PDF');
              break;
            case 'r':
              e.preventDefault();
              triggerAction('Rectificar');
              break;
              case 'd':
              e.preventDefault();
              triggerAction('Eliminar');
              break;
          }
        });
  
        function selectRow(i) {
          $rows.removeClass('selected');
          var $row = $rows.eq(i).addClass('selected');
          // Scroll si la fila queda fuera de vista
          var top    = $row.offset().top;
          var bottom = top + $row.outerHeight();
          var vTop   = $(window).scrollTop();
          var vBot   = vTop + $(window).height();
          if (top < vTop) {
            $(window).scrollTop(top - 20);
          }
          else if (bottom > vBot) {
            $(window).scrollTop(bottom - $(window).height() + 20);
          }
        }
  
        function triggerAction(actionText) {
          var $link = $rows.eq(idx).find('a').filter(function () {
            return $(this).text().trim().indexOf(actionText) === 0;
          }).first();
          if ($link.length) {
            if ($link.attr('target') === '_blank') {
              window.open($link.attr('href'));
            } else {
              $link[0].click();
            }
          }
        }
      }
    };
  })(Drupal, drupalSettings, jQuery);
  