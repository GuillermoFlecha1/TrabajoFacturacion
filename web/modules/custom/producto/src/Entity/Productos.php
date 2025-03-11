<?php

namespace Drupal\producto\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Defines the Producto entity.
 *
 * @ContentEntityType(
 *   id = "producto",
 *   label = @Translation("Producto"),
 *   base_table = "producto",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "nombre"
 *   },
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\producto\ProductosListBuilder",
 *     "form" = {
 *       "default" = "Drupal\producto\Form\ProductoForm",
 *       "add" = "Drupal\producto\Form\ProductoForm",
 *       "edit" = "Drupal\producto\Form\ProductoForm",
 *       "delete" = "Drupal\producto\Form\ProductoDeleteForm"
 *     },
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler"
 *   },
 *   links = {
 *     "canonical" = "/producto/{producto}",
 *     "add-form" = "/producto/add",
 *     "edit-form" = "/producto/{producto}/edit",
 *     "delete-form" = "/producto/{producto}/delete",
 *   },
 *   field_ui_base_route = "producto.settings"
 * )
 */
class Productos extends ContentEntityBase {
  
  use EntityChangedTrait;

  /**
  * {@inheritdoc}
  */
 public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

     // Campo ID.
     $fields['id'] = BaseFieldDefinition::create('integer')
     ->setLabel(t('ID'))
     ->setDescription(t('El ID de la entidad productos.'))
     ->setReadOnly(TRUE);
 
     // Campo UUID.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('El UUID de la entidad productos.'))
      ->setReadOnly(TRUE);
      
    // Campo 'nombre'
    $fields['nombre'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre'))
      ->setDescription(t('El nombre del producto.'))
      ->setRequired(TRUE)
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 'precio'
    $fields['precio'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Precio'))
      ->setDescription(t('El precio del producto.'))
      ->setRequired(TRUE)
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'number_decimal',
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 'descripcion'
    $fields['descripcion'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Descripción'))
      ->setDescription(t('Descripción detallada del producto.'))
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => 2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 'cantidad'
    $fields['cantidad'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Cantidad'))
      ->setDescription(t('La cantidad disponible del producto.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'number_integer',
        'weight' => 3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }
}