<?php

namespace Drupal\producto;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Contiene una lista para visualizar y gestionar Productos.
 */
class ProductosListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['nombre'] = $this->t('Nombre');
    $header['precio'] = $this->t('Precio');
    $header['impuesto'] = $this->t('Impuesto');
    $header['acciones'] = $this->t('Acciones');
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  /**
 * {@inheritdoc}
 */
public function buildRow(EntityInterface $entity) {
  $row['id'] = $entity->id();
  $row['nombre'] = $entity->toLink($entity->label());
  $row['precio'] = $entity->get('precio')->value;

  // Obtener el impuesto relacionado.
  $impuesto_id = $entity->get('impuesto_id')->target_id;
  if ($impuesto_id) {
    $impuesto_entity = \Drupal::entityTypeManager()->getStorage('impuestos')->load($impuesto_id);
    if ($impuesto_entity) {
      $impuesto_nombre = $impuesto_entity->label();
      $impuesto_valor = $impuesto_entity->get('valor')->value;
      /*$row['impuesto'] = $this->t('@nombre (@valor%)', [
        '@nombre' => $impuesto_nombre,
        '@valor' => $impuesto_valor,
      ]);*/
      $row['impuesto'] = $this->t('@valor%', [
        '@valor' => $impuesto_valor
      ]);
    } else {
      $row['impuesto'] = $this->t('No asignado');
    }
  } else {
    $row['impuesto'] = $this->t('No asignado');
  }

  // Enlaces de acciones.
  $edit_url = Url::fromRoute('producto.edit_form', ['producto' => $entity->id()]);
  $delete_url = Url::fromRoute('producto.delete_form', ['producto' => $entity->id()]);

  $row['acciones'] = [
    'data' => [
      Link::fromTextAndUrl($this->t('Editar'), $edit_url)->toRenderable(),
      ['#markup' => ' | '],
      Link::fromTextAndUrl($this->t('Eliminar'), $delete_url)->toRenderable(),
    ],
  ];

  return $row;
}


  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['add_button'] = [
      '#type' => 'link',
      '#title' => $this->t('Agregar Producto'),
      '#url' => Url::fromRoute('producto.add_form'),
      '#attributes' => [
        'class' => ['button', 'button--primary'],
        'style' => 'margin-bottom: 10px; display: inline-block;',
      ],
    ];

    $build += parent::render();
    return $build;
  }
}
