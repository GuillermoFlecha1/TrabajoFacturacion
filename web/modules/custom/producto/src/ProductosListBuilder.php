<?php

namespace Drupal\producto;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;


/**
 * Provides a list controller for the Producto entity.
 */
class ProductosListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['nombre'] = $this->t('Nombre');
    $header['precio'] = $this->t('Precio');
    $header['cantidad'] = $this->t('Cantidad'); // Cambiado de 'stock' a 'cantidad'
    
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    // Aquí definimos cómo se muestran las filas.
    $row['id'] = $entity->id();
    $row['nombre'] = $entity->toLink($entity->label());
    $row['precio'] = $entity->get('precio')->value; // Campo 'precio'
    $row['cantidad'] = $entity->get('cantidad')->value; // Campo 'cantidad' en lugar de 'stock'
    return $row + parent::buildRow($entity);
  }
}
