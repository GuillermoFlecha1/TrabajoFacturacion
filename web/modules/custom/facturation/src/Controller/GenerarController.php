<?php

namespace Drupal\facturation\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\facturation\Entity\Facturas;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GenerarController extends ControllerBase {

  public function visualizarPDF($facturas) {
    // Cargar la factura por ID.
    $factura = Facturas::load($facturas);
    
    if (!$factura) {
        throw new NotFoundHttpException('Factura no encontrada.');
    }

    // Obtener el ID de la factura.
    $factura_id = $factura->id();

    // Ruta absoluta del archivo PDF basado en el ID de la factura.
    $pdf_path = DRUPAL_ROOT . "/modules/custom/facturation/pdf/factura_{$factura_id}.pdf";

    // Depurar la ruta del archivo en logs de Drupal.
    \Drupal::logger('facturation')->notice('Buscando PDF en: @path', ['@path' => $pdf_path]);

    // Verificar si el archivo existe.
    if (!file_exists($pdf_path)) {
        throw new FileNotFoundException("El PDF de la factura no se encuentra: {$pdf_path}");
    }

    // Leer el contenido del archivo.
    $pdf_content = file_get_contents($pdf_path);

    // Responder con el PDF en modo inline.
    $response = new Response($pdf_content);
    $response->headers->set('Content-Type', 'application/pdf');
    $response->headers->set('Content-Disposition', 'inline; filename="factura_'.$factura_id.'.pdf"');

    return $response;
  }
}