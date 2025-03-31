<?php

namespace Drupal\facturation\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use FPDF;
use Drupal\Core\Url;

/**
 * Formulario para gestionar Facturas.
 */
class FacturaForm extends ContentEntityForm {

  public function buildForm(array $form, FormStateInterface $form_state) {
    // Se elimina la generación del número de pedido aquí; se asigna al finalizar.
    $form = parent::buildForm($form, $form_state);

    // --- Usuario (user_id) ---
    $default_value_User = '';
    if (!$this->entity->isNew() && !$this->entity->get('user_id')->isEmpty()) {
      $default_value_User = $this->entity->get('user_id')->first()->getValue()['target_id'];
    }
    if (isset($form['user_id']['widget'][0]['target_id'])) {
      $form['user_id']['widget'][0]['target_id'] = [
        '#type' => 'select',
        '#title' => $this->t('Usuario'),
        '#options' => $this->getUserOptions(),
        '#default_value' => $default_value_User,
        '#required' => TRUE,
      ];
    }
    else {
      $form['user_id'] = [
        '#type' => 'select',
        '#title' => $this->t('Usuario'),
        '#options' => $this->getUserOptions(),
        '#default_value' => $default_value_User,
        '#required' => TRUE,
      ];
    }

    $factura_id = $this->entity->id();
    $productos = $form_state->get('productos') ?? [];
    
    if ($factura_id && empty($productos)) {
      // Cargar las entidades factura_producto relacionadas.
      $factura_productos = \Drupal::entityTypeManager()
        ->getStorage('factura_producto')
        ->loadByProperties(['factura_id' => $factura_id]);
    
      foreach ($factura_productos as $factura_producto) {
        $producto_id = $factura_producto->get('producto_id')->target_id ?? NULL;
        $cantidad = $factura_producto->get('cantidad')->value ?? 0;
    
        $producto = \Drupal::entityTypeManager()->getStorage('producto')->load($producto_id);
    
        if ($producto) {
          $productos[$producto_id] = [
            'producto_id' => $producto_id,
            'producto' => $producto->get('nombre')->value ?? 'Producto desconocido',
            'cantidad' => $cantidad,
          ];
        }
      }
      $form_state->set('productos', $productos);
    }
    
    $form['producto_cantidad_container'] = [
      '#type' => 'container',
      '#attributes' => ['style' => 'display: flex; align-items: center; gap: 10px;'],
    ];
  
    // Campo de autocompletar para producto.
    $form['producto_cantidad_container']['producto_id'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Producto'),
      '#target_type' => 'producto', 
      '#selection_handler' => 'default',
      '#attributes' => [
        'placeholder' => $this->t('Escribe para buscar...'),
      ],
    ];
  
    $form['producto_cantidad_container']['cantidad'] = [
      '#type' => 'number',
      '#title' => $this->t('Cantidad'),
      '#min' => 1,
      '#default_value' => 1,
    ];
  
    $form['producto_cantidad_container']['agregar'] = [
      '#type' => 'submit',
      '#value' => $this->t('Añadir'),
      '#submit' => ['::agregarProducto'],
      '#ajax' => [
          'callback' => '::ajaxActualizarTabla',
          'wrapper' => 'productos-agregados-wrapper',
          'event' => 'click',
          'effect' => 'fade',
      ],
      '#attributes' => ['style' => 'margin-top: 40px;'],
    ];

    // Contenedor de la tabla de productos agregados.
    $form['productos_agregados_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'productos-agregados-wrapper'],
    ];

    $form['productos_agregados_wrapper']['productos_agregados'] = [
      '#type' => 'table',
      '#header' => [
          $this->t('Producto'),
          $this->t('Cantidad'),
          $this->t('Precio (€)'),
          $this->t('Impuesto (%)'),
          $this->t('Importe (€)'),
          $this->t('Acciones'),
      ],
      '#empty' => $this->t('No hay productos agregados.'),
      '#tree' => TRUE,
    ];

    $total_importe = 0;
    $total_impuesto = 0;
    $total_final = 0;

    foreach ($productos as $index => $producto) {
      $precio = $this->getProductoPrecio($producto['producto_id']);
      $impuesto = $this->getProductoImpuesto($producto['producto_id']);
      $importe = $precio * ($form_state->getValue(['productos_agregados', $index, 'cantidad']) ?? $producto['cantidad']);
      $impuesto_total_producto = ($importe * $impuesto) / 100;
      $total_importe += $importe;
      $total_impuesto += $impuesto_total_producto;
      $total_final = $total_importe + $total_impuesto;

      $form['productos_agregados_wrapper']['productos_agregados'][$index]['producto'] = [
        '#markup' => '<strong>' . $this->getProductoOptions()[$producto['producto_id']] . '</strong>',
        '#allowed_tags' => ['strong'],
        '#wrapper_attributes' => [
          'style' => 'width: 130px; white-space: nowrap;',
        ], 
      ];

      $form['productos_agregados_wrapper']['productos_agregados'][$index]['producto_id'] = [
        '#type' => 'hidden',
        '#value' => $producto['producto_id'],
        '#wrapper_attributes' => ['style' => 'display: none;'],
      ];

      $form['productos_agregados_wrapper']['productos_agregados'][$index]['cantidad'] = [
        '#type' => 'number',
        '#default_value' => $producto['cantidad'],
        '#min' => 1,
        '#ajax' => [
          'callback' => '::ajaxActualizarTabla',
          'event' => 'change',
          'wrapper' => 'productos-agregados-wrapper',
        ],
      ];

      $form['productos_agregados_wrapper']['productos_agregados'][$index]['precio'] = [
        '#type' => 'textfield',
        '#default_value' => number_format($precio, 2),
        '#disabled' => TRUE,
      ];

      $form['productos_agregados_wrapper']['productos_agregados'][$index]['impuesto'] = [
        '#type' => 'textfield',
        '#default_value' => number_format($impuesto, 2) . '%',
        '#disabled' => TRUE,
      ];

      $form['productos_agregados_wrapper']['productos_agregados'][$index]['importe'] = [
        '#type' => 'textfield',
        '#default_value' => number_format($importe, 2),
        '#disabled' => TRUE,
      ];

      $form['productos_agregados_wrapper']['productos_agregados'][$index]['acciones'] = [
        '#type' => 'submit',
        '#value' => $this->t('Eliminar'),
        '#submit' => ['::eliminarProducto'],
        '#limit_validation_errors' => [],
        '#name' => 'eliminar_producto_' . $producto['producto_id'],
      ];
    }

    $form['productos_agregados_wrapper']['totales'] = [
      '#type' => 'markup',
      '#markup' => '<div><b>Total Importe:</b> ' . number_format($total_importe, 2) . '€<br>' .
                   '<b>Total Impuesto:</b> ' . number_format($total_impuesto, 2) . '€<br>' .
                   '<b>Total Final:</b> ' . number_format($total_final, 2) . '€</div>',
    ];

    if (isset($form['fecha_vencimiento'])) {
      $form['fecha_vencimiento']['#element_validate'][] = [$this, 'validateFechaVencimiento'];
    }
    $form['generar_pdf'] = [
      '#type' => 'submit',
      '#value' => $this->t('Finalizar Factura'),
      '#submit' => ['::generarFacturaPDF'],
      '#attributes' => ['style' => 'margin-top: 20px;'],
    ];
    return $form;
  }

