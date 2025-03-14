<?php

namespace Drupal\facturas\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Defines the Factura entity.
 *
 * @ContentEntityType(
 *   id = "facturas",
 *   label = @Translation("Facturas"),
 *   base_table = "facturas",
 *   entity_keys = {
 *     "id" = "num_factura",
 *     "uuid" = "uuid",
 *     "label" = "num_factura"
 *   },
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\facturas\Entity\FacturasListBuilder",
 *     "form" = {
 *       "default" = "Drupal\facturas\Form\FacturasForm",
 *       "add" = "Drupal\facturas\Form\FacturasForm",
 *       "edit" = "Drupal\facturas\Form\FacturasForm",
 *       "delete" = "Drupal\facturas\Form\FacturasDeleteForm"
 *     },
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler"
 *   },
 *   links = {
 *     "canonical" = "/admin/facturas/{facturas}",
 *     "add-form" = "/admin/facturas/add",
 *     "edit-form" = "/admin/facturas/{facturas}/edit",
 *     "delete-form" = "/admin/facturas/{facturas}/delete",
 *     "collection" = "/admin/facturas"
 *   },
 *   field_ui_base_route = "facturas.settings"
 * )
 */
class Facturas extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    // Campo UUID
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('El UUID de la entidad Factura.'))
      ->setReadOnly(TRUE);

    // Número de factura
    $fields['num_factura'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Número de Factura'))
      ->setDescription(t('Número de identificación de la factura.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'number_integer',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    // Número de pedido
    $fields['num_pedido'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Número de Pedido'))
      ->setDescription(t('Número del pedido relacionado.'))
      ->setSettings(['max_length' => 50])
      ->setDefaultValueCallback('Drupal\facturas\Entity\Facturas::generateRandomOrderNumber')
      ->setRequired(TRUE)
      ->setDisplayOptions('view', ['label' => 'above', 'type' => 'string', 'weight' => -4])
      ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => -4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Fecha de creación
    $fields['fecha_creacion'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de Creación'))
      ->setDescription(t('Fecha en que se generó la factura.'))
      ->setDisplayOptions('view', ['label' => 'above', 'type' => 'timestamp_default', 'weight' => -3])
      ->setDisplayOptions('form', ['type' => 'timestamp', 'weight' => -3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Fecha de vencimiento
    $fields['fecha_vencimiento'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de Vencimiento'))
      ->setDescription(t('Fecha límite de pago de la factura.'))
      ->setSetting('datetime_type', 'date')
      ->setRequired(TRUE)
      ->setDisplayOptions('view', ['label' => 'above', 'type' => 'date_default', 'weight' => -2])
      ->setDisplayOptions('form', ['type' => 'date', 'weight' => -2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Usuario relacionado
    $fields['id_user'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Usuario'))
      ->setDescription(t('Usuario relacionado con la factura.'))
      ->setSetting('target_type', 'user')
      ->setRequired(TRUE)
      ->setDisplayOptions('view', ['label' => 'above', 'type' => 'entity_reference', 'weight' => -2])
      ->setDisplayOptions('form', ['type' => 'entity_reference_autocomplete', 'weight' => -2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Producto relacionado
    $fields['id_producto'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Producto'))
      ->setDescription(t('Producto relacionado con la factura.'))
      ->setSetting('target_type', 'producto')
      ->setRequired(TRUE)
      ->setDisplayOptions('view', ['label' => 'above', 'type' => 'entity_reference', 'weight' => -1])
      ->setDisplayOptions('form', ['type' => 'entity_reference_autocomplete', 'weight' => -1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Cantidad de productos
    $fields['cantidad'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Cantidad'))
      ->setDescription(t('Cantidad de productos en la factura.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', ['label' => 'above', 'type' => 'number_integer', 'weight' => 0])
      ->setDisplayOptions('form', ['type' => 'number', 'weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Precio total
    $fields['total_precio'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Total Precio'))
      ->setDescription(t('Monto total de la factura.'))
      ->setSettings(['precision' => 10, 'scale' => 2])
      ->setRequired(TRUE)
      ->setDisplayOptions('view', ['label' => 'above', 'type' => 'number_decimal', 'weight' => 1])
      ->setDisplayOptions('form', ['type' => 'number', 'weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }
  /**
   * Genera un número de pedido aleatorio.
   */
  public static function generateRandomOrderNumber() {
    return (string) rand(10000, 99999);
  }
}