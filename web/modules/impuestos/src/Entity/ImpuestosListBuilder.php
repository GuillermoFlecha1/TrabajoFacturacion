<?php

namespace Drupal\impuestos\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;  // Asegúrate de importar Link

/**
 * Provides a list controller for the Impuestos entity.
 */
class ImpuestosListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    // Definimos explícitamente las columnas del encabezado.
    $header['id'] = $this->t('ID');
    $header['nombre'] = $this->t('Nombre');
    $header['valor'] = $this->t('Valor');
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
    $row['nombre'] = $entity->label();
    $row['valor'] = $entity->get('valor')->value . '%'; // El campo 'valor' con el signo '%'
    
    // Agregar enlaces de acciones
    $add_url = \Drupal\Core\Url::fromRoute('impuestos.add_form', ['impuestos' => $entity->id()]);
    $edit_url = \Drupal\Core\Url::fromRoute('impuestos.edit_form', ['impuestos' => $entity->id()]);
    $delete_url = \Drupal\Core\Url::fromRoute('impuestos.delete_form', ['impuestos' => $entity->id()]);

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
