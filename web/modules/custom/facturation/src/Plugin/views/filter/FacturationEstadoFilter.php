<?php

namespace Drupal\facturation\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Filtro personalizado para el estado de la factura.
 *
 * Permite buscar registros ingresando por ejemplo "Borrador", "Finalizada", 
 * "Rectificada" o "Rectificativa".
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("facturation_estado_filter")
 */
class FacturationEstadoFilter extends FilterPluginBase {

  /**
   * Define las opciones de operador.
   *
   * En este ejemplo se utiliza únicamente el operador "igual a".
   */
  public function operatorOptions() {
    return [
      '=' => $this->t('Equals'),
    ];
  }

  /**
   * Construye el formulario expuesto para el filtro.
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state) {
    // Utilizamos el identificador expuesto para que coincida con la clave configurada en la vista.
    $exposed_id = !empty($this->options['exposed_identifier']) ? $this->options['exposed_identifier'] : 'estado';
    
    $form[$exposed_id] = [
      '#type' => 'textfield',
      '#title' => $this->t('Estado'),
      '#default_value' => $this->value,
      '#description' => $this->t('Ejemplo: Borrador, Finalizada, Rectificada, Rectificativa'),
    ];
  }

  /**
   * Modifica la consulta de Views en función del filtro.
   */
  public function query() {
    // Obtener el valor ingresado.
    $value = $this->value;
    if (is_array($value)) {
      $value = reset($value);
    }
    // Convertir el valor a cadena y eliminar espacios.
    $value = trim((string) $value);

    if ($value === '' || $value === NULL) {
      return;
    }

    // Mapeo que relaciona el nombre del estado (en mayúsculas) con el valor entero.
    $estado_lookup = [
      'BORRADOR' => 0,
      'FINALIZADA' => 1,
      'RECTIFICADA' => 2,
      'RECTIFICATIVA' => 3,
    ];

    $value_key = strtoupper($value);

    // Si el nombre ingresado está definido en el mapeo, obtenemos su valor entero.
    if (isset($estado_lookup[$value_key])) {
      $estado_number = $estado_lookup[$value_key];

      // Asegurarse de tener el alias de la tabla.
      $this->ensureMyTable();
      // El campo en la tabla es "estado" (o el que hayas configurado en tu vista).
      $field = $this->realField;
      $this->query->addWhere($this->options['group'], "$this->tableAlias.$field", $estado_number, '=');
    }
  }
}