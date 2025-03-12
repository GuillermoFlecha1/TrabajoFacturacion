<?php

namespace Drupal\producto;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;

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
    $header['cantidad'] = $this->t('Cantidad');
    $header['impuesto'] = $this->t('Impuesto');
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
    $row['cantidad'] = $entity->get('cantidad')->value;
  
    // Obtener los valores brutos de impuesto_id (puede ser un array con un solo valor)
    $impuesto_values = $entity->get('impuesto_id')->getValue();
    
    if ($impuesto_values === NULL) {
      \Drupal::messenger()->addMessage($this->t('Valor de impuesto_id: NULL'));
    } elseif (empty($impuesto_values)) {
      \Drupal::messenger()->addMessage($this->t('Valor de impuesto_id: vacío'));
    } else {
      \Drupal::messenger()->addMessage($this->t('Valor de impuesto_id: @val', ['@val' => print_r($impuesto_values, TRUE)]));
    }
    // Si el array no está vacío y tiene al menos un valor
    if (!empty($impuesto_values)) {
      // Procesar los valores de impuesto_id
      foreach ($impuesto_values as $impuesto_value) {
        // Aquí, ya no usamos 'target_id', sino que el valor directo es el ID
        $impuesto_id = $impuesto_value; // El valor de impuesto_id es directamente el ID del impuesto.

        // Cargar la entidad de impuesto usando el ID
        $impuesto_entity = \Drupal::entityTypeManager()->getStorage('impuestos')->load($impuesto_id);
        
        if ($impuesto_entity) {
          // Si se encuentra la entidad, obtenemos el valor del impuesto
          $valor = $impuesto_entity->get('valor')->value;
          $row['impuesto'] = $valor;
        } else {
          // Si no se puede cargar la entidad de impuesto
          $row['impuesto'] = $this->t('No se pudo cargar la entidad de impuesto');
        }
      }
    } else {
      // Si no hay impuesto_id
      $row['impuesto'] = $this->t('No asignado');
    }
  
    // Enlaces de acciones...
    $add_url = Url::fromRoute('producto.add_form', ['producto' => $entity->id()]);
    $edit_url = Url::fromRoute('producto.edit_form', ['producto' => $entity->id()]);
    $delete_url = Url::fromRoute('producto.delete_form', ['producto' => $entity->id()]);
  
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

