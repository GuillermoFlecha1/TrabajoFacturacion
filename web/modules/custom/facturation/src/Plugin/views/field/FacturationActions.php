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
   * @var EntityTypeManagerInterface
   */
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
    // No hacemos nada en la consulta.
  }

  public function render(ResultRow $values) {
    $factura_id = $values->id;
    $factura = $this->entityTypeManager->getStorage('facturas')->load($factura_id);

    if (!$factura) {
      return [];
    }

    $estado = (int) $factura->get('estado')->value;
    $links = [];

    // Construcción de enlaces según estado.
    $pdf_url = Url::fromRoute('facturas.ver_pdf', ['facturas' => $factura_id], ['attributes' => ['target' => '_blank']]);
    if ($estado === 2 || $estado === 3) {
      $links[] = Link::fromTextAndUrl($this->t('Visualizar PDF'), $pdf_url)->toRenderable();
    }
    elseif ($estado === 1) {
      $links[] = Link::fromTextAndUrl($this->t('Visualizar PDF'), $pdf_url)->toRenderable();
      $links[] = Link::fromTextAndUrl($this->t('Rectificar'), Url::fromRoute('facturas.rectificar', ['facturas' => $factura_id]))->toRenderable();
    }
    else {
      $links[] = Link::fromTextAndUrl($this->t('Editar'), Url::fromRoute('facturas.edit_form', ['facturas' => $factura_id]))->toRenderable();
      $links[] = Link::fromTextAndUrl($this->t('Eliminar'), Url::fromRoute('facturas.delete_form', ['facturas' => $factura_id]))->toRenderable();
    }

    // Render inline en un <div> sin <ul>.
    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['facturation-actions']],
      'links' => $links,
    ];
  }
}