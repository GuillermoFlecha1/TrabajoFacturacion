<?php

namespace Drupal\facturas\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;

/**
 * Formulario para eliminar la entidad Facturas.
 */
class FacturasDeleteForm extends ContentEntityDeleteForm {

  /**
   * Mensaje de confirmación antes de la eliminación.
   */
  public function getQuestion() {
    return $this->t('¿Estás seguro de que deseas eliminar la factura %num_factura?', ['%num_factura' => $this->entity->label()]);
  }

  /**
   * Mensaje después de la eliminación.
   */
  public function getDeletionMessage() {
    return $this->t('La factura %num_factura ha sido eliminada.', ['%num_factura' => $this->entity->label()]);
  }
}
