<?php

namespace Drupal\facturation\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para gestionar Facturas.
 */
class FacturaForm extends ContentEntityForm {

  /**
   * Construye el formulario.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Obtén el formulario base.
    $form = parent::buildForm($form, $form_state);

    // Campo Usuario (user_id).
    $default_value_User = '';
    if (!$this->entity->isNew() && !$this->entity->get('user_id')->isEmpty()) {
      $default_value_User = $this->entity->get('user_id')->first()->getValue()['target_id'];
    }
    $form['user_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Usuario'),
      '#options' => $this->getUserOptions(),
      '#default_value' => $default_value_User,
      '#required' => TRUE,
    ];

    // (Aquí pueden ir otros campos generales de la factura, si los hay.)

    // --- Tabla de "line items" (productos a incluir en la factura) ---
    // La tabla se ubicará al final del formulario.
    // Determinamos el número de filas a mostrar; inicialmente es 1.
    $num_items = $form_state->get('num_items');
    if ($num_items === NULL) {
      $num_items = 1;
      $form_state->set('num_items', $num_items);
    }

    // Contenedor AJAX para la tabla.
    $form['line_items'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Producto'),
        $this->t('Cantidad'),
        $this->t('Acción'),
      ],
      '#prefix' => '<div id="line-items-wrapper">',
      '#suffix' => '</div>',
    ];

    // Recuperar los valores actuales (si existen) para preservar al recargar.
    $line_items_values = $form_state->getValue('line_items');
    for ($i = 0; $i < $num_items; $i++) {
      // Desplegable para seleccionar el producto.
      $form['line_items'][$i]['producto'] = [
        '#type' => 'select',
        '#options' => $this->getProductoOptions(),
        '#default_value' => isset($line_items_values[$i]['producto']) ? $line_items_values[$i]['producto'] : '',
        '#required' => TRUE,
      ];
      // Campo numérico para la cantidad.
      $form['line_items'][$i]['cantidad'] = [
        '#type' => 'number',
        '#default_value' => isset($line_items_values[$i]['cantidad']) ? $line_items_values[$i]['cantidad'] : 1,
        '#min' => 1,
        '#required' => TRUE,
      ];
      // Columna de acciones: dos botones, "Eliminar" y "Añadir producto".
      $form['line_items'][$i]['actions'] = [
        '#type' => 'container',
      ];
      // Botón para eliminar la fila.
      $form['line_items'][$i]['actions']['remove'] = [
        '#type' => 'submit',
        '#value' => $this->t('Eliminar'),
        '#name' => 'remove_item_' . $i,
        '#submit' => ['::removeItemSubmit'],
        '#limit_validation_errors' => [],
        '#ajax' => [
          'callback' => '::lineItemsAjaxCallback',
          'wrapper' => 'line-items-wrapper',
        ],
      ];
      // Botón para añadir una nueva fila (renombrado a "Añadir producto").
      $form['line_items'][$i]['actions']['add'] = [
        '#type' => 'submit',
        '#value' => $this->t('Añadir producto'),
        '#name' => 'add_item_' . $i,
        '#submit' => ['::addItemSubmit'],
        '#limit_validation_errors' => [],
        '#ajax' => [
          'callback' => '::lineItemsAjaxCallback',
          'wrapper' => 'line-items-wrapper',
        ],
      ];
    }

    // Botón para generar la factura.
    $form['generate_invoice'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generar Factura'),
      '#submit' => ['::generateInvoiceSubmit'],
    ];

    return $form;
  }

  /**
   * AJAX callback para actualizar la tabla de line items.
   */
  public function lineItemsAjaxCallback(array &$form, FormStateInterface $form_state) {
    return $form['line_items'];
  }

  /**
   * Handler para añadir una fila.
   */
  public function addItemSubmit(array &$form, FormStateInterface $form_state) {
    $num_items = $form_state->get('num_items');
    $num_items++;
    $form_state->set('num_items', $num_items);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Handler para eliminar una fila.
   */
  public function removeItemSubmit(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $name = $trigger['#name']; // e.g., remove_item_0
    $index = str_replace('remove_item_', '', $name);

    $line_items = $form_state->getValue('line_items');
    if (isset($line_items[$index])) {
      unset($line_items[$index]);
    }
    // Reindexar.
    $line_items = array_values($line_items);
    $form_state->set('num_items', count($line_items));
    $form_state->setValue('line_items', $line_items);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Handler para generar la factura.
   */
  public function generateInvoiceSubmit(array &$form, FormStateInterface $form_state) {
    $line_items = $form_state->getValue('line_items');
    if (empty($line_items)) {
      \Drupal::messenger()->addError($this->t('No se han añadido productos.'));
      return;
    }
    // Aquí implementarías la lógica para procesar la factura con los elementos.
    \Drupal::messenger()->addMessage($this->t('Factura generada.'));
  }

  /**
   * Validación del campo fecha de vencimiento.
   */
  public function validateFechaVencimiento($element, FormStateInterface $form_state, $form) {
    $fecha_creacion = $form_state->getValue('fecha_creacion');
    $fecha_vencimiento = $form_state->getValue('fecha_vencimiento');

    if (is_array($fecha_creacion)) {
      $fecha_creacion = isset($fecha_creacion[0]['value']) ? $fecha_creacion[0]['value'] : $fecha_creacion['value'];
    }
    if (is_array($fecha_vencimiento)) {
      $fecha_vencimiento = isset($fecha_vencimiento[0]['value']) ? $fecha_vencimiento[0]['value'] : $fecha_vencimiento['value'];
    }
    
    if (strtotime($fecha_vencimiento) === false) {
      $form_state->setError($element, $this->t('La fecha de vencimiento no es válida.'));
    }
    elseif (strtotime($fecha_vencimiento) <= strtotime($fecha_creacion)) {
      $form_state->setError($element, $this->t('La fecha de vencimiento debe ser posterior a la fecha de creación.'));
    }
  }

  /**
   * Validación del campo cantidad.
   */
  public function validateCantidad($element, FormStateInterface $form_state, $form) {
    $cantidad = $form_state->getValue('cantidad');
    if (is_array($cantidad)) {
      if (isset($cantidad[0]['value'])) {
        $cantidad = $cantidad[0]['value'];
      }
      elseif (isset($cantidad['value'])) {
        $cantidad = $cantidad['value'];
      }
    }
    if (floatval($cantidad) < 1) {
      $form_state->setError($element, $this->t('La cantidad debe ser mayor que 0'));
    }
  }

  /**
   * Obtiene las opciones para el campo select de usuarios.
   */
  private function getUserOptions() {
    $options = [];
    $users = \Drupal::entityTypeManager()->getStorage('user')->loadMultiple();
    foreach ($users as $user) {
      $options[$user->id()] = $user->getDisplayName();
    }
    return $options;
  }
  
  /**
   * Obtiene las opciones para el campo select de productos.
   */
  private function getProductoOptions() {
    $options = [];
    $productos = \Drupal::entityTypeManager()->getStorage('producto')->loadMultiple();
    foreach ($productos as $producto) {
      $options[$producto->id()] = $producto->get('nombre')->value;
    }
    return $options;
  }
}