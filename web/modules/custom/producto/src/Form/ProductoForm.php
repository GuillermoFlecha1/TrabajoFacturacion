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

    // Validación personalizada para el campo precio.
    if (isset($form['precio'])) {
      $form['precio']['#element_validate'][] = [$this, 'validatePrecio'];
    }

    // Prepara el valor por defecto (si es edición).
    $default_value = '';
    if (!$this->entity->isNew() && !$this->entity->get('impuesto_id')->isEmpty()) {
      // Para un campo de referencia de valor único, obtenemos el primer item.
      $default_value = $this->entity->get('impuesto_id')->first()->getValue()['target_id'];
    }

    // En vez de unsetear, sobreescribe el widget del campo 'impuesto_id'.
    if (isset($form['impuesto_id']['widget'][0]['target_id'])) {
      $form['impuesto_id']['widget'][0]['target_id'] = [
        '#type' => 'select',
        '#title' => $this->t('Impuesto'),
        '#options' => $this->getImpuestosOptions(),
        '#default_value' => $default_value,
        '#required' => TRUE,
      ];
    }
    else {
      // Si la estructura no es la esperada, agrega el elemento de forma directa.
      $form['impuesto_id'] = [
        '#type' => 'select',
        '#title' => $this->t('Impuesto'),
        '#options' => $this->getImpuestosOptions(),
        '#default_value' => $default_value,
        '#required' => TRUE,
      ];
    }

    return $form;
  }

  /**
   * Obtiene las opciones para el campo select de impuestos.
   */
  private function getImpuestosOptions() {
    $options = [];
    // Cargar todas las entidades de tipo 'impuestos'.
    $impuestos = \Drupal::entityTypeManager()->getStorage('impuestos')->loadMultiple();
    foreach ($impuestos as $impuesto) {
      $options[$impuesto->id()] = $impuesto->get('nombre')->value;
    }
    \Drupal::logger('producto')->notice('Impuestos options: ' . print_r($options, TRUE));
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
   * Guardado del formulario.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Se guarda el formulario base.
    parent::submitForm($form, $form_state);

    // Obtener el valor del campo 'impuesto_id'.
    $impuesto_value = $form_state->getValue('impuesto_id');
    if (!empty($impuesto_value)) {
      // Asigna el valor directamente a la entidad.
      $this->entity->set('impuesto_id', $impuesto_value);
    }
    // Guarda la entidad.
    $this->entity->save();
    // Mensaje de confirmación.
    \Drupal::messenger()->addMessage($this->t('La entidad producto ha sido guardada.'));
  }
}