<?php

namespace Drupal\facturation\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\facturation\Entity\Facturas;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

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
  
    // Si la factura es "Rectificativa", se intenta buscar la original.
    if ($factura->get('estado')->value === 'Rectificativa') {
      // Se asume que el número de pedido de la rectificativa es "BIV-<n>"
      // y la original debe tener "BIA-<n>".
      $num_pedido = $factura->get('num_pedido')->value;
      // Extraer el número sin prefijo (eliminar "BIV-").
      $numero = preg_replace('/^BIV-/', '', $num_pedido);
      // Buscar la factura original con estado "Rectificada" y número "BIA-<n>".
      $factura_original = \Drupal::entityTypeManager()
        ->getStorage('facturas')
        ->loadByProperties(['estado' => 'Rectificada', 'num_pedido' => 'BIA-' . $numero]);
  
      if (!empty($factura_original)) {
        $factura = reset($factura_original);
      }
    }
  
    $factura_id = $factura->id();
    // La ruta del PDF se construye igual que en FacturaForm.
    $pdf_path = dirname(DRUPAL_ROOT) . "/private/pdf/factura_{$factura_id}.pdf";
    \Drupal::logger('facturation')->notice('Buscando PDF en: @path', ['@path' => $pdf_path]);
  
    if (!file_exists($pdf_path)) {
      throw new FileNotFoundException("El PDF de la factura no se encuentra: {$pdf_path}");
    }
  
    $pdf_content = file_get_contents($pdf_path);
    $response = new Response($pdf_content);
    $response->headers->set('Content-Type', 'application/pdf');
    $response->headers->set('Content-Disposition', 'inline; filename="factura_' . $factura_id . '.pdf"');
  
    return $response;
  }  

  /**
   * Rectifica una factura.
   *
   * Realiza la duplicación de la factura, actualizando el estado de la
   * factura original a "Rectificada" y la copia a "Rectificativa", asignándole
   * el mismo número base.
   */
  public function rectificar(Facturas $facturas) {
    // Obtener el número actual de la factura original (se asume en formato BI-<n>).
    $original = $facturas->get('num_pedido')->value;
    // Extraer el número sin prefijo.
    $numero = preg_replace('/^BI-/', '', $original);
    // Actualizar la factura original: asignarle el prefijo BIA- y cambiar estado.
    $facturas->set('num_pedido', 'BIA-' . $numero);
    $facturas->set('estado', 'Rectificada');
    $facturas->save();
  
    // Clonar la factura original para crear la factura rectificativa.
    $factura_rectificativa = $facturas->createDuplicate();
    $factura_rectificativa->set('num_pedido', 'BIV-' . $numero);
    $factura_rectificativa->set('estado', 'Rectificativa');
    $factura_rectificativa->save();
    
    // Duplicar productos de la factura original a la rectificativa.
    $this->duplicarProductos($facturas->id(), $factura_rectificativa->id());
  
    // Crear una copia en borrador de la factura rectificativa.
    // Aquí, en lugar de dejar que al finalizar se reasigne usando el nuevo ID,
    // asignamos explícitamente el número deseado.
    $factura_borrador = $factura_rectificativa->createDuplicate();
    // Asignamos el número que esperamos: en este ejemplo, queremos que sea BI-<n+1>.
    // Por ello, incrementamos el número base en 1.
    $nuevo_num = $numero + 1;
    $factura_borrador->set('num_pedido', 'BI-' . $nuevo_num);
    $factura_borrador->set('estado', 'Borrador');
    $factura_borrador->save();
    $this->duplicarProductos($factura_rectificativa->id(), $factura_borrador->id());
  
    $this->messenger()->addStatus($this->t('Se ha creado una factura rectificativa con el número de pedido BIV-%num y una copia en borrador con BI-%num2.', [
      '%num' => $numero,
      '%num2' => $nuevo_num,
    ]));
  
    return new RedirectResponse(Url::fromRoute('entity.facturas.collection')->toString());
  }
  
  /**
   * Duplica los productos de una factura.
   */
  private function duplicarProductos($factura_original_id, $nueva_factura_id) {
    $factura_productos = \Drupal::entityTypeManager()
      ->getStorage('factura_producto')
      ->loadByProperties(['factura_id' => $factura_original_id]);
  
    foreach ($factura_productos as $factura_producto) {
      $nuevo_producto = $factura_producto->createDuplicate();
      $nuevo_producto->set('factura_id', $nueva_factura_id);
      $nuevo_producto->save();
    }
  }

  /**
   * (Opcional) Genera un nuevo número de pedido.
   */
  protected function generarNuevoNumeroPedido() {
    return 'PED-' . date('YmdHis') . '-' . rand(100, 999);
  }
}
