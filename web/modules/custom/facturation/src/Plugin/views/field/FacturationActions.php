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
 * A handler to provide proper displays for facturation actions.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("facturation_actions")
 */
class FacturationActions extends FieldPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a FacturationActions object.
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
    
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $factura_id = $values->id;
    
    // Cargar la entidad factura
    $factura = $this->entityTypeManager->getStorage('facturas')->load($factura_id);
    
    if (!$factura) {
      return [];
    }
    
    // Obtener el estado de la factura
    $estado = (int) $factura->get('estado')->value;
    
    // Enlaces para diferentes estados, igual que en FacturasListBuilder
    $links = [];
    
    // Si el estado es "Rectificada" o "Rectificativa", solo mostramos el botón de Visualizar PDF.
    if ($estado == 2 || $estado == 3) {
      $pdf_url = Url::fromRoute('facturas.ver_pdf', ['facturas' => $factura_id], ['attributes' => ['target' => '_blank']]);
      $links[] = Link::fromTextAndUrl($this->t('Visualizar PDF'), $pdf_url)->toRenderable();
    }
    // Si el estado es "Finalizado", se muestra Visualizar PDF y Rectificar.
    elseif ($estado == 1) {
      $pdf_url = Url::fromRoute('facturas.ver_pdf', ['facturas' => $factura_id], ['attributes' => ['target' => '_blank']]);
      $rectificar_url = Url::fromRoute('facturas.rectificar', ['facturas' => $factura_id]);
      
      $links[] = Link::fromTextAndUrl($this->t('Visualizar PDF'), $pdf_url)->toRenderable();
      $links[] = Link::fromTextAndUrl($this->t('Rectificar'), $rectificar_url)->toRenderable();
    } 
    // Para otros estados, se muestra Editar y Eliminar.
    else {
      $edit_url = Url::fromRoute('facturas.edit_form', ['facturas' => $factura_id]);
      $delete_url = Url::fromRoute('facturas.delete_form', ['facturas' => $factura_id]);
      
      $links[] = Link::fromTextAndUrl($this->t('Editar'), $edit_url)->toRenderable();
      $links[] = Link::fromTextAndUrl($this->t('Eliminar'), $delete_url)->toRenderable();
    }
    
    return [
      '#theme' => 'item_list',
      '#items' => $links,
      '#attributes' => ['class' => ['facturation-actions']],
    ];
  }
}