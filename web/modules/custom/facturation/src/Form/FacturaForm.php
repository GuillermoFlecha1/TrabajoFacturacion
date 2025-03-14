<?php

namespace Drupal\facturation\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para gestionar Facturas.
 */
class FacturaForm extends ContentEntityForm {

  /**
   * Construcción del formulario.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Obtén el formulario base.
    $form = parent::buildForm($form, $form_state);

    // --- Número de Pedido ---
    if ($this->entity->isNew()) {
      // Generar un número de pedido aleatorio.
      $num_pedido = mt_rand(10000000, 99999999);

      // Verificar si el número de pedido ya existe en la base de datos.
      $query = \Drupal::entityQuery('facturas')
        ->condition('num_pedido', $num_pedido)
        ->range(0, 1) // Solo necesitamos verificar si existe uno.
        ->accessCheck(FALSE); // Desactiva la verificación de acceso

      // Si ya existe, generar un nuevo número de pedido.
      while ($query->execute()) {
        $num_pedido = mt_rand(10000000, 99999999);
        $query->condition('num_pedido', $num_pedido); // Actualizar la condición de la consulta.
      }

      // Asignar el número de pedido único a la entidad.
      $this->entity->set('num_pedido', $num_pedido);

      // Mostrar el número de pedido en el formulario.
      $form['num_pedido'] = [
        '#type' => 'item',
        '#title' => $this->t('Número de Pedido'),
        '#markup' => $num_pedido,
      ];
    } else {
      // Si la entidad no es nueva, mostrar el número de pedido actual.
      $form['num_pedido'] = [
        '#type' => 'item',
        '#title' => $this->t('Número de Pedido'),
        '#markup' => $this->entity->get('num_pedido')->value,
      ];
    }

    // --- Fecha de Creación ---
    $today = date('Y-m-d');
    if ($this->entity->isNew()) {
      $form['fecha_creacion'] = [
        '#type' => 'date',
        '#title' => $this->t('Fecha de Creación'),
        '#default_value' => $today,
        '#required' => TRUE,
      ];
    } else {
      $form['fecha_creacion'] = [
        '#type' => 'date',
        '#title' => $this->t('Fecha de Creación'),
        '#default_value' => date('Y-m-d', $this->entity->get('fecha_creacion')->value),
        '#required' => TRUE,
      ];
    }

    // --- Fecha de Vencimiento ---
    $default_vencimiento = date('Y-m-d', strtotime('+4 years', strtotime($today)));
    if ($this->entity->isNew()) {
      $form['fecha_vencimiento'] = [
        '#type' => 'date',
        '#title' => $this->t('Fecha de Vencimiento'),
        '#default_value' => $default_vencimiento,
        '#required' => TRUE,
      ];
    } else {
      $form['fecha_vencimiento'] = [
        '#type' => 'date',
        '#title' => $this->t('Fecha de Vencimiento'),
        '#default_value' => date('Y-m-d', $this->entity->get('fecha_vencimiento')->value),
        '#required' => TRUE,
      ];
    }
    // Prepara el valor por defecto (si es edición).
    $default_value_User = '';
    if (!$this->entity->isNew() && !$this->entity->get('user_id')->isEmpty()) {
      // Para un campo de referencia de valor único, obtenemos el primer item.
      $default_value_User = $this->entity->get('user_id')->first()->getValue()['target_id'];
    }
    // --- Usuario (user_id) ---
    if (isset($form['user_id']['widget'][0]['target_id'])) {
      $form['user_id']['widget'][0]['target_id'] = [
        '#type' => 'select',
        '#title' => $this->t('Usuario'),
        '#options' => $this->getUserOptions(),
        '#default_value' => $default_value_User,
        '#required' => TRUE,
      ];
    } else {
      $form['user_id'] = [
        '#type' => 'select',
        '#title' => $this->t('Usuario'),
        '#options' => $this->getUserOptions(),
        '#default_value' => $default_value_User,
        '#required' => TRUE,
      ];
    }
    // Prepara el valor por defecto (si es edición).
    $default_value_Product = '';
    if (!$this->entity->isNew() && !$this->entity->get('producto_id')->isEmpty()) {
      // Para un campo de referencia de valor único, obtenemos el primer item.
      $default_value_Product = $this->entity->get('producto_id')->first()->getValue()['target_id'];
    }
    // --- Producto (producto_id) ---
    if (isset($form['producto_id']['widget'][0]['target_id'])) {
      $form['producto_id']['widget'][0]['target_id'] = [
        '#type' => 'select',
        '#title' => $this->t('Producto'),
        '#options' => $this->getProductoOptions(),
        '#default_value' => $default_value_Product,
        '#required' => TRUE,
      ];
    } else {
      $form['producto_id'] = [
        '#type' => 'select',
        '#title' => $this->t('Producto'),
        '#options' => $this->getProductoOptions(),
        '#default_value' => $default_value_Product,
        '#required' => TRUE,
      ];
    }

    // --- Cantidad ---
    if (isset($form['cantidad'])) {
      $form['cantidad']['#type'] = 'number';
      $form['cantidad']['#title'] = $this->t('Cantidad');
      $form['cantidad']['#required'] = TRUE;
    } else {
      $form['cantidad'] = [
        '#type' => 'number',
        '#title' => $this->t('Cantidad'),
        '#required' => TRUE,
      ];
    }

    // --- Total Final ---
    $total_final = $this->entity->isNew() ? $this->t('Se calculará automáticamente') : $this->entity->get('total_final')->value;
    $form['total_final'] = [
      '#type' => 'item',
      '#title' => $this->t('Total Final'),
      '#markup' => $total_final,
    ];

    return $form;
  }

  /**
   * Validación del formulario.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $fecha_creacion = $form_state->getValue('fecha_creacion');
    $fecha_vencimiento = $form_state->getValue('fecha_vencimiento');

    if (strtotime($fecha_vencimiento) <= strtotime($fecha_creacion)) {
      $form_state->setErrorByName('fecha_vencimiento', $this->t('La fecha de vencimiento debe ser posterior a la fecha de creación.'));
    }
  }

  /**
   * Guardado del formulario.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    
    $user_value = $form_state->getValue('user_id');
    $producto_value = $form_state->getValue('producto_id');
    if (!empty($producto_value)) {
      // Asigna el valor directamente a la entidad.
      $this->entity->set('producto_id', $producto_value);
    }
    if (!empty($user_value)) {
      // Asigna el valor directamente a la entidad.
      $this->entity->set('user_id', $user_value);
    }
    
    // Guarda la entidad.
    $this->entity->save();
    \Drupal::messenger()->addMessage($this->t('La factura ha sido guardada.'));
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
