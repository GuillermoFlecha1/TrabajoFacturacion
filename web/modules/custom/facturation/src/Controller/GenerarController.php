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
  
    // Si la factura es "Rectificativa", buscar la original
    if ($factura->get('estado')->value === 'Rectificativa') {
      $factura_original = \Drupal::entityTypeManager()
        ->getStorage('facturas')
        ->loadByProperties(['estado' => 'Rectificada', 'num_pedido' => $factura->get('num_pedido')->value - 1]);
  
      if (!empty($factura_original)) {
        $factura = reset($factura_original);
      }
    }
  
    // Obtener el ID de la factura original o rectificativa
    $factura_id = $factura->id();
  
    // Ruta del PDF basado en la factura original
    $pdf_path = DRUPAL_ROOT . "/modules/custom/facturation/pdf/factura_{$factura_id}.pdf";
  
    // Log de depuración
    \Drupal::logger('facturation')->notice('Buscando PDF en: @path', ['@path' => $pdf_path]);
  
    // Verificar si el archivo existe
    if (!file_exists($pdf_path)) {
      throw new FileNotFoundException("El PDF de la factura no se encuentra: {$pdf_path}");
    }
  
    // Leer el contenido del archivo
    $pdf_content = file_get_contents($pdf_path);
  
    // Responder con el PDF en modo inline
    $response = new Response($pdf_content);
    $response->headers->set('Content-Type', 'application/pdf');
    $response->headers->set('Content-Disposition', 'inline; filename="factura_' . $factura_id . '.pdf"');
  
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
