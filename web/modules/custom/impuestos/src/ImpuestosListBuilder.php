<?php

namespace Drupal\impuestos;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Provides a list controller for the Impuestos entity.
 */
class ImpuestosListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['nombre'] = $this->t('Nombre');
    $header['valor'] = $this->t('Valor');
    $header['acciones'] = $this->t('Acciones');
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
    $row['valor'] = $entity->get('valor')->value . '%';

    // Definir enlaces de edición y eliminación
    $edit_url = Url::fromRoute('impuestos.edit_form', ['impuestos' => $entity->id()]);
    $delete_url = Url::fromRoute('impuestos.delete_form', ['impuestos' => $entity->id()]);
    $row['valor'] = $entity->get('valor')->value . '%';

    // Definir enlaces de edición y eliminación
    $edit_url = Url::fromRoute('impuestos.edit_form', ['impuestos' => $entity->id()]);
    $delete_url = Url::fromRoute('impuestos.delete_form', ['impuestos' => $entity->id()]);

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
    // Botón "Agregar Impuesto"
    $build['add_button'] = [
      '#type' => 'link',
      '#title' => $this->t('Agregar Impuesto'),
      '#url' => Url::fromRoute('impuestos.add_form'),
      '#attributes' => [
        'class' => ['button', 'button--primary'],
        'style' => 'margin-bottom: 10px; display: inline-block;',
      ],
    ];

    // Agregar la lista de impuestos
    $build += parent::render();
    
    return $build;
  }
}