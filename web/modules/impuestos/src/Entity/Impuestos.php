<?php

namespace Drupal\impuestos\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Defines the Impuestos entity.
 *
 * @ContentEntityType(
 *   id = "impuestos",
 *   label = @Translation("Impuestos"),
 *   base_table = "impuestos",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "nombre"
 *   },
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\impuestos\ImpuestosListBuilder",
 *     "form" = {
 *       "default" = "Drupal\impuestos\Form\ImpuestosForm",
 *       "add" = "Drupal\impuestos\Form\ImpuestosForm",
 *       "edit" = "Drupal\impuestos\Form\ImpuestosForm",
 *       "delete" = "Drupal\impuestos\Form\ImpuestosDeleteForm"
 *     },
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler"
 *   },
 *   links = {
 *     "canonical" = "/impuestos/{impuestos}",
 *     "add-form" = "/impuestos/add",
 *     "edit-form" = "/impuestos/{impuestos}/edit",
 *     "delete-form" = "/impuestos/{impuestos}/delete",
 *   },
 *   field_ui_base_route = "impuestos.settings"
 * )
 */
class Impuestos extends ContentEntityBase {

  use EntityChangedTrait;
  
  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    // Empieza con los campos básicos de las entidades de contenido.
    $fields = parent::baseFieldDefinitions($entity_type);
  
    // Campo ID.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('El ID de la entidad Impuestos.'))
      ->setReadOnly(TRUE);
  
    // Campo UUID.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('El UUID de la entidad Impuestos.'))
      ->setReadOnly(TRUE);
  
    // Campo "nombre".
    $fields['nombre'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre'))
      ->setDescription(t('El nombre del impuesto.'))
      ->setSettings([
        'max_length' => 255,
      ])
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
  
    // Campo "valor" (para almacenar el porcentaje del IVA).
    $fields['valor'] = BaseFieldDefinition::create('integer') 
      ->setLabel(t('Valor'))
      ->setDescription(t('El porcentaje del IVA.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'number_integer', 
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',  
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
  
    // Campo para guardar la fecha de la última modificación.
    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Última modificación'))
      ->setDescription(t('La fecha de la última modificación del impuesto.'));
  
    return $fields;
  }
}