  /**
   * Callback AJAX para actualizar la tabla.
   */
  public function ajaxActualizarTabla(array &$form, FormStateInterface $form_state) {
    return $form['productos_agregados_wrapper'];
  }

  /**
   * Valida el campo fecha de vencimiento.
   */
  public function validateFechaVencimiento($element, FormStateInterface $form_state, $form) {
    $fecha_creacion = $form_state->getValue('fecha_creacion');
    $fecha_vencimiento = $form_state->getValue('fecha_vencimiento');

    if (is_array($fecha_creacion)) {
      $fecha_creacion = $fecha_creacion[0]['value'] ?? $fecha_creacion['value'];
    }
    if (is_array($fecha_vencimiento)) {
      $fecha_vencimiento = $fecha_vencimiento[0]['value'] ?? $fecha_vencimiento['value'];
    }

    if (strtotime($fecha_vencimiento) === false) {
      $form_state->setError($element, t('La fecha de vencimiento no es válida.'));
    }
    elseif (strtotime($fecha_vencimiento) <= strtotime($fecha_creacion)) {
      $form_state->setError($element, t('La fecha de vencimiento debe ser posterior a la fecha de creación.'));
    }
  }

  /**
   * Genera el PDF de la factura y asigna el número de pedido.
   * Para facturas nuevas se asigna "BI-<ID>"; si ya existe un número (por rectificación),
   * se respeta el valor asignado.
   */
  public function generarFacturaPDF(array &$form, FormStateInterface $form_state) {
    $factura = $this->entity;
    $factura_id = $factura->id();

    if (!$factura_id) {
      \Drupal::messenger()->addError($this->t('No se puede generar el PDF porque la factura no está guardada.'));
      return;
    }

    // Si la factura ya tiene un número asignado que no es el valor por defecto,
    // no lo sobreescribimos. Por ejemplo, en facturas derivadas de rectificación.
    $num_pedido_actual = $factura->get('num_pedido')->value;
    if (empty($num_pedido_actual) || !preg_match('/^(BI|BIA|BIV)-/', $num_pedido_actual)) {
      // Asignar el número de pedido usando el ID de la factura (formato BI-<ID>).
      $factura->set('num_pedido', 'BI-' . $factura_id);
    }
    
    // Para facturas normales se cambia el estado a "Finalizado".
    // (Si la factura proviene de un proceso de rectificación, ya se asignó el número correspondiente).
    if ($factura->get('estado')->value === 'Borrador') {
      $factura->set('estado', 'Finalizado');
    }
    $factura->save();

    // Datos del usuario.
    $user = $factura->get('user_id')->entity;
    $nombre_usuario = iconv('UTF-8', 'ISO-8859-1', $user->getDisplayName());
    $email_usuario = iconv('UTF-8', 'ISO-8859-1', $user->getEmail());
    $dni_usuario = iconv('UTF-8', 'ISO-8859-1', $user->get('field_dni')->value ?? 'N/A');

    // Datos de la factura.
    $num_pedido = iconv('UTF-8', 'ISO-8859-1', $factura->get('num_pedido')->value);
    $fecha_creacion = date('d/m/Y', strtotime($factura->get('fecha_creacion')->value));
    $fecha_vencimiento = date('d/m/Y', strtotime($factura->get('fecha_vencimiento')->value));

    // Productos asociados.
    $factura_productos = \Drupal::entityTypeManager()
      ->getStorage('factura_producto')
      ->loadByProperties(['factura_id' => $factura_id]);

    $pdf = new FPDF();
    $pdf->AddPage();
    
    // Encabezado.
    $pdf->SetFont('Arial', 'B', 18);
    $pdf->Cell(190, 10, iconv('UTF-8', 'ISO-8859-1', 'Factura N° ' . $num_pedido), 0, 1, 'C');
    $pdf->Ln(5);
    
    // Datos del cliente.
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(100, 8, iconv('UTF-8', 'ISO-8859-1', 'Datos del Cliente:'), 0, 1);
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(100, 6, iconv('UTF-8', 'ISO-8859-1', 'Nombre: ') . $nombre_usuario, 0, 1);
    $pdf->Cell(100, 6, iconv('UTF-8', 'ISO-8859-1', 'Email: ') . $email_usuario, 0, 1);
    $pdf->Cell(100, 6, iconv('UTF-8', 'ISO-8859-1', 'DNI: ') . $dni_usuario, 0, 1);
    $pdf->Ln(5);

    // Fechas.
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(100, 8, 'Detalles de la Factura:', 0, 1);
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(100, 6, 'Fecha de Creacion: ' . $fecha_creacion, 0, 1);
    $pdf->Cell(100, 6, 'Fecha de Vencimiento: ' . $fecha_vencimiento, 0, 1);
    $pdf->Ln(8);

    // Tabla de productos.
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

      $pdf->Cell(65, 8, $nombre_producto, 1);
      $pdf->Cell(28, 8, $cantidad, 1, 0, 'C');
      $pdf->Cell(34, 8, number_format($precio, 2), 1, 0, 'C');
      $pdf->Cell(28, 8, $impuesto . '%', 1, 0, 'C');
      $pdf->Cell(35, 8, number_format($importe, 2), 1, 1, 'C');

      $total_importe += $importe;
      $total_impuesto += $impuesto_total;
    }

