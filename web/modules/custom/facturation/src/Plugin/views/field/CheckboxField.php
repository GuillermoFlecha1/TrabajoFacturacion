<?php

namespace Drupal\facturation\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\Annotation\ViewsField;

/**
 * Renders a checkbox field in the view.
 *
 * @ViewsField("facturation_checkbox_field")
 */
class CheckboxField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
  }

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * Render checkbox por fila.
   */
  public function render(ResultRow $values) {
    // Se obtiene el ID usando el alias correcto.
    $id = $values->facturas_id ?? $this->getValue($values);
    if ($id === NULL) {
      $id = '';
    }

    return [
      '#type' => 'html_tag',
      '#tag' => 'input',
      '#attributes' => [
        'type' => 'checkbox',
        'name' => 'facturas_seleccionadas[]',
        'value' => $id,
        'class' => ['factura-checkbox'],
        'aria-label' => $this->t('Seleccionar factura @id', ['@id' => $id]),
      ],
    ];
  }

  /**
   * Render header con checkbox "Seleccionar todos".
   */
  public function renderHeader() {
    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['factura-checkbox-header']],
      'checkbox' => [
        '#type' => 'html_tag',
        '#tag' => 'input',
        '#attributes' => [
          'type' => 'checkbox',
          'id' => 'factura-checkbox-select-all',
          'class' => ['factura-checkbox-select-all'],
          'title' => $this->t('Seleccionar todos'),
          'aria-label' => $this->t('Seleccionar todas las facturas'),
        ],
      ],
    ];
  }   

  /**
   * Agrega el JS necesario para el checkbox "Seleccionar todos".
   */
  public function preRender(&$values) {
    $build = parent::preRender($values);
    // Se adjunta la librería para el JS.
    $this->view->element['#attached']['library'][] = 'facturation/checkbox_selector';
    return $build;
  }
}
