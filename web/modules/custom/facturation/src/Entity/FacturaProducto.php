<?php

namespace Drupal\facturation\Entity;

use Drupal\Core\Entity\EntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityBase;

/**
 * Defines the FacturaProducto entity.
 *
 * @ContentEntityType(
 *   id = "factura_producto",
 *   label = @Translation("Factura Producto"),
 *   base_table = "factura_productos",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "factura_id",
 *   },
 *   handlers = {
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\EntityForm",
 *     }
 *   }
 * )
 */
class FacturaProducto extends ContentEntityBase implements ContentEntityInterface {

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setReadOnly(TRUE);

      $fields['factura_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Factura'))
      ->setSetting('target_type', 'facturas')
      ->setRequired(TRUE)
      ->setCardinality(1)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
      ])
      ->setDisplayConfigurable('view', TRUE);
    // Producto.
    $fields['producto_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Producto'))
      ->setDescription(t('El producto asociado a la factura.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'producto')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
      ])
      ->setDisplayConfigurable('view', TRUE);

    // Cantidad.
    $fields['cantidad'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Cantidad'))
      ->setDescription(t('Cantidad de productos asociados a la factura.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'number',
      ])
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }
}
