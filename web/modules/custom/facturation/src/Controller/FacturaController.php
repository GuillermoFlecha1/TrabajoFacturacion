<?php

namespace Drupal\facturation\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\user\Entity\User;

class FacturaController extends ControllerBase {

  /**
   * Muestra los detalles de la factura basada en el número de pedido.
   */
  public function facturaView($num_pedido) {
    // Cargar la factura utilizando el número de pedido.
    $factura = \Drupal::entityTypeManager()
      ->getStorage('facturas')
      ->loadByProperties(['num_pedido' => $num_pedido]);

    if (empty($factura)) {
      throw new NotFoundHttpException();
    }
    
    $factura = reset($factura);
    $factura_id = $factura->id();

    $num_factura = $factura->get('num_pedido')->value;
    $total_final = $factura->get('total_final')->value;
    $fecha_Creacion = $factura->get("fecha_creacion")->value;
    $fecha_Vencimiento = $factura->get("fecha_vencimiento")->value;
    $usuario_id = $factura->get("user_id")->target_id;
    $usuario = User::load($usuario_id);
    $nombre_usuario = $usuario ? $usuario->getDisplayName() : t('Usuario desconocido');
    $estado = $factura->get('estado')->value;

    // Renderizar la tabla de productos de la factura.
    $tabla_productos = $this->renderizarTablaProductosFactura($factura_id);

    return [
      'factura_detalles' => [
        '#theme' => 'item_list',
        '#title' => 'Detalles de la Factura',
        '#items' => [
          'Número de Pedido: ' . $num_factura,
          'Fecha de Creación: ' . $fecha_Creacion,
          'Fecha de Vencimiento: ' . $fecha_Vencimiento,
          'Usuario: ' . $nombre_usuario,
          'Estado: ' . $estado,
          'Total Final: ' . number_format($total_final, 2) . ' EUR',
        ],
      ],
      'factura_productos' => [
        '#markup' => $tabla_productos,
      ],
    ];
  }

  /**
   * Obtiene los productos de una factura específica usando el Entity API.
   */
  private function obtenerProductosDeFactura($factura_id) {
    $factura_productos = \Drupal::entityTypeManager()
      ->getStorage('factura_producto')
      ->loadByProperties(['factura_id' => $factura_id]);

    $productos = [];
    if (!empty($factura_productos)) {
      foreach ($factura_productos as $factura_producto) {
        $producto = $factura_producto->get('producto_id')->entity;
        if ($producto) {
          $productos[] = [
            'nombre' => $producto->get('nombre')->value,
            'cantidad' => $factura_producto->get('cantidad')->value,
            'precio' => number_format($producto->get('precio')->value, 2),
            'impuesto' => number_format($this->getProductoImpuesto($producto->id()), 2),
            'importe' => number_format($producto->get('precio')->value * $factura_producto->get('cantidad')->value, 2),
          ];
        }
      }
    }
    return $productos;
  }

  /**
   * Renderiza la tabla de productos de la factura.
   */
  private function renderizarTablaProductosFactura($factura_id) {
    $productos = $this->obtenerProductosDeFactura($factura_id);

    if (empty($productos)) {
      return '<p>No hay productos en esta factura.</p>';
    }

    $output = '<table border="1" cellpadding="5" cellspacing="0">';
    $output .= '<thead>
                  <tr>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Precio (€)</th>
                    <th>Impuesto (%)</th>
                    <th>Importe (€)</th>
                  </tr>
                </thead>';
    $output .= '<tbody>';
    
    $total_importe = 0;
    $total_impuesto = 0;

    foreach ($productos as $producto) {
      $importe_valor = floatval(str_replace(',', '', $producto['importe']));
      $impuesto_valor = floatval(str_replace(',', '', $producto['impuesto']));
      $impuesto_total = ($importe_valor * $impuesto_valor) / 100;
      $total_importe += $importe_valor;
      $total_impuesto += $impuesto_total;

      $output .= "<tr>
                    <td>{$producto['nombre']}</td>
                    <td>{$producto['cantidad']}</td>
                    <td>{$producto['precio']}</td>
                    <td>{$producto['impuesto']}%</td>
                    <td>{$producto['importe']}</td>
                  </tr>";
    }

    $total_final = $total_importe + $total_impuesto;

    $output .= "<tr>
                  <td colspan='4'><strong>Total Importe:</strong></td>
                  <td><strong>" . number_format($total_importe, 2) . " €</strong></td>
                </tr>
                <tr>
                  <td colspan='4'><strong>Total Impuesto:</strong></td>
                  <td><strong>" . number_format($total_impuesto, 2) . " €</strong></td>
                </tr>
                <tr>
                  <td colspan='4'><strong>Total Final:</strong></td>
                  <td><strong>" . number_format($total_final, 2) . " €</strong></td>
                </tr>";

    $output .= '</tbody></table>';
    return $output;
  }

  /**
   * Obtiene el precio del producto.
   */
  private function getProductoPrecio($producto_id) {
    $producto = \Drupal::entityTypeManager()->getStorage('producto')->load($producto_id);
    return $producto ? $producto->get('precio')->value : 0;
  }

  /**
   * Obtiene el impuesto del producto.
   */
  private function getProductoImpuesto($producto_id) {
    $producto = \Drupal::entityTypeManager()->getStorage('producto')->load($producto_id);
    if ($producto) {
      if ($producto->hasField('impuesto')) {
        return $producto->get('impuesto')->value;
      }
      elseif ($producto->hasField('impuesto_id')) {
        $impuesto_id = $producto->get('impuesto_id')->target_id;
        $impuesto = \Drupal::entityTypeManager()->getStorage('impuestos')->load($impuesto_id);
        return ($impuesto && $impuesto->hasField('valor')) ? $impuesto->get('valor')->value : 0;
      }
    }
    return 0;
  }
}
