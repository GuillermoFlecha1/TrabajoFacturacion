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
   *
   * @param int $facturas
   *   El ID de la factura.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Respuesta con el contenido del PDF.
   */
  public function visualizarPDF($facturas) {
    // Cargar la factura por ID.
    $factura = Facturas::load($facturas);
    if (!$factura) {
        throw new NotFoundHttpException('Factura no encontrada.');
    }

    $factura_id = $factura->id();
    $num_pedido = $factura->get('num_pedido')->value;
    $pdf_path = dirname(DRUPAL_ROOT) . "/private/pdf/factura_{$num_pedido}.pdf";

    // Verificar si la factura es "Rectificativa" y obtener la factura original.
    if ($factura->get('estado')->value === 'Rectificativa') {
        $original_num = $factura->get('num_pedido')->value - 1;
        $facturas_originales = \Drupal::entityTypeManager()
            ->getStorage('facturas')
            ->loadByProperties([
                'estado' => 'Rectificada',
                'num_pedido' => $original_num,
            ]);
        $factura_original = !empty($facturas_originales) ? reset($facturas_originales) : NULL;

        // Si encontramos la factura original, usamos su ID para el PDF base.
        if ($factura_original) {
            $original_num_pedido = $factura_original->get('num_pedido')->value;
            $pdf_path = dirname(DRUPAL_ROOT) . "/private/pdf/factura_{$original_num_pedido}.pdf";
        } else {
            throw new NotFoundHttpException('Factura original no encontrada.');
        }
    }

    // Verificar si el PDF base existe antes de proceder.
    if (!file_exists($pdf_path)) {
        throw new FileNotFoundException("El PDF base de la factura no se encuentra: {$pdf_path}");
    }

    // Crear la instancia del PDF.
    $pdf = new PdfWithRotation();
    $pdf->AddPage();
    $pdf->setSourceFile($pdf_path);
    $tplIdx = $pdf->importPage(1);
    $pdf->useTemplate($tplIdx, 0, 0);

    // Verifica si el estado de la factura es 'Rectificada'
    if ($factura->get('estado')->value === 'Rectificada') {
        // Aquí aplicas la marca de agua
        $watermarkText = 'RECTIFICADA';
        $pdf->AddWatermark($watermarkText); // Este método debe encargarse de poner la marca en la página
    }
    // Verifica si el estado de la factura es 'Rectificativa'
    elseif ($factura->get('estado')->value === 'Rectificativa') {
        // Agregar texto específico de una factura rectificativa
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY(10, 250);
        $num_pedido_original = isset($factura_original) ? $factura_original->get('num_pedido')->value : 'N/A';
        $pdf->Cell(0, 10, "Factura Rectificativa de {$num_pedido_original}", 0, 1);
    }

    // Guardar el nuevo PDF con la marca de agua o el texto adicional
    if ($factura->get('estado')->value === 'Rectificada') {
        $pdf_temp_path = dirname(DRUPAL_ROOT) . "/private/pdf/factura_{$num_pedido}_rectificada.pdf";
        $pdf->Output($pdf_temp_path, 'F');
        $pdf_content = file_get_contents($pdf_temp_path);
    }
    elseif ($factura->get('estado')->value === 'Rectificativa') {
        $pdf_new_path = dirname(DRUPAL_ROOT) . "/private/pdf/factura_{$num_pedido}_rectificativa.pdf";
        $pdf->Output($pdf_new_path, 'F');
        $pdf_content = file_get_contents($pdf_new_path);
    } else {
        // Para otros estados, devolver el PDF base sin cambios.
        $pdf_content = file_get_contents($pdf_path);
    }

    // Devolver el PDF en la respuesta HTTP.
    $response = new Response($pdf_content);
    $response->headers->set('Content-Type', 'application/pdf');
    $response->headers->set('Content-Disposition', 'inline; filename="factura_' . $num_pedido . '.pdf"');
    return $response;
  }  

  /**
   * Método para rectificar una factura.
   *
   * Realiza la duplicación de la factura, actualizando el estado de la
   * factura original a "Rectificada" y la copia a "Rectificativa", asignándole
   * un nuevo número de pedido.
   *
   * @param \Drupal\facturation\Entity\Facturas $facturas
   *   La factura a rectificar, inyectada a partir de la ruta.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirección a la lista de facturas.
   */
  public function rectificar(Facturas $facturas) {
    // Actualiza el estado de la factura original a "Rectificada".
    $facturas->set('estado', 'Rectificada');
    $facturas->save();
  
    // Clona la factura original para crear la nueva con estado "Rectificativa".
    $factura_rectificativa = $facturas->createDuplicate();
    
    // Generar el nuevo número de pedido basado en la factura original.
    $nuevo_num_pedido = $facturas->get('num_pedido')->value + 1;
    $factura_rectificativa->set('num_pedido', $nuevo_num_pedido);
    
    // Asignar el estado "Rectificativa".
    $factura_rectificativa->set('estado', 'Rectificativa');
    
    // Guarda la factura "Rectificativa".
    $factura_rectificativa->save();
    
    // Obtener los productos asociados y duplicarlos en la factura rectificativa.
    $this->duplicarProductos($facturas->id(), $factura_rectificativa->id());
  
    // Segunda duplicación: Crear una copia de la factura rectificativa con estado "Borrador".
    $factura_borrador = $factura_rectificativa->createDuplicate();
    
    // Mantener el mismo número de pedido de la rectificativa.
    $factura_borrador->set('num_pedido', $nuevo_num_pedido);
    
    // Asignar el estado "Borrador".
    $factura_borrador->set('estado', 'Borrador');
    
    // Guarda la factura en estado "Borrador".
    $factura_borrador->save();
    
    // Duplicar los productos en la factura "Borrador".
    $this->duplicarProductos($factura_rectificativa->id(), $factura_borrador->id());
  
    $this->messenger()->addStatus($this->t('Se ha creado una factura rectificativa con el número de pedido @num y una copia en borrador.', [
      '@num' => $nuevo_num_pedido
    ]));
  
    // Redirige a la lista de facturas.
    return new RedirectResponse(Url::fromRoute('entity.facturas.collection')->toString());
  }
  
  /**
   * Duplica los productos de una factura original y los asigna a la nueva factura.
   */
  private function duplicarProductos($factura_original_id, $nueva_factura_id) {
    // Cargar los productos de la factura original.
    $factura_productos = \Drupal::entityTypeManager()
      ->getStorage('factura_producto')
      ->loadByProperties(['factura_id' => $factura_original_id]);
  
    foreach ($factura_productos as $factura_producto) {
      // Crear una nueva entidad factura_producto duplicando la original.
      $nuevo_producto = $factura_producto->createDuplicate();
      $nuevo_producto->set('factura_id', $nueva_factura_id);
      $nuevo_producto->save();
    }
  }   

  /**
   * Función para generar un nuevo número de pedido.
   *
   * Puedes personalizar esta función de acuerdo a tus reglas de negocio.
   *
   * @return string
   *   El nuevo número de pedido.
   */
  protected function generarNuevoNumeroPedido() {
    // Ejemplo simple: concatenar "PED-" con la fecha y un número aleatorio.
    return 'PED-' . date('YmdHis') . '-' . rand(100, 999);
  }
}