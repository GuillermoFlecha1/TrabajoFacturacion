<?php

namespace Drupal\impuestos\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear y editar la entidad Impuestos.
 */
class ImpuestosForm extends ContentEntityForm {

  /**
   * Construcción del formulario.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Llamamos al método de la clase base para construir el formulario.
    $form = parent::buildForm($form, $form_state);

    // Añadimos la validación personalizada para el campo 'valor'.
    if (isset($form['valor'])) {
      $form['valor']['#element_validate'][] = [$this, 'validateValor'];
    }

    return $form;
  }

  /**
   * Validación personalizada del campo 'valor' (porcentaje del IVA).
   */
  public function validateValor($element, FormStateInterface $form_state, $form) {
    $valor = $form_state->getValue('valor');

    // Si el valor viene como un arreglo indexado, extraer el primer valor.
    if (is_array($valor)) {
      if (isset($valor[0]['value'])) {
        $valor = $valor[0]['value'];
      }
      elseif (isset($valor['value'])) {
        $valor = $valor['value'];
      }
    }

    // Convertir a número (float) para que se pueda comparar.
    $valor_numeric = floatval($valor);

    // Registrar para depuración (opcional).
    \Drupal::logger('impuestos')->notice('Validando valor: @valor (numeric: @numeric)', [
      '@valor' => $valor,
      '@numeric' => $valor_numeric,
    ]);

    // Verificar que el valor esté en el rango de 0 a 100.
    if ($valor_numeric < 0 || $valor_numeric > 100) {
      $form_state->setError($element, t('El campo Valor debe estar entre 0 y 100.'));
    }
  }

  /**
   * Guardado del formulario.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Mensaje de confirmación al guardar la entidad.
    \Drupal::messenger()->addMessage($this->t('La entidad Impuestos ha sido guardada.'));
  }
}
