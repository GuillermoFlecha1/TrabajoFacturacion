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

<<<<<<< HEAD
    // Validación personalizada para el campo precio.
=======
    // Asegúrate de que el campo 'impuesto_id' esté presente
    // Eliminamos el unset ya que no queremos eliminar el campo
    // unset($form['impuesto_id']);  // Eliminar esta línea
    
    // Validación personalizada para otros campos
>>>>>>> ff4193a2 (El impuesto_id se muestra correctamente en la lista de productos pero no se guarda al añadir o editar)
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
  parent::submitForm($form, $form_state);

  // Obtener la entidad producto
  $producto = $this->entity;

  // Obtener el valor de 'impuesto_id' del formulario
  $impuesto_target_id = $form_state->getValue('impuesto_id');

  // Verificar si se ha seleccionado un valor para impuesto_id
  if ($impuesto_target_id) {
    // Asignar el valor de 'impuesto_id' como una referencia de entidad
    $producto->set('impuesto_id', ['target_id' => $impuesto_target_id]);

    \Drupal::messenger()->addMessage($this->t('Se ha asignado el impuesto_id: @impuesto', ['@impuesto' => $impuesto_target_id]));
  } else {
    \Drupal::messenger()->addMessage($this->t('No se ha seleccionado un impuesto.'));
  }

  \Drupal::messenger()->addMessage($this->t("La entidad producto ha sido guardada"));
}


}
