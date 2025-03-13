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

    // Intentar obtener las entidades de Impuestos referenciadas.
    $impuestos = $entity->get('impuesto_id')->referencedEntities();

    // Si no se obtuvieron entidades, intentar cargar manualmente usando el valor del campo.
    if (empty($impuestos)) {
      $impuesto_id = $entity->get('impuesto_id')->value;
      if (!empty($impuesto_id)) {
        $impuesto = \Drupal::entityTypeManager()->getStorage('impuestos')->load($impuesto_id);
        if ($impuesto) {
          $impuestos[] = $impuesto;
        }
      }
    }

    // Si no hay impuestos, mostrar un mensaje adecuado
    if (empty($impuestos)) {
      $row['impuestos'] = $this->t('No hay impuestos asociados');
    } else {
      // Recoger los nombres de los impuestos asociados (campo 'nombre' en la entidad Impuestos)
      $impuesto_nombres = [];
      $valor_impuesto = '';
      foreach ($impuestos as $impuesto) {
        if ($impuesto->hasField('nombre')) {
          $nombre = $impuesto->get('nombre')->value;
          if (!empty($nombre)) {
            $impuesto_nombres[] = $nombre;
          }
        }
        // Obtener el valor del impuesto si existe
        if ($impuesto->hasField('valor')) {
          $valor_impuesto = $impuesto->get('valor')->value;
        }
      }

      // Mostrar el valor o el nombre del impuesto
      if (!empty($valor_impuesto)) {
        $row['impuestos'] = $valor_impuesto . '%'; 
      } else {
        $row['impuestos'] = !empty($impuesto_nombres) ? implode(', ', $impuesto_nombres) : $this->t('No hay impuestos asociados');
      }
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
