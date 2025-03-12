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
    $form = parent::buildForm($form, $form_state);

    // Asegúrate de que el campo 'impuesto_id' esté presente
    if (isset($form['impuesto_id'])) {
      unset($form['impuesto_id']);
    }

    // Validación personalizada para otros campos
    if (isset($form['precio'])) {
      $form['precio']['#element_validate'][] = [$this, 'validatePrecio'];
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

    // Si el precio viene como un array indexado, extraer el primer precio.
    if (is_array($precio)) {
      if (isset($precio[0]['value'])) {
        $precio = $precio[0]['value'];
      }
      elseif (isset($precio['value'])) {
        $precio = $precio['value'];
      }
    }

    // Convertir a número (float) para que se pueda comparar.
    $precio_numeric = floatval($precio);

    // Verificar que el precio sea mayor que 0.
    if ($precio_numeric < 1) {
      $form_state->setError($element, t('El precio debe ser mayor que 0'));
    }
  }

  /**
   * Guardado del formulario.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    
    // Acceder a la entidad actual.
    $producto = $this->entity;
  
    // Obtener el valor del campo 'impuesto_id'
    $impuesto_value = $producto->get('impuesto_id')->getValue();
    
    // Verificar que 'impuesto_id' tiene un valor
    if (empty($impuesto_value)) {
      \Drupal::messenger()->addMessage($this->t('El campo impuesto_id está vacío.'));
    } else {
      // Extraer el target_id del primer valor del array
      $impuesto_target_id = isset($impuesto_value[0]['target_id']) ? $impuesto_value[0]['target_id'] : 'No disponible';
      
      // Mostrar el valor del impuesto_id
      \Drupal::messenger()->addMessage($this->t('Impuesto ID: @value', ['@value' => $impuesto_target_id]));
      
      // Asignar el valor de impuesto_id a la entidad antes de guardarlo
      $producto->set('impuesto_id', $impuesto_value);
    }
    
    \Drupal::messenger()->addMessage($this->t("La entidad producto ha sido guardada"));
  }
}
