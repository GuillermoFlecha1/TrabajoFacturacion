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
    $precio = floatval($form_state->getValue('precio'));
    if ($precio <= 0) {
      $form_state->setError($element, t('El precio debe ser un número mayor a 0.'));
    }
  }
  /**
   * Validación del campo cantidad.
   */
  public function validateCantidad($element, FormStateInterface $form_state, $form) {
    $cantidad = intval($form_state->getValue('cantidad'));
    if ($cantidad <= 0) {
      $form_state->setError($element, t('La cantidad debe ser un número mayor a 0.'));
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