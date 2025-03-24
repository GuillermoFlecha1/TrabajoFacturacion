<?php

namespace Drupal\editable_table\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Ejemplo de formulario con tabla editable usando Ajax.
 */
class EditableTableForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'editable_table_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Inicializar datos predeterminados para la tabla si no existen.
    if (!$form_state->has('table_data')) {
      $table_data = [
        1 => ['name' => 'Producto A', 'quantity' => 2, 'price' => 10.00],
        2 => ['name' => 'Producto B', 'quantity' => 3, 'price' => 20.00],
        3 => ['name' => 'Producto C', 'quantity' => 1, 'price' => 15.50],
      ];
      $form_state->set('table_data', $table_data);
    }
    else {
      $table_data = $form_state->get('table_data');
    }

    // Calcular el total general.
    $total_general = 0;
    foreach ($table_data as $item) {
      $total_general += $item['quantity'] * $item['price'];
    }

    // Definir el encabezado de la tabla.
    $header = [
      $this->t('Producto'),
      $this->t('Cantidad'),
      $this->t('Precio (€)'),
      $this->t('Total (€)'),
    ];

    $rows = [];
    // Construir las filas de la tabla.
    foreach ($table_data as $id => $item) {
      $row_total = $item['quantity'] * $item['price'];
      $rows[$id]['producto'] = [
        '#markup' => $item['name'],
      ];
      $rows[$id]['quantity'] = [
        '#type' => 'number',
        '#default_value' => $item['quantity'],
        '#min' => 0,
        '#ajax' => [
          'callback' => '::updateTableCallback',
          'wrapper' => 'editable-table-wrapper',
          'event' => 'change',
        ],
        '#attributes' => [
          'data-id' => $id,
        ],
      ];
      $rows[$id]['price'] = [
        '#markup' => number_format($item['price'], 2),
      ];
      $rows[$id]['total'] = [
        '#markup' => number_format($row_total, 2),
      ];
    }

    // Elemento tabla, se le asigna un ID para el contenedor Ajax.
    $form['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => ['id' => 'editable-table-wrapper'],
    ];

    // Mostrar el total general debajo de la tabla.
    $form['total_general'] = [
      '#type' => 'markup',
      '#markup' => '<div id="total-general"><strong>' . $this->t('Total General: €@total', ['@total' => number_format($total_general, 2)]) . '</strong></div>',
    ];

    // Botón de enviar para guardar (este ejemplo simplemente muestra un mensaje).
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Guardar'),
    ];

    return $form;
  }

  /**
   * Callback Ajax para actualizar la tabla.
   */
  public function updateTableCallback(array &$form, FormStateInterface $form_state) {
    // Obtener los datos actuales de la tabla.
    $table_data = $form_state->get('table_data');
    // Obtener el elemento que disparó el Ajax.
    $trigger = $form_state->getTriggeringElement();
    if (!empty($trigger['#attributes']['data-id'])) {
      $id = $trigger['#attributes']['data-id'];
      // Actualizar la cantidad en el array de datos.
      $new_quantity = $form_state->getValue(['table', $id, 'quantity']);
      // Si no se obtiene desde la estructura "table", usamos el valor del elemento disparador.
      if ($new_quantity === NULL) {
        $new_quantity = $trigger['#value'];
      }
      $table_data[$id]['quantity'] = $new_quantity;
      $form_state->set('table_data', $table_data);
    }
    // Devolver el elemento de la tabla para que se reconstruya vía Ajax.
    return $form['table'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Obtener los datos de la tabla desde el estado del formulario.
    $table_data = $form_state->get('table_data');

    // Iterar a través de los datos de la tabla y guardarlos en la base de datos.
    foreach ($table_data as $item) {
      // Preparar los datos para insertar en la base de datos.
      $fields = [
        'name' => $item['name'],
        'quantity' => $item['quantity'],
        'price' => $item['price'],
      ];

      // Insertar los datos en la tabla "editable_table".
      \Drupal::database()->insert('editable_table')
        ->fields($fields)
        ->execute();
    }

    // Mostrar un mensaje al usuario indicando que los datos fueron guardados correctamente.
    \Drupal::messenger()->addMessage($this->t('Datos guardados correctamente.'));
  }
}
