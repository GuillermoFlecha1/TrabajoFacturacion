<?php

namespace Drupal\facturation\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\facturation\Entity\Facturas;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\facturation\Service\PDFService;
use Drupal\Core\Url;
use Drupal\Core\File\FileUrlGeneratorInterface;

class GenerarController extends ControllerBase {

  /**
   * El servicio de generación de PDFs.
   *
   * @var \Drupal\facturation\Service\PDFService
   */
  protected $pdfService;

  /**
   * El servicio para generar URLs de archivos.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * GenerarController constructor.
   *
   * @param \Drupal\facturation\Service\PDFService $pdf_service
   *   El servicio de generación de PDFs.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   El servicio para generar URLs de archivos.
   */
  public function __construct(PDFService $pdf_service, FileUrlGeneratorInterface $file_url_generator) {
    $this->pdfService = $pdf_service;
    $this->fileUrlGenerator = $file_url_generator;
  }

  /**
   * Crea una instancia del controlador.
   *
   * @return static
   *   El controlador.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('facturation.pdf_service'),
      $container->get('file_url_generator')
    );
  }

  /**
   * Genera el PDF de una factura y lo guarda en el servidor.
   *
   * @param int $facturas
   *   El ID de la factura que se quiere generar.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirección a la página de facturas.
   */
  public function generarPDF($facturas) {
    // Cargar la factura utilizando el ID recibido.
    $factura = Facturas::load($facturas);
    
    if (!$factura) {
        // Si la factura no existe, mostramos un mensaje de error.
        \Drupal::messenger()->addError($this->t('Factura no encontrada.'));
        return $this->redirect('facturation.facturas_list');
    }

    // Usar el servicio PDF para generar el PDF y guardarlo.
    $pdf_path = $this->pdfService->generarFacturaPDF($factura);

    if (!$pdf_path) {
        // Si hubo un error al generar el PDF.
        \Drupal::messenger()->addError($this->t('Hubo un error al generar el PDF.'));
        return $this->redirect('facturation.facturas_list');
    }

    // Obtener la URL pública del PDF generado.
    $file_url = $this->fileUrlGenerator->generateAbsoluteString($pdf_path);

    // Mensaje de éxito.
    \Drupal::messenger()->addMessage($this->t('Factura generada correctamente. El PDF ha sido guardado.'));
    
    // Redirigir a la URL del PDF.
    return new RedirectResponse($file_url);
    }
}
