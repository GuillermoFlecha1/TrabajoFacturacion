<?php

namespace Drupal\facturation\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler para campos calculados de facturas.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("facturation_calculated_field")
 */
class FacturationCalculatedField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // No agregamos ningún campo a la consulta SQL.
    // Los datos se calcularán en hook_views_pre_render().
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $field_name = $this->field;
    $property = 'facturas_' . $field_name;
    
    // Si el valor existe, renderizarlo
    if (isset($values->$property)) {
      $value = $values->$property;
      
      // Formatear según el tipo de campo
      if ($field_name == 'tax_percentage') {
        return number_format($value, 2) . '%';
      } elseif (in_array($field_name, ['total_importe', 'total_impuesto'])) {
        return number_format($value, 2) . ' €';
      }
      
      return $value;
    }
    
    return '';
  }
}