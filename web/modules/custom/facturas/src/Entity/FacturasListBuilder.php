<?php

namespace Drupal\facturas\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Provides a list controller for the Facturas entity.
 */
class FacturasListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['num_factura'] = $this->t('Número de Factura');
    $header['num_pedido'] = $this->t('Número de Pedido');
    $header['fecha_creacion'] = $this->t('Fecha de Creación');
    $header['fecha_vencimiento'] = $this->t('Fecha de Vencimiento');
    $header['usuario'] = $this->t('Usuario');
    $header['total_precio'] = $this->t('Total Precio');
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

    // Construir la fila con los datos de la entidad
    $row['id'] = $entity->id();
    $row['num_factura'] = $entity->label();
    $row['num_pedido'] = $entity->get('num_pedido')->value;
    $row['fecha_creacion'] = \Drupal::service('date.formatter')->format($entity->get('fecha_creacion')->value, 'custom', 'd/m/Y');
    $row['fecha_vencimiento'] = \Drupal::service('date.formatter')->format($entity->get('fecha_vencimiento')->value, 'custom', 'd/m/Y');
    $row['usuario'] = $entity->get('id_user')->entity ? $entity->get('id_user')->entity->label() : $this->t('N/A');
    $row['total_precio'] = $entity->get('total_precio')->value . ' ' . $this->t('USD');

    // Definir enlaces de edición y eliminación
    $edit_url = Url::fromRoute('facturas.edit_form', ['facturas' => $entity->get('num_factura')->value]);
    $delete_url = Url::fromRoute('facturas.delete_form', ['facturas' => $entity->get('num_factura')->value]);

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
    // Botón "Agregar Factura"
    $build['add_button'] = [
      '#type' => 'link',
      '#title' => $this->t('Agregar Factura'),
      '#url' => Url::fromRoute('facturas.add_form'),
      '#attributes' => [
        'class' => ['button', 'button--primary'],
        'style' => 'margin-bottom: 10px; display: inline-block;',
      ],
    ];

    // Agregar la lista de facturas
    $build += parent::render();

    return $build;
  }
}
