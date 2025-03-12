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
      // Opcionalmente, puedes personalizar el campo aquí si es necesario
      // Por ejemplo, agregar clases o configuraciones adicionales
      $form['impuesto_id']['#attributes']['class'][] = 'impuesto-field';
    }

    // Validación personalizada para otros campos
    if (isset($form['precio'])) {
      $form['precio']['#element_validate'][] = [$this, 'validatePrecio'];
    }

    if (isset($form['cantidad'])) {
      $form['cantidad']['#element_validate'][] = [$this, 'validateCantidad'];
    }

    return $form;
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
   * Validación del campo cantidad.
   */
  public function validateCantidad($element, FormStateInterface $form_state, $form) {
    $cantidad = $form_state->getValue('cantidad');

    // Si la cantidad viene como un array indexado, extraer el primer precio.
    if (is_array($cantidad)) {
      if (isset($cantidad[0]['value'])) {
        $cantidad = $cantidad[0]['value'];
      }
      elseif (isset($cantidad['value'])) {
        $cantidad = $cantidad['value'];
      }
    }

    // Convertir a número (float) para que se pueda comparar.
    $cantidad_numeric = intval($cantidad);

    // Verificar que la cantidad sea mayor que 0.
    if ($cantidad_numeric < 1) {
      $form_state->setError($element, t('La cantidad debe ser mayor que 0'));
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
    }
    
    \Drupal::messenger()->addMessage($this->t("La entidad producto ha sido guardada"));
  }
  
  
}
