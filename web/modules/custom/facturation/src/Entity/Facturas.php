<?php 
namespace Drupal\facturation\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Defines the Facturas entity.
 *
 * @ContentEntityType(
 *   id = "facturas",
 *   label = @Translation("Factura"),
 *   base_table = "facturas",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "num_pedido"
 *   },
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\facturation\FacturasListBuilder",
 *     "form" = {
 *       "default" = "Drupal\facturation\Form\FacturaForm",
 *       "add" = "Drupal\facturation\Form\FacturaForm",
 *       "edit" = "Drupal\facturation\Form\FacturaForm",
 *       "delete" = "Drupal\facturation\Form\FacturaDeleteForm"
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

    // NumFactura: Campo ID autoincremental.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('NumFactura'))
      ->setDescription(t('Número de factura (ID autoincremental).'))
      ->setReadOnly(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0, // Orden en la vista
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 0, // Orden en el formulario
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    // UUID (No se muestra en el formulario, pero sigue presente en la base de datos).
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('El UUID de la factura.'))
      ->setReadOnly(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'hidden',
        'weight' => -1, // No se muestra en el formulario
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -1, // No se muestra en la vista
      ]);
      
    // Fecha de Creación.
    $fields['fecha_creacion'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de Creación'))
      ->setDescription(t('La fecha en que se creó la factura.'))
      ->setSetting('datetime_type', 'date')
      ->setRequired(TRUE)
      ->setDefaultValueCallback('Drupal\\facturation\\Entity\\Facturas::getCurrentDate')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'datetime_default',
        'weight' => 2, // Orden en la vista
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime',
        'weight' => 2, // Orden en el formulario
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Número de Pedido (único, generado aleatoriamente).
    $fields['num_pedido'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Número de Pedido'))
      ->setDescription(t('Número de pedido único generado aleatoriamente.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'number',
        'weight' => 1, // Orden en la vista
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 1, // Orden en el formulario
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    //Fecha de Vencimiento.
    $fields['fecha_vencimiento'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de Vencimiento'))
      ->setDescription(t('La fecha de vencimiento de la factura.'))
      ->setRequired(TRUE)
      ->setDefaultValueCallback('Drupal\\facturation\\Entity\\Facturas::defaultFechaVencimiento')
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'datetime_default',
        'weight' => 3, // Orden en la vista
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime',
        'weight' => 3, // Orden en el formulario
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Usuario.
    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Usuario'))
      ->setDescription(t('El usuario asociado a la factura.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => 4, // Orden en la vista
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 4, // Orden en el formulario
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => t('Seleccione un usuario'),
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
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
        'weight' => 5, // Orden en la vista
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5, // Orden en el formulario
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => t('Seleccione un producto'),
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Cantidad.
    $fields['cantidad'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Cantidad'))
      ->setDescription(t('Cantidad de productos asociados a la factura.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'number',
        'weight' => 6, // Orden en la vista
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 6, // Orden en el formulario
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Total Final.
    $fields['total_final'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Total Final'))
      ->setDescription(t('El total final de la factura.'))
      ->setRequired(TRUE)
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'number_decimal',
        'weight' => 7, // Orden en la vista
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 7, // Orden en el formulario
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }
  /**
 * Callback para definir el valor predeterminado de fecha de vencimiento.
 *
 * @return array
 *   Valor predeterminado para el campo.
 */
public static function defaultFechaVencimiento() {
  $today = new \DateTime();
  $today->modify('+4 years'); // Incrementar 4 años desde hoy.
  return $today->format('Y-m-d');
}
public static function getCurrentDate() {
  return date('Y-m-d');
}

}
