<?php

namespace Drupal\producto\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;

/**
 * Proporciona un formulario de eliminación para la entidad Producto.
 */
class ProductoDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $entity = $this->getEntity();
    return $this->t('¿Estás seguro de que deseas eliminar el producto "%nombre"?', [
      '%nombre' => $entity->label(),
    ]);
  }

}
