<?php

namespace Drupal\facturation\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use FPDF;
/**
 * Formulario para gestionar Facturas.
 */
class FacturaForm extends ContentEntityForm {

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // Generar número de pedido si es necesario
    $num_pedido_actual = $this->entity->get('num_pedido')?->value;
    if (empty($num_pedido_actual) && !$form_state->has('num_pedido_aleatorio')) {
      do {
        $num_pedido_actual = rand(10000000, 99999999);
      } while (\Drupal::entityQuery('facturas')->condition('num_pedido', $num_pedido_actual)->range(0, 1)->accessCheck(FALSE)->execute());
      $form_state->set('num_pedido_aleatorio', $num_pedido_actual);
      $this->entity->set('num_pedido', $num_pedido_actual);
    }
    
    if (!$this->entity->isNew()) {
      $form['num_pedido'] = [
        '#type' => 'markup',
        '#markup' => $this->t('<b>Número de pedido único:</b> @num_pedido', ['@num_pedido' => $num_pedido_actual]),
      ];
    }

   // Prepara el valor por defecto (si es edición).
   $default_value_User = '';
   if (!$this->entity->isNew() && !$this->entity->get('user_id')->isEmpty()) {
     // Para un campo de referencia de valor único, obtenemos el primer item.
     $default_value_User = $this->entity->get('user_id')->first()->getValue()['target_id'];
   }
   // --- Usuario (user_id) ---
   if (isset($form['user_id']['widget'][0]['target_id'])) {
     $form['user_id']['widget'][0]['target_id'] = [
       '#type' => 'select',
       '#title' => $this->t('Usuario'),
       '#options' => $this->getUserOptions(),
       '#default_value' => $default_value_User,
       '#required' => TRUE,
     ];
   } else {
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
      // Cargar todas las entidades factura_producto relacionadas con la factura.
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

    $producto_options = $this->getProductoOptions();
    $default_producto_id = !empty($producto_options) ? key($producto_options) : NULL;

    $form['producto_cantidad_container']['producto_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Producto'),
      '#options' => $producto_options,
      '#required' => TRUE,
      '#default_value' => $form_state->getValue('producto_id') ?? $default_producto_id,
    ];

