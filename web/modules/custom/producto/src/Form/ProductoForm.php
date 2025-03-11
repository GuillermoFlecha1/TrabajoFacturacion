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

    // Validación personalizada para el campo cantidad.
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

    // Verificar si es un nuevo producto o una actualización
    \Drupal::messenger()->addMessage($this->t("La entidad producto ha sido guardada"));
  }
}