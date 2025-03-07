<?php

namespace Drupal\impuestos\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear y editar la entidad Impuestos.
 */
class ImpuestosForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Ejecuta la validación básica definida en la clase padre.
    parent::validateForm($form, $form_state);

    // Validación del campo "nombre".
    $nombre = $form_state->getValue('nombre');
    if (empty($nombre)) {
      $form_state->setErrorByName('nombre', $this->t('El campo Nombre es obligatorio.'));
    }

    // Validación del campo "valor" (porcentaje del IVA).
    $valor = $form_state->getValue('valor');

    // Asegurémonos de que el valor sea tratado como un número entero.
    $valor = (int) $valor; // Convierte el valor a entero.

    // Verificar si el valor es un número entero y está dentro del rango válido.
    if (!is_numeric($valor) || $valor != (int)$valor) {
      $form_state->setErrorByName('valor', $this->t('El campo Valor debe ser un número entero.'));
    }
    elseif ($valor < 0 || $valor > 100) {
      $form_state->setErrorByName('valor', $this->t('El campo Valor debe estar entre 0 y 100.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Llama al método submit del padre para guardar la entidad.
    parent::submitForm($form, $form_state);

    // Mensaje de confirmación.
    \Drupal::messenger()->addMessage($this->t('La entidad Impuestos ha sido guardada.'));
  }
}
