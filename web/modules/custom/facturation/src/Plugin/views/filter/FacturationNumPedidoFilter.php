<?php

namespace Drupal\facturation\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Filtro personalizado para el número de pedido con prefijo.
 *
 * Permite buscar registros ingresando por ejemplo "BI + 1" o "BIV1".
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("facturation_num_pedido_filter")
 */
class FacturationNumPedidoFilter extends FilterPluginBase {

  /**
   * Define las opciones de operador.
   *
   * En este ejemplo solo usaremos “igual a”.
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
    // Se utiliza el identificador expuesto que debe coincidir con la clave configurada en la vista.
    $exposed_id = !empty($this->options['exposed_identifier']) ? $this->options['exposed_identifier'] : 'num_pedido';
    
    $form[$exposed_id] = [
      '#type' => 'textfield',
      '#title' => $this->t('Número de Pedido'),
      '#default_value' => $this->value,
      '#description' => $this->t('Ejemplo: BI1 o BIV1'),
    ];
  }

/**
 * Modifica la consulta de Views en función del filtro.
 */
public function query() {
    // Obtener el valor ingresado en el filtro.
    $value = $this->value;
  
    // Si el valor viene como array, extraemos el primer elemento.
    if (is_array($value)) {
      $value = reset($value);
    }
  
    // Asegurarse de trabajar con una cadena.
    $value = trim((string) $value);
  
    if ($value === NULL || $value === '') {
      return;
    }
  
    // Asegurarse de tener el alias de la tabla.
    $this->ensureMyTable();
    // Suponemos que el campo en la tabla es "num_pedido".
    $field = $this->realField;
  
    // Si se ingresó un valor con prefijo y número, ej. "BI1" o "BIV1".
    if (preg_match('/^(BI(?:V)?)\s*\+?\s*(\d+)$/i', $value, $matches)) {
      $prefix = strtoupper($matches[1]);
      $num_value = $matches[2];
  
      // Condición para el campo num_pedido.
      $this->query->addWhere($this->options['group'], "$this->tableAlias.$field", $num_value, '=');
  
      // Condición adicional según el prefijo.
      if ($prefix === 'BI') {
        // "BI" corresponde a estado 1 o 2.
        $this->query->addWhere($this->options['group'], "$this->tableAlias.estado", [1, 2], 'IN');
      }
      elseif ($prefix === 'BIV') {
        // "BIV" corresponde a estado 3.
        $this->query->addWhere($this->options['group'], "$this->tableAlias.estado", 3, '=');
      }
    }
  }  
}