<?php

namespace Drupal\facturation\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Link;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A handler to provide proper displays for user associated with a factura.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("facturation_user_field")
 */
class FacturationUserField extends FieldPluginBase implements ContainerFactoryPluginInterface {

  protected $entityTypeManager;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  public function query() {
    $this->ensureMyTable();
    $this->field_alias = $this->tableAlias . '.user_id';
  }

  public function render(ResultRow $values) {
    $factura_id = $values->id;
    $factura = $this->entityTypeManager->getStorage('facturas')->load($factura_id);
  
    if (!$factura) {
      return $this->t('Factura no encontrada (ID: @id)', ['@id' => $factura_id]);
    }
  
    if (!$factura->hasField('user_id')) {
      return $this->t('Campo user_id no disponible en factura (ID: @id)', ['@id' => $factura_id]);
    }
  
    $usuario = $factura->get('user_id')->entity;
  
    if (!$usuario) {
      return $this->t('Sin usuario asignado');
    }
  
    return $usuario->toLink()->toString(); 
  }
}