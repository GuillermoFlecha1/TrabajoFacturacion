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
    $this->field_alias = $this->tableAlias . '.' . $this->realField;
  }

  public function render(ResultRow $values) {
    $factura_id = $values->id;
    $factura = $this->entityTypeManager->getStorage('facturas')->load($factura_id);
    if (!$factura) {
      return $this->t('Número no disponible');
    }

    $estado = (int) $factura->get('estado')->value;
    $numero = $factura->get('num_pedido')->value;
    
    // Si está en borrador, no mostramos un número
    if ($estado === 0) {
        return $this->t('Número no disponible');
      }
  
      if ($estado === 1 || $estado === 2) {
        $prefijo = 'BI';
      } elseif ($estado === 3) {
        $prefijo = 'BIV';
      } else {
        $prefijo = '';
      }
  
      $display_num = $prefijo . $numero;
  
      $factura_url = Url::fromRoute('entity.facturas.canonical', ['num_pedido' => $numero]);
      return Link::fromTextAndUrl($display_num, $factura_url)->toString();
  }
}