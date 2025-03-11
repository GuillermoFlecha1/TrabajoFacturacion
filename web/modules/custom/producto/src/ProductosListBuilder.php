<?php

namespace Drupal\producto;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;  // Asegúrate de importar Link

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
    $header['acciones'] = $this->t('Acciones'); // Asegúrate de que "acciones" es el nombre que quieres mostrar
    
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    // Asegúrate de que la entidad no sea null
    if (!$entity) {
      return;
    }
    // Aquí definimos cómo se muestran las filas.
    $row['id'] = $entity->id();
    $row['nombre'] = $entity->toLink($entity->label());
    $row['precio'] = $entity->get('precio')->value; // Campo 'precio'
    $row['cantidad'] = $entity->get('cantidad')->value; // Campo 'cantidad' en lugar de 'stock'

    // Agregar enlaces de acciones
    $add_url = \Drupal\Core\Url::fromRoute('producto.add_form', ['producto' => $entity->id()]);
    $edit_url = \Drupal\Core\Url::fromRoute('producto.edit_form', ['producto' => $entity->id()]);
    $delete_url = \Drupal\Core\Url::fromRoute('producto.delete_form', ['producto' => $entity->id()]);

    // Definir las acciones de editar y eliminar
    $row['acciones'] = [
      'data' => [
        Link::fromTextAndUrl($this->t('Añadir'), $add_url)->toRenderable(),
        ['#markup' => ' | '],
        Link::fromTextAndUrl($this->t('Editar'), $edit_url)->toRenderable(),
        ['#markup' => ' | '],
        Link::fromTextAndUrl($this->t('Eliminar'), $delete_url)->toRenderable(),
      ],
    ];

    return $row;
  }
}
