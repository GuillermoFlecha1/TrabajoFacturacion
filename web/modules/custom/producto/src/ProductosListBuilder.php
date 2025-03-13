<?php

namespace Drupal\producto;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;

class ProductosListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['nombre'] = $this->t('Nombre');
    $header['precio'] = $this->t('Precio');
    $header['impuestos'] = $this->t('Impuesto');
    $header['acciones'] = $this->t('Acciones');
    return $header;
  }

    /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    if (!$entity) {
      return;
    }

    $row['id'] = $entity->id();
    $row['nombre'] = $entity->toLink($entity->label());
    $row['precio'] = $entity->get('precio')->value;

    // Obtener el ID del impuesto asociado
    $impuesto_id = $entity->get('impuesto_id')->target_id;

    if (!empty($impuesto_id)) {
      // Cargar la entidad de impuestos
      $impuesto = \Drupal::entityTypeManager()->getStorage('impuestos')->load($impuesto_id);

      if ($impuesto && $impuesto->hasField('valor')) {
        $valor_impuesto = $impuesto->get('valor')->value;
        $row['impuestos'] = $valor_impuesto . '%';
      } else {
        $row['impuestos'] = $this->t('No hay impuestos asociados');
      }
    } else {
      $row['impuestos'] = $this->t('No hay impuestos asociados');
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
