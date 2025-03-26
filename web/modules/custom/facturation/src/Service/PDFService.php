<?php

namespace Drupal\facturation\Service;

use FPDF;
use Drupal\facturation\Entity\Facturas;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;

class PDFService {

  /**
   * El servicio de manejo de archivos.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * El servicio de manejo de rutas del sistema de archivos.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * PDFService constructor.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   */
  public function __construct(FileSystemInterface $file_system, StreamWrapperManagerInterface $stream_wrapper_manager) {
    $this->fileSystem = $file_system;
    $this->streamWrapperManager = $stream_wrapper_manager;
  }

  /**
   * Genera el PDF de la factura y lo guarda en la carpeta específica.
   *
   * @param \Drupal\facturation\Entity\Facturas $factura
   *   La factura de la que se genera el PDF.
   *
   * @return string
   *   El contenido del PDF o la ruta del archivo guardado.
   */
  public function generarFacturaPDF(Facturas $factura) {
    // Usar FPDF para generar el PDF
    $pdf = new FPDF();
    $pdf->AddPage();

    // **Encabezado**
    $pdf->SetFont('Arial', 'B', 18);
    $pdf->Cell(190, 10, iconv('UTF-8', 'ISO-8859-1', 'Factura' . ' - Pedido N° ' . $factura->get('num_pedido')->value), 0, 1, 'C');
    $pdf->Ln(5);
    
    // Obtener datos del usuario
    $user = $factura->get('user_id')->entity;
    $nombre_usuario = iconv('UTF-8', 'ISO-8859-1', $user->getDisplayName());
    $email_usuario = iconv('UTF-8', 'ISO-8859-1', $user->getEmail());
    $dni_usuario = iconv('UTF-8', 'ISO-8859-1', $user->get('field_dni')->value ?? 'N/A');

    // **Datos del Cliente**
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(100, 8, iconv('UTF-8', 'ISO-8859-1', 'Datos del Cliente:'), 0, 1);
    
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(100, 6, iconv('UTF-8', 'ISO-8859-1', 'Nombre: ') . $nombre_usuario, 0, 1);
    $pdf->Cell(100, 6, iconv('UTF-8', 'ISO-8859-1', 'Email: ') . $email_usuario, 0, 1);
    $pdf->Cell(100, 6, iconv('UTF-8', 'ISO-8859-1', 'DNI: ') . $dni_usuario, 0, 1);
    $pdf->Ln(5);

    // Obtener detalles de la factura
    $num_pedido = iconv('UTF-8', 'ISO-8859-1', $factura->get('num_pedido')->value);
    $fecha_creacion = date('d/m/Y', strtotime($factura->get('fecha_creacion')->value));
    $fecha_vencimiento = date('d/m/Y', strtotime($factura->get('fecha_vencimiento')->value));

    // **Fechas**
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(100, 8, 'Detalles de la Factura:', 0, 1);

    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(100, 6, 'Fecha de Creacion: ' . $fecha_creacion, 0, 1);
    $pdf->Cell(100, 6, 'Fecha de Vencimiento: ' . $fecha_vencimiento, 0, 1);
    $pdf->Ln(8);

    // Obtener productos de la factura
    $factura_productos = \Drupal::entityTypeManager()
        ->getStorage('factura_producto')
        ->loadByProperties(['factura_id' => $factura->id()]);

    // **Encabezado Tabla**
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(200, 200, 200);
    $pdf->Cell(65, 8, 'Producto', 1, 0, 'C', true);
    $pdf->Cell(28, 8, 'Cantidad', 1, 0, 'C', true);
    $pdf->Cell(34, 8, 'Precio (' . chr(128) . ')', 1, 0, 'C', true);
    $pdf->Cell(28, 8, 'Impuesto (%)', 1, 0, 'C', true);
    $pdf->Cell(35, 8, 'Importe (' . chr(128) . ')', 1, 1, 'C', true);

    $pdf->SetFont('Arial', '', 10);
    $total_importe = 0;
    $total_impuesto = 0;

    foreach ($factura_productos as $factura_producto) {
        $producto = $factura_producto->get('producto_id')->entity;
        $nombre_producto = iconv('UTF-8', 'ISO-8859-1', $producto->get('nombre')->value);
        $cantidad = $factura_producto->get('cantidad')->value;
        $precio = $this->getProductoPrecio($producto->id());
        $impuesto = $this->getProductoImpuesto($producto->id());
        $importe = $precio * $cantidad;
        $impuesto_total = ($importe * $impuesto) / 100;

        // Filas de la tabla
        $pdf->Cell(65, 8, $nombre_producto, 1);
        $pdf->Cell(28, 8, $cantidad, 1, 0, 'C');
        $pdf->Cell(34, 8, number_format($precio, 2), 1, 0, 'C');
        $pdf->Cell(28, 8, $impuesto . '%', 1, 0, 'C');
        $pdf->Cell(35, 8, number_format($importe, 2), 1, 1, 'C');

        $total_importe += $importe;
        $total_impuesto += $impuesto_total;
    }

    $total_final = $total_importe + $total_impuesto;

    // **Totales**
    $pdf->Ln(5);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetFillColor(230, 230, 230);
    
    $pdf->Cell(120, 8, '', 0);
    $pdf->Cell(40, 8, 'Total Importe:', 1, 0, 'R', true);
    $pdf->Cell(30, 8, number_format($total_importe, 2) . ' ' . chr(128), 1, 1, 'C');

    $pdf->Cell(120, 8, '', 0);
    $pdf->Cell(40, 8, 'Total Impuesto:', 1, 0, 'R', true);
    $pdf->Cell(30, 8, number_format($total_impuesto, 2) . ' ' . chr(128), 1, 1, 'C');

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetFillColor(180, 220, 180);
    $pdf->Cell(120, 10, '', 0);
    $pdf->Cell(40, 10, 'Total Final:', 1, 0, 'R', true);
    $pdf->Cell(30, 10, number_format($total_final, 2) . ' ' . chr(128), 1, 1, 'C');

    // **Ruta del directorio donde se va a guardar el PDF**
    $module_path = \Drupal::service('extension.list.module')->getPath('facturation');
    $pdf_folder = $module_path . '/pdf'; 

    // Crear la carpeta si no existe
    if (!file_exists($pdf_folder)) {
      mkdir($pdf_folder, 0777, true);
    }

    // Nombre del archivo.
    $file_name = 'factura_' . $factura->id() . '.pdf';

    // Ruta completa para guardar el archivo.
    $file_path = $pdf_folder . '/' . $file_name;
    
    // Guardar el PDF en la ruta definida
    $pdf->Output('F', $file_path);

    return $file_path;
  }
  
  // Métodos auxiliares para obtener el precio y el impuesto de los productos.
  private function getProductoPrecio($producto_id) {
    // Lógica para obtener el precio del producto
    return 10.00; 
  }

  private function getProductoImpuesto($producto_id) {
    // Lógica para obtener el impuesto del producto
    return 21; 
  }
}