    $total_final = $total_importe + $total_impuesto;

    // Totales.
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

    // Ruta de guardado del PDF.
    $project_root = dirname(DRUPAL_ROOT);
    $pdf_folder = $project_root . '/private/pdf';
    if (!file_exists($pdf_folder)) {
      mkdir($pdf_folder, 0777, true);
    }
    $file_name = 'factura_' . $factura_id . '.pdf';
    $file_path = $pdf_folder . '/' . $file_name;
    $pdf->Output('F', $file_path);

    $url = Url::fromRoute('entity.facturas.collection');
    $form_state->setRedirectUrl($url);
    \Drupal::messenger()->addMessage($this->t('Factura generada y guardada en: %path', ['%path' => $file_path]));
    return $file_path;
  }

  public function agregarProducto(array &$form, FormStateInterface $form_state) {
    $producto_id = $form_state->getValue('producto_id');
    $cantidad = $form_state->getValue('cantidad');
    $productos = $form_state->get('productos') ?? [];

    if (!empty($producto_id)) {
      $precio = $this->getProductoPrecio($producto_id);
      $impuesto = $this->getProductoImpuesto($producto_id);
    }
    else {
      \Drupal::messenger()->addError($this->t('Error: No se seleccionó un producto válido.'));
      return;
    }

    if (isset($productos[$producto_id])) {
      \Drupal::messenger()->addError($this->t('Este producto ya ha sido añadido.'));
      return;
    }
    else {
      $importe = $precio * $cantidad;
      $productos[$producto_id] = [
        'producto_id' => $producto_id,
        'producto' => $this->getProductoOptions()[$producto_id],
        'cantidad' => $cantidad,
        'precio' => $precio,
        'impuesto' => $impuesto,
        'importe' => $importe,
      ];
    }

    $form_state->set('productos', $productos);
    $form_state->setRebuild();
  }

  public function eliminarProducto(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $producto_id = str_replace('eliminar_producto_', '', $triggering_element['#name']);
    $productos = $form_state->get('productos') ?? [];
    foreach ($productos as $key => $producto) {
      if ($producto['producto_id'] == $producto_id) {
        unset($productos[$key]);
        break;
      }
    }
    $form_state->set('productos', $productos);
    $form_state->setRebuild();
    return $this->ajaxActualizarTabla($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $user_value = $form_state->getValue('user_id');
    $factura = $this->entity;
    if (!empty($user_value)) {
      $factura->set('user_id', $user_value);
    }
    // Se asigna el estado "Borrador"; el número se asignará al finalizar (si no está ya definido).
    $factura->set('estado', 'Borrador');
    $factura->save();
    $factura_id = $factura->id();

    $productos_actualizados = $form_state->getValue(['productos_agregados']) ?? [];
    if (!is_array($productos_actualizados)) {
      $productos_actualizados = [];
    }
    \Drupal::database()->delete('factura_productos')
      ->condition('factura_id', $factura_id)
      ->execute();

    $total_importe = 0;
    $total_impuesto = 0;
    foreach ($productos_actualizados as $index => $producto) {
      $producto_id = $producto['producto_id'];
      $cantidad = $producto['cantidad'];
      if (!empty($producto_id) && $cantidad > 0) {
        $factura_producto = \Drupal::entityTypeManager()->getStorage('factura_producto')->create([
          'factura_id' => $factura_id,
          'producto_id' => $producto_id,
          'cantidad' => $cantidad,
        ]);
        $factura_producto->save();

        $precio = $this->getProductoPrecio($producto_id);
        $impuesto = $this->getProductoImpuesto($producto_id);
        $importe = $precio * $cantidad;
        $impuesto_total_producto = ($importe * $impuesto) / 100;
        $total_importe += $importe;
        $total_impuesto += $impuesto_total_producto;
      }
    }
    $total_final = $total_importe + $total_impuesto;
    if ($factura->get('total_final')->value != $total_final) {
      $factura->set('total_final', $total_final);
      $factura->save();
    }
    \Drupal::messenger()->addMessage($this->t('La factura ha sido guardada correctamente con las cantidades actualizadas.'));
    $url = Url::fromRoute('entity.facturas.collection');
    $form_state->setRedirectUrl($url);
  }

  private function getUserOptions() {
    $options = [];
    $users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['status' => 1]);
    foreach ($users as $user) {
      if ($user->id() > 0) {
        $options[$user->id()] = $user->getDisplayName();
      }
    }
    return $options;
  }
 
  private function getProductoOptions($factura_id = NULL) {
    $options = [];
    if ($factura_id) {
      $factura_productos = \Drupal::entityTypeManager()
        ->getStorage('factura_producto')
        ->loadByProperties(['factura_id' => $factura_id]);
      if (empty($factura_productos)) {
        \Drupal::messenger()->addWarning($this->t('No hay productos asociados a esta factura.'));
        return $options;
      }
      foreach ($factura_productos as $factura_producto) {
        $producto_id = $factura_producto->get('producto_id')->target_id;
        $producto = \Drupal::entityTypeManager()->getStorage('producto')->load($producto_id);
        if ($producto) {
          $nombre = $producto->get('nombre')->value ?? 'Producto desconocido';
          $options[$producto_id] = $nombre;
        }
      }
    }
    else {
      $productos = \Drupal::entityTypeManager()->getStorage('producto')->loadMultiple();
      if (empty($productos)) {
        \Drupal::messenger()->addError($this->t('Error: No se encontraron productos en la base de datos.'));
        return $options;
      }
      foreach ($productos as $producto) {
        $nombre = $producto->get('nombre')->value ?? 'Producto sin nombre';
        $options[$producto->id()] = $nombre;
      }
    }
    \Drupal::logger('facturation')->notice('Productos cargados para @modo factura @factura_id: @productos', [
      '@modo' => $factura_id ? 'edición' : 'creación',
      '@factura_id' => $factura_id ?? 'N/A',
      '@productos' => json_encode(array_keys($options))
    ]);
    return $options;
  }

  private function getProductoPrecio($producto_id) {
    $producto = \Drupal::entityTypeManager()->getStorage('producto')->load($producto_id);
    return $producto ? $producto->get('precio')->value : 0;
  }

  private function getProductoImpuesto($producto_id) {
    $producto = \Drupal::entityTypeManager()->getStorage('producto')->load($producto_id);
    if ($producto && $producto->hasField('impuesto_id')) {
      $impuesto_id = $producto->get('impuesto_id')->target_id;
      $impuesto = \Drupal::entityTypeManager()->getStorage('impuestos')->load($impuesto_id);
      return ($impuesto && $impuesto->hasField('valor')) ? $impuesto->get('valor')->value : 0;
    }
    return 0;
  }
}
