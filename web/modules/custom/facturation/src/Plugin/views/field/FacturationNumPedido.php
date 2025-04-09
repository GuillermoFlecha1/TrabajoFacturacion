<?php

namespace Drupal\facturation\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A handler to provide proper displays for facturation number.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("facturation_num_pedido")
 */
class FacturationNumPedido extends FieldPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a FacturationNumPedido object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $this->field_alias = $this->tableAlias . '.' . $this->realField;
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $factura_id = $values->id;
    
    // Cargar la entidad factura
    $factura = $this->entityTypeManager->getStorage('facturas')->load($factura_id);
    
    if (!$factura) {
      return $this->t('Número no disponible');
    }
    
    // Obtener estado y número de pedido
    $estado = $factura->get('estado')->value;
    $factura_numero = $factura->get('num_pedido')->value;
    
    // Si está en borrador, no mostramos un número
    if ($estado === 'Borrador') {
      return $this->t('Número no disponible');
    }
    
    // Aplicar prefijo según el estado
    if ($estado === 'Finalizado' || $estado === 'Rectificada') {
      $factura_numero = 'BI' . $factura_numero;
    } 
    elseif ($estado === 'Rectificativa') {
      $factura_numero = 'BIV' . $factura_numero;
    }
    
    // Construir el enlace
    $factura_url = Url::fromRoute('entity.facturas.canonical', ['num_pedido' => $factura_id]);
    return Link::fromTextAndUrl($factura_numero, $factura_url)->toString();
  }
}