    $form['producto_cantidad_container']['cantidad'] = [
      '#type' => 'number',
      '#title' => $this->t('Cantidad'),
      '#min' => 1,
      '#default_value' => $form_state->getValue('cantidad') ?? 1,
      '#required' => TRUE,
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

    // Contenedor para la tabla de productos agregados
    $form['productos_agregados_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'productos-agregados-wrapper'],
  ];

  // Crear la tabla de productos agregados
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
  $total_final=0;

  foreach ($productos as $index => $producto) {
      $precio = $this->getProductoPrecio($producto['producto_id']);
      $impuesto = $this->getProductoImpuesto($producto['producto_id']);
      $importe = $precio * ($form_state->getValue(['productos_agregados', $index, 'cantidad']) ?? $producto['cantidad']);
      $impuesto_total_producto = ($importe * $impuesto) / 100;
      $total_importe += $importe;
      $total_impuesto += $impuesto_total_producto;
      $total_final = $total_importe + $total_impuesto;

      $form['productos_agregados_wrapper']['productos_agregados'][$index]['producto'] = [
          '#type' => 'select',
          '#options' => $this->getProductoOptions(),
          '#default_value' => $producto['producto_id'],
          '#ajax' => [
              'callback' => '::ajaxActualizarTabla',
              'wrapper' => 'productos-agregados-wrapper',
              'event' => 'change',
          ],
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

  // Agregar los totales al final de la tabla
  $form['productos_agregados_wrapper']['totales'] = [
      '#type' => 'markup',
      '#markup' => '<div><b>Total Importe:</b> ' . number_format($total_importe, 2) . '€<br>' .
                   '<b>Total Impuesto:</b> ' . number_format($total_impuesto, 2) . '€<br>' .
                   '<b>Total Final:</b> ' . number_format($total_final, 2) . '€</div>',
  ];

    // --- Fecha de vencimiento ---
    if (isset($form['fecha_vencimiento'])) {
      $form['fecha_vencimiento']['#element_validate'][] = [$this, 'validateFechaVencimiento'];
    }
    $form['generar_pdf'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generar PDF'),
      '#submit' => ['::generarFacturaPDF'],
      '#attributes' => ['style' => 'margin-top: 20px;'],
    ];
    return $form;
  }
  /**
   * Callback AJAX para actualizar la tabla de productos.
   */
  public function ajaxActualizarTabla(array &$form, FormStateInterface $form_state) {
    return $form['productos_agregados_wrapper'];
  }


  /**
   * Validación del campo cantidad.
   */
    public function validateCantidad($element, FormStateInterface $form_state, $form) {
    $cantidad = $form_state->getValue('cantidad');
    if (is_array($cantidad)) {
      if (isset($cantidad[0]['value'])) {
        $cantidad = $cantidad[0]['value'];
      }
      elseif (isset($cantidad['value'])) {
        $cantidad = $cantidad['value'];
      }
    }
    $cantidad_numeric = floatval($cantidad);
    if ($cantidad_numeric < 1) {
      $form_state->setError($element, t('La cantidad debe ser mayor que 0'));
    }
  }
  /**
   * Validación del campo fecha_vencimiento.
   */
  public function validateFechaVencimiento($element, FormStateInterface $form_state, $form) {
    $fecha_creacion = $form_state->getValue('fecha_creacion');
    $fecha_vencimiento = $form_state->getValue('fecha_vencimiento');

    // Asegúrate de obtener el valor de las fechas correctamente
    if (is_array($fecha_creacion)) {
      $fecha_creacion = isset($fecha_creacion[0]['value']) ? $fecha_creacion[0]['value'] : $fecha_creacion['value'];
    }
    if (is_array($fecha_vencimiento)) {
      $fecha_vencimiento = isset($fecha_vencimiento[0]['value']) ? $fecha_vencimiento[0]['value'] : $fecha_vencimiento['value'];
    }

    // Validar que la fecha de vencimiento sea válida
    if (strtotime($fecha_vencimiento) === false) {
      $form_state->setError($element, t('La fecha de vencimiento no es válida.'));
    }
    // Validar que la fecha de vencimiento sea posterior a la fecha de creación
    elseif (strtotime($fecha_vencimiento) <= strtotime($fecha_creacion)) {
      $form_state->setError($element, t('La fecha de vencimiento debe ser posterior a la fecha de creación.'));
    }
  }

  public function generarFacturaPDF(array &$form, FormStateInterface $form_state) {
    $factura = $this->entity;
    $factura_id = $factura->id();

    if (!$factura_id) {
        \Drupal::messenger()->addError($this->t('No se puede generar el PDF porque la factura no está guardada.'));
        return;
    }

    // Obtener datos del usuario
    $user = $factura->get('user_id')->entity;
    $nombre_usuario = iconv('UTF-8', 'ISO-8859-1', $user->getDisplayName());
    $email_usuario = iconv('UTF-8', 'ISO-8859-1', $user->getEmail());
    $dni_usuario = iconv('UTF-8', 'ISO-8859-1', $user->get('field_dni')->value ?? 'N/A');

    // Obtener datos de la factura
    $num_pedido = iconv('UTF-8', 'ISO-8859-1', $factura->get('num_pedido')->value);
    $fecha_creacion = date('d/m/Y', strtotime($factura->get('fecha_creacion')->value));
    $fecha_vencimiento = date('d/m/Y', strtotime($factura->get('fecha_vencimiento')->value));

    // Obtener productos de la factura
    $factura_productos = \Drupal::entityTypeManager()
        ->getStorage('factura_producto')
        ->loadByProperties(['factura_id' => $factura_id]);

    // Crear instancia de FPDF
    $pdf = new FPDF();
    $pdf->AddPage();
    
    // **Encabezado**
    $pdf->SetFont('Arial', 'B', 18);
    $pdf->Cell(190, 10, iconv('UTF-8', 'ISO-8859-1', 'Factura N° ' . $num_pedido), 0, 1, 'C');
    $pdf->Ln(5);
    
    // **Datos del Cliente**
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(100, 8, iconv('UTF-8', 'ISO-8859-1', 'Datos del Cliente:'), 0, 1);
    
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(100, 6, iconv('UTF-8', 'ISO-8859-1', 'Nombre: ') . $nombre_usuario, 0, 1);
    $pdf->Cell(100, 6, iconv('UTF-8', 'ISO-8859-1', 'Email: ') . $email_usuario, 0, 1);
    $pdf->Cell(100, 6, iconv('UTF-8', 'ISO-8859-1', 'DNI: ') . $dni_usuario, 0, 1);
    $pdf->Ln(5);

    // **Fechas**
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(100, 8, 'Detalles de la Factura:', 0, 1);

    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(100, 6, 'Fecha de Creacion: ' . $fecha_creacion, 0, 1);
    $pdf->Cell(100, 6, 'Fecha de Vencimiento: ' . $fecha_vencimiento, 0, 1);
    $pdf->Ln(8);

    // **Encabezado Tabla**
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(200, 200, 200);
    $pdf->Cell(60, 8, 'Producto', 1, 0, 'C', true);
    $pdf->Cell(25, 8, 'Cantidad', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Precio (' . chr(128) . ')', 1, 0, 'C', true);
    $pdf->Cell(25, 8, 'Impuesto (%)', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Importe (' . chr(128) . ')', 1, 1, 'C', true);

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
        $pdf->Cell(60, 8, $nombre_producto, 1);
        $pdf->Cell(25, 8, $cantidad, 1, 0, 'C');
        $pdf->Cell(30, 8, number_format($precio, 2), 1, 0, 'C');
        $pdf->Cell(25, 8, $impuesto . '%', 1, 0, 'C');
        $pdf->Cell(30, 8, number_format($importe, 2), 1, 1, 'C');

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

    // **Salida del PDF**
    $pdf->Output('D', 'Factura_' . $num_pedido . '.pdf');
    exit();
}

  /**
   * Agregar producto al array asociativo en el estado del formulario.
   */
  public function agregarProducto(array &$form, FormStateInterface $form_state) {
    $producto_id = $form_state->getValue('producto_id');
    $cantidad = $form_state->getValue('cantidad');

    $productos = $form_state->get('productos') ?? [];

    if (!empty($producto_id)) {
      $precio = $this->getProductoPrecio($producto_id);
      $impuesto = $this->getProductoImpuesto($producto_id);
    } else {
      \Drupal::messenger()->addError($this->t('Error: No se seleccionó un producto válido.'));
      return;
    }
    $importe = $precio * $cantidad;

    // Agregar producto con precio e importe calculado
    $productos[$producto_id] = [
      'producto_id' => $producto_id,
      'producto' => $this->getProductoOptions()[$producto_id],
      'cantidad' => $cantidad,
      'precio' => $precio,
      'impuesto' => $impuesto,
      'importe' => $importe,
    ];

    $form_state->set('productos', $productos);
    $form_state->setRebuild();
  }

  /**
   * Elimina un producto del listado editable.
   */
  public function eliminarProducto(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $producto_id = str_replace('eliminar_producto_', '', $triggering_element['#name']);
    
    $productos = $form_state->get('productos') ?? [];
    
    // Buscar el índice del producto en el arreglo usando su ID
    foreach ($productos as $key => $producto) {
        if ($producto['producto_id'] == $producto_id) {
            unset($productos[$key]);
            break;
        }
    }

    
// Reindexar el arreglo después de eliminar
    $productos = array_values($productos);
    
    // Actualizar el estado del formulario con los productos restantes
    $form_state->set('productos', $productos);
    
    // Marcar el formulario para que se reconstruya y refleje los cambios
    $form_state->setRebuild();
  }


  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $num_pedido_aleatorio = $form_state->get('num_pedido_aleatorio');
    $user_value = $form_state->getValue('user_id');

    // Obtener la entidad de factura
    $factura = $this->entity;

    if (!empty($num_pedido_aleatorio)) {
        $factura->set('num_pedido', $num_pedido_aleatorio);
    }

    if (!empty($user_value)) {
        $factura->set('user_id', $user_value);
    }

    $factura->save();
    $factura_id = $factura->id();

    // 🔹 Obtener los productos actualizados desde el formulario (los editados en la tabla)
    $productos_actualizados = $form_state->getValue(['productos_agregados']) ?? [];

    // 🔹 Eliminar productos anteriores y guardar los nuevos
    \Drupal::database()->delete('factura_productos')
        ->condition('factura_id', $factura_id)
        ->execute();

    $total_importe = 0;
    $total_impuesto = 0;

    foreach ($productos_actualizados as $index => $producto) {
        $producto_id = $producto['producto'];
        $cantidad = $producto['cantidad'];

        if (!empty($producto_id) && $cantidad > 0) {
            $factura_producto = \Drupal::entityTypeManager()->getStorage('factura_producto')->create([
                'factura_id' => $factura_id,
                'producto_id' => $producto_id,
                'cantidad' => $cantidad,
            ]);
            $factura_producto->save();

            // 🔹 Obtener precio e impuesto del producto
            $precio = $this->getProductoPrecio($producto_id);
            $impuesto = $this->getProductoImpuesto($producto_id);
            $importe = $precio * $cantidad;
            $impuesto_total_producto = ($importe * $impuesto) / 100;

            $total_importe += $importe;
            $total_impuesto += $impuesto_total_producto;
        }
    }

    $total_final = $total_importe + $total_impuesto;

    // 🔹 Guardar el total correcto en la factura
    if ($factura->get('total_final')->value != $total_final) {
        $factura->set('total_final', $total_final);
        $factura->save();
    }

    \Drupal::messenger()->addMessage($this->t('La factura ha sido guardada correctamente con las cantidades actualizadas.'));
}



  private function getUserOptions() {
    $options = [];
    $users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['status' => 1]); // Cargar solo usuarios activos
    
    foreach ($users as $user) {
        if ($user->id() > 0) { // Filtrar usuarios con ID mayor que 0
            $options[$user->id()] = $user->getDisplayName();
        }
    }

    return $options;
  }


  private function getProductoOptions($factura_id = NULL) {
    $options = [];

    if ($factura_id) {
        // Modo edición: Obtener solo los productos asociados a esta factura
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
    } else {
        // Modo creación: Mostrar todos los productos disponibles
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
  /**
   * Obtener el precio de un producto.
   */
  private function getProductoPrecio($producto_id) {
    $producto = \Drupal::entityTypeManager()->getStorage('producto')->load($producto_id);
    return $producto ? $producto->get('precio')->value : 0;
  }

  /**
   * Obtener el Impuesto de un producto.
   */
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
