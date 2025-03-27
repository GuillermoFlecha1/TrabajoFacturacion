<?php

namespace Drupal\facturation\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Render\Markup;

class FacturasListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'id' => $this->t('ID'),
      'num_pedido' => $this->t('Número de Pedido'),
      'fecha_creacion' => $this->t('Fecha de Creación'),
      'fecha_vencimiento' => $this->t('Fecha de Vencimiento'),
      'estado' => $this->t('Estado'),
      'usuario' => $this->t('Usuario'),
      'total_final' => $this->t('Total Final'),
      'acciones' => $this->t('Acciones'),
    ];

    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    if (!$entity) {
        return;
    }

    // Define una fila para cada factura.
    $row = [];

    // ID de la factura.
    $row['id'] = $entity->id();

    // Estado de la factura.
    $estado = $entity->get('estado')->value;
    
    // Número de pedido (se oculta si el estado es "Borrador").
    if ($estado === 'Borrador') {
      $row['num_pedido'] = $this->t('Número no disponible');
    } else {
      $factura_numero = $entity->get('num_pedido')->value;
      $factura_url = Url::fromRoute('entity.facturas.canonical', ['num_pedido' => $factura_numero]);
      $row['num_pedido'] = Link::fromTextAndUrl($factura_numero, $factura_url)->toString();
    }

    // Fechas de creación y vencimiento.
    $fecha_creacion = $entity->get('fecha_creacion')->date;
    $fecha_vencimiento = $entity->get('fecha_vencimiento')->date;

    // Muestra solo la fecha (año-mes-día) sin la hora.
    $row['fecha_creacion'] = $fecha_creacion ? $fecha_creacion->format('d-m-Y') : $this->t('Fecha no disponible');
    $row['fecha_vencimiento'] = $fecha_vencimiento ? $fecha_vencimiento->format('d-m-Y') : $this->t('Fecha no disponible');

    // Estado de la factura.
    $row['estado'] = $estado ? $this->t($estado) : $this->t('Desconocido');

    // Obtener la información del usuario asociado.
    $usuario = $entity->get('user_id')->entity;
    $row['usuario'] = $usuario ? $usuario->toLink()->toString() : $this->t('No asignado');

    // Mostrar el total final.
    $row['total_final'] = $entity->get('total_final')->value . '€';

    // Si el estado es "Rectificada" o "Rectificativa", solo mostramos el botón de Visualizar PDF.
    if ($estado == 'Rectificada' || $estado == 'Rectificativa') {
      $pdf_url = Url::fromRoute('facturas.ver_pdf', ['facturas' => $entity->id()], ['attributes' => ['target' => '_blank']]);
      $row['acciones'] = [
          'data' => [
              Link::fromTextAndUrl($this->t('Visualizar PDF'), $pdf_url)->toRenderable(),
          ],
      ];
    }
    // Si el estado es "Finalizado", se muestra Visualizar PDF y Rectificar.
    elseif ($estado == 'Finalizado') {
      $pdf_url = Url::fromRoute('facturas.ver_pdf', ['facturas' => $entity->id()], ['attributes' => ['target' => '_blank']]);
      $rectificar_url = Url::fromRoute('facturas.rectificar', ['facturas' => $entity->id()]);

      $row['acciones'] = [
          'data' => [
              Link::fromTextAndUrl($this->t('Visualizar PDF'), $pdf_url)->toRenderable(),
              ['#markup' => ' | '],
              Link::fromTextAndUrl($this->t('Rectificar'), $rectificar_url)->toRenderable(),
          ],
      ];
    } 
    // Para otros estados, se muestra Editar y Eliminar.
    else {
      $edit_url = Url::fromRoute('facturas.edit_form', ['facturas' => $entity->id()]);
      $delete_url = Url::fromRoute('facturas.delete_form', ['facturas' => $entity->id()]);

      $row['acciones'] = [
          'data' => [
              Link::fromTextAndUrl($this->t('Editar'), $edit_url)->toRenderable(),
              ['#markup' => ' | '],
              Link::fromTextAndUrl($this->t('Eliminar'), $delete_url)->toRenderable(),
          ],
      ];
    }

    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    // Agregar el botón para agregar una nueva factura.
    $build['add_button'] = [
      '#type' => 'link',
      '#title' => $this->t('Agregar Factura'),
      '#url' => Url::fromRoute('facturas.add_form'),
      '#attributes' => [
        'class' => ['button', 'button--primary'],
        'style' => 'margin-bottom: 10px; display: inline-block;',
      ],
    ];

    // Llama al render de la clase base y agrega el botón de 'Agregar Factura'.
    $build += parent::render();
    return $build;
  }
}