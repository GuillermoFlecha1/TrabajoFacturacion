<?php

namespace Drupal\facturation\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\facturation\Entity\Facturas;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\facturation\Utils\PdfWithRotation;

class GenerarController extends ControllerBase {

  /**
   * Muestra el PDF de la factura.
   */
  public function visualizarPDF($facturas) {
    $factura = Facturas::load($facturas);
    if (!$factura) {
        throw new NotFoundHttpException('Factura no encontrada.');
    }

    $num_pedido = $factura->get('num_pedido')->value;
    $estado = $factura->get('estado')->value;
    if($estado === "3"){
      $pdf_path = dirname(DRUPAL_ROOT) . "/private/pdf/factura_BIV{$num_pedido}.pdf";
    }else{
      $pdf_path = dirname(DRUPAL_ROOT) . "/private/pdf/factura_BI{$num_pedido}.pdf";
    }
    

    if (!file_exists($pdf_path)) {
        throw new FileNotFoundException("El PDF no se encuentra: {$pdf_path}");
    }

    $pdf_content = file_get_contents($pdf_path);
    $response = new Response($pdf_content);
    $response->headers->set('Content-Type', 'application/pdf');
    if($estado === "3"){
      $response->headers->set('Content-Disposition', 'inline; filename="factura_BIV'."$num_pedido".'.pdf"');
    }else{
      $response->headers->set('Content-Disposition', 'inline; filename="factura_BI'."$num_pedido".'.pdf"');
    }
    
    return $response;
  }

  /**
   * Rectifica una factura.
   */
  public function rectificar(Facturas $facturas) {
    $num_pedido_original = $facturas->get('num_pedido')->value;

    $facturas->set('estado', '2');
    $facturas->save();

    // Crear la factura rectificativa (BIV)
    $factura_BIV = $facturas->createDuplicate();
    $query = \Drupal::database()->select('facturas', 'f')
        ->fields('f', ['num_pedido'])
        ->condition('estado', '3')
        ->orderBy('num_pedido', 'DESC')
        ->range(0, 1);

    $ultimo_numero = $query->execute()->fetchField();
    
    // Si no hay números previos, iniciar con 1
    $nuevo_numero = $ultimo_numero ? intval($ultimo_numero) + 1 : 1;

    // Asignamos el nuevo número
    $factura_BIV->set('num_pedido', $nuevo_numero);
    $factura_BIV->set('estado', '3');
    $factura_BIV->save();
    $nuevo_num_pedido_BIV = $factura_BIV->get('num_pedido')->value;
   

    // Crear la factura en estado "Borrador"
    $factura_borrador = $facturas->createDuplicate();
    $factura_borrador->set('num_pedido', NULL);
    $factura_borrador->set('estado', '0');
    $factura_borrador->set('total_final', 0);
    $factura_borrador->save();

    $this->generarPDFRectificativa($nuevo_num_pedido_BIV, $num_pedido_original);

    // Mensaje de éxito
    $this->messenger()->addStatus("Factura rectificada correctamente: \nBIA -> Rectificada, \nBIV -> Rectificativa, \nNueva factura en borrador creada.");
    
    return new RedirectResponse(Url::fromRoute('entity.facturas.collection')->toString());
  }

  private function generarPDFRectificativa($nuevo_num_pedido_BIV, $num_pedido_original) {
    $pdf_original = dirname(DRUPAL_ROOT) . "/private/pdf/factura_BI{$num_pedido_original}.pdf";
    $pdf_nuevo = dirname(DRUPAL_ROOT) . "/private/pdf/factura_BIV{$nuevo_num_pedido_BIV}.pdf";
  
    if (!file_exists($pdf_original)) {
        \Drupal::logger('facturation')->error("El PDF original no se encuentra: {$pdf_original}");
        return;
    }
  
    $pdf = new PdfWithRotation();
    $pdf->AddPage();
    $pdf->setSourceFile($pdf_original);
    $tplIdx = $pdf->importPage(1);
    $pdf->useTemplate($tplIdx, 0, 0);

    // "Limpiar" el área del título.
    $pdf->SetFillColor(255, 255, 255);
    $pdf->Rect(0, 10, 190, 10, 'F');

    // Reimprimir el encabezado con el nuevo número de pedido rectificativa.
    $pdf->SetFont('Arial', 'B', 18);
    $pdf->Cell(190, 10, iconv('UTF-8', 'ISO-8859-1', 'Factura N° BIV' . $nuevo_num_pedido_BIV), 0, 1, 'C');

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetXY(10, 250);
    $pdf->Cell(0, 10, "Factura Rectificativa de BI{$num_pedido_original}", 0, 1);

    $pdf->AddWatermark("RECTIFICATIVA");

    $pdf->Output($pdf_nuevo, 'F');
  }     
}