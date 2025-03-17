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
    /*
    // --- Número de Pedido (único, generado aleatoriamente) ---
    if ($this->entity->isNew()) {
      // Generamos el número aleatorio entre 100000 y 999999.
      $num_pedido_aleatorio = rand(100000, 999999);
    
      // Asignamos el número aleatorio a la entidad (usamos set).
      $this->entity->set('num_pedido', $num_pedido_aleatorio);
    } else {
      // Si no es nueva, obtenemos el valor de num_pedido de la entidad (usamos get).
      $num_pedido_aleatorio = $this->entity->get('num_pedido')->value;
    }
    $form['num_pedido'] = [
      '#type' => 'textfield',  // Cambiado a 'textfield' para mostrarlo como texto no editable.
      '#title' => $this->t('Número de Pedido'),
      '#default_value' => $num_pedido_aleatorio,  // El valor predeterminado será el número aleatorio.
      '#disabled' => TRUE,  // Evita que se pueda editar.
      '#required' => TRUE,
    ];
*/
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
    // --- Fecha de vencimiento ---
    if (isset($form['fecha_vencimiento'])) {
      $form['fecha_vencimiento']['#element_validate'][] = [$this, 'validateFechaVencimiento'];
    }

    // --- Cantidad ---
    if (isset($form['cantidad'])) {
      $form['cantidad']['#element_validate'][] = [$this, 'validateCantidad'];
    }

    return $form;
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
    $cantidad_numeric = floatval($cantidad);
    if ($cantidad_numeric < 1) {
      $form_state->setError($element, t('La cantidad debe ser mayor que 0'));
    }
  }
  /**
   * Validación del campo fecha_vencimiento.
   */
  public function validateFechaVencimiento($element, FormStateInterface $form_state, $form) {
    $fecha_creacion = $form_state->getValue('fecha_creacion');
    $fecha_vencimiento = $form_state->getValue('fecha_vencimiento');

    // Asegúrate de obtener el valor de las fechas correctamente
    if (is_array($fecha_creacion)) {
      $fecha_creacion = isset($fecha_creacion[0]['value']) ? $fecha_creacion[0]['value'] : $fecha_creacion['value'];
    }
    if (is_array($fecha_vencimiento)) {
      $fecha_vencimiento = isset($fecha_vencimiento[0]['value']) ? $fecha_vencimiento[0]['value'] : $fecha_vencimiento['value'];
    }

    // Validar que la fecha de vencimiento sea válida
    if (strtotime($fecha_vencimiento) === false) {
      $form_state->setError($element, t('La fecha de vencimiento no es válida.'));
    }
    // Validar que la fecha de vencimiento sea posterior a la fecha de creación
    elseif (strtotime($fecha_vencimiento) <= strtotime($fecha_creacion)) {
      $form_state->setError($element, t('La fecha de vencimiento debe ser posterior a la fecha de creación.'));
    }
  }


  /**
 * Guardado del formulario.
 */
public function submitForm(array &$form, FormStateInterface $form_state) {
  parent::submitForm($form, $form_state);
  
  // Obtener los valores del formulario.
  $user_value = $form_state->getValue('user_id');
  $producto_value = $form_state->getValue('producto_id');

  // Asignar los valores a la entidad.
  if (!empty($producto_value)) {
    $this->entity->set('producto_id', $producto_value);
  }
  if (!empty($user_value)) {
    $this->entity->set('user_id', $user_value);
  }
  $num_pedido_aleatorio = rand(100000, 999999);  // Número aleatorio entre 100000 y 999999

  // Asignar el número de pedido aleatorio a la entidad.
  $this->entity->set('num_pedido', $num_pedido_aleatorio);
  // Guardar la entidad.
  $this->entity->save();

  // Mensaje de confirmación.
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
