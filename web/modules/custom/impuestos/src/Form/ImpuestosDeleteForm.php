<?php

namespace Drupal\impuestos\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;

/**
 * Proporciona un formulario de confirmación para eliminar la entidad Impuestos.
 */
class ImpuestosDeleteForm extends ContentEntityDeleteForm {
  /**
   * Personaliza el mensaje de confirmación al eliminar un impuesto.
   */
  public function getQuestion() {
    return $this->t('¿Estás seguro de que deseas eliminar el impuesto "%name"?', [
      '%name' => $this->getEntity()->label(),
    ]);
  }
}