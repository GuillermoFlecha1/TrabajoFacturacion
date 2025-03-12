<?php

namespace Drupal\producto\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para gestionar Productos.
 */
class ProductoForm extends ContentEntityForm {

  /**
   * Construcción del formulario.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Construye el formulario a partir de la definición del formulario base.
    $form = parent::buildForm($form, $form_state);

    // Validación personalizada para el campo precio.
    if (isset($form['precio'])) {
      $form['precio']['#element_validate'][] = [$this, 'validatePrecio'];
    }
    // Validación personalizada para el campo cantidad.
    if (isset($form['cantidad'])) {
      $form['cantidad']['#element_validate'][] = [$this, 'validateCantidad'];
    }

    // Elimina el widget original del campo impuesto_id.
    if (isset($form['impuesto_id'])) {
      unset($form['impuesto_id']);
    }

    // Prepara el valor por defecto (si es edición).
    $default_value = '';
    if (!$this->entity->isNew() && !$this->entity->get('impuesto_id')->isEmpty()) {
      // Para un campo de referencia de valor único, obtenemos el primer item.
      $default_value = $this->entity->get('impuesto_id')->first()->getValue()['target_id'];
    }

    // Agrega el campo de impuesto_id como un desplegable (select) anidado.
    // Esto genera una estructura del tipo:
    // [ 0 => [ 'target_id' => <select> ] ]
    $form['impuesto_id'] = [
      0 => [
        'target_id' => [
          '#type' => 'select',
          '#title' => $this->t('Impuesto'),
          '#options' => $this->getImpuestosOptions(),
          '#default_value' => $default_value,
          '#required' => TRUE,
        ],
      ],
    ];

    return $form;
  }

  /**
   * Obtiene las opciones para el campo select de impuestos.
   */
  private function getImpuestosOptions() {
    $options = [];
    // Carga todas las entidades de tipo 'impuestos'.
    $impuestos = \Drupal::entityTypeManager()->getStorage('impuestos')->loadMultiple();
    // Prepara las opciones: la clave es el ID y el valor el campo 'nombre'.
    foreach ($impuestos as $impuesto) {
      $options[$impuesto->id()] = $impuesto->get('nombre')->value;
    }
    return $options;
  }

  /**
   * Validación del campo precio.
   */
  public function validatePrecio($element, FormStateInterface $form_state, $form) {
    $precio = $form_state->getValue('precio');
    if (is_array($precio)) {
      if (isset($precio[0]['value'])) {
        $precio = $precio[0]['value'];
      }
      elseif (isset($precio['value'])) {
        $precio = $precio['value'];
      }
    }
    $precio_numeric = floatval($precio);
    if ($precio_numeric < 1) {
      $form_state->setError($element, t('El precio debe ser mayor que 0'));
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
    $cantidad_numeric = intval($cantidad);
    if ($cantidad_numeric < 1) {
      $form_state->setError($element, t('La cantidad debe ser mayor que 0'));
    }
  }

  /**
   * Guardado del formulario.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Se guarda el formulario base.
    parent::submitForm($form, $form_state);
    
    // Obtener el valor anidado del campo impuesto_id.
    $impuesto_values = $form_state->getValue('impuesto_id');
    // Se asume que la estructura es: [0 => ['target_id' => $valor]]
    // Asignamos directamente ese array a la entidad.
    $this->entity->set('impuesto_id', $impuesto_values);
    $this->entity->save();

    \Drupal::messenger()->addMessage($this->t('La entidad producto ha sido guardada'));
  }
}