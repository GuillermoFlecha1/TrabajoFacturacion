<?php

namespace Drupal\facturation\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;

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

    $row = [];
    
    // ID de la factura.
    $row['id'] = $entity->id();

    // Obtener el valor numérico del estado (0, 1, 2, 3).
    $estado = (int) $entity->get('estado')->value;
    // Mapeo de estados.
    $estadoLabels = [
      0 => $this->t('Borrador'),
      1 => $this->t('Finalizado'),
      2 => $this->t('Rectificada'),
      3 => $this->t('Rectificativa'),
    ];

    // Obtener el número de pedido (almacenado como entero).
    $numero = $entity->get('num_pedido')->value;

    // Determinar prefijo según el estado:
    // - Para Finalizado (1) y Rectificada (2) se usa "BI"
    // - Para Rectificativa (3) se usa "BIV"
    // - Para Borrador (0) se mostrará "Número no disponible".
    if ($estado === 0) {
      $display_num = $this->t('Número no disponible');
    }
    else {
      if ($estado === 1 || $estado === 2) {
        $prefijo = 'BI';
      }
      elseif ($estado === 3) {
        $prefijo = 'BIV';
      }
      else {
        $prefijo = '';
      }
      $display_num = $prefijo . $numero;
    }

    // Construir el enlace para el número de pedido, excepto si es Borrador.
    if ($estado === 0) {
      $row['num_pedido'] = $this->t('Número no disponible');
    }
    else {
      // Asegúrate de pasar el parámetro correcto. Aquí suponemos que la ruta "entity.facturas.canonical"
      // espera el parámetro "num_pedido" para identificar la factura.
      $factura_url = Url::fromRoute('entity.facturas.canonical', ['num_pedido' => $numero]);
      $row['num_pedido'] = Link::fromTextAndUrl($display_num, $factura_url)->toString();
    }

    // Fechas de creación y vencimiento.
    $fecha_creacion = $entity->get('fecha_creacion')->date;
    $fecha_vencimiento = $entity->get('fecha_vencimiento')->date;
    $row['fecha_creacion'] = $fecha_creacion ? $fecha_creacion->format('Y-m-d') : $this->t('Fecha no disponible');
    $row['fecha_vencimiento'] = $fecha_vencimiento ? $fecha_vencimiento->format('Y-m-d') : $this->t('Fecha no disponible');

    // Mostrar el estado en formato de texto, usando el mapeo.
    $row['estado'] = isset($estadoLabels[$estado]) ? $estadoLabels[$estado] : $this->t('Desconocido');

    // Información del usuario.
    $usuario = $entity->get('user_id')->entity;
    $row['usuario'] = $usuario ? $usuario->toLink()->toString() : $this->t('No asignado');

    // Total final.
    $row['total_final'] = $entity->get('total_final')->value . '€';

    // Definir las acciones según el estado.
    // Si el estado es Rectificada (2) o Rectificativa (3): solo se muestra "Visualizar PDF".
    // Si es Finalizado (1): se muestran "Visualizar PDF" y "Rectificar".
    // Para otros estados se muestran "Editar" y "Eliminar".
    if ($estado === 2 || $estado === 3) {
      $pdf_url = Url::fromRoute('facturas.ver_pdf', ['facturas' => $entity->id()], ['attributes' => ['target' => '_blank']]);
      $row['acciones'] = [
        'data' => [
          Link::fromTextAndUrl($this->t('Visualizar PDF'), $pdf_url)->toRenderable(),
        ],
      ];
    }
    elseif ($estado === 1) {
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
