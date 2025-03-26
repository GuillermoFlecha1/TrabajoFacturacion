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
  
  // Select de productos filtrados
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
      '#value' => $this->t('Finalizar Factura'),
      '#submit' => ['::finalizarFactura'],
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
   * Función de submit que solo cambia el estado a Finalizado.
   */
  public function finalizarFactura(array &$form, FormStateInterface $form_state) {
    $factura = $this->entity;

    if (!$factura) {
        \Drupal::messenger()->addError($this->t('No se puede finalizar la factura.'));
        return;
    }

    // Cambiar el estado de la factura a "Finalizado"
    $factura->set('estado', 'Finalizado');
    $factura->save();

    \Drupal::messenger()->addMessage($this->t('La factura ha sido finalizada.'));
    
    // Redirigir a la lista de facturas después de finalizar
    $url = Url::fromRoute('entity.facturas.collection');
    $form_state->setRedirectUrl($url);
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

    // Verificar si el producto ya está en el arreglo de productos
    if (isset($productos[$producto_id])) {
        // Si ya está, mostrar un mensaje de error y salir
        \Drupal::messenger()->addError($this->t('Este producto ya ha sido añadido.'));
        return;
    } else {
        // Si no está, agregarlo como un nuevo producto
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

    // Actualizar el estado del formulario con los productos modificados
    $form_state->set('productos', $productos);
    $form_state->setRebuild();
}


  /**
   * Elimina un producto del listado editable.
   */
  public function eliminarProducto(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $producto_id = str_replace('eliminar_producto_', '', $triggering_element['#name']);
    
    // Obtener los productos agregados
    $productos = $form_state->get('productos') ?? [];

    // Buscar y eliminar el producto del arreglo de productos
    foreach ($productos as $key => $producto) {
        if ($producto['producto_id'] == $producto_id) {
            unset($productos[$key]);  // Eliminar producto de la lista de productos agregados
            break;
        }
    }

    // Actualizar el estado del formulario con los productos restantes
    $form_state->set('productos', $productos);

    // Recalcular los totales y la tabla de productos
    $form_state->setRebuild();

    return $this->ajaxActualizarTabla($form, $form_state);
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

    $factura->set('estado', 'Borrador');
    $factura->save();
    $factura_id = $factura->id();

    // 🔹 Obtener los productos actualizados desde el formulario (los editados en la tabla)
    $productos_actualizados = $form_state->getValue(['productos_agregados']) ?? [];

    // Verificar que productos_actualizados sea un array y no una cadena
    if (!is_array($productos_actualizados)) {
        $productos_actualizados = [];
    }

    // 🔹 Eliminar productos anteriores y guardar los nuevos
    \Drupal::database()->delete('factura_productos')
        ->condition('factura_id', $factura_id)
        ->execute();

    $total_importe = 0;
    $total_impuesto = 0;

    // 🔹 Asegurarse de que productos_actualizados tenga valores
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

    // Mensaje de éxito
    \Drupal::messenger()->addMessage($this->t('La factura ha sido guardada correctamente con las cantidades actualizadas.'));
    
    // Redirigir a la lista de facturas
    $url = Url::fromRoute('entity.facturas.collection');
    $form_state->setRedirectUrl($url);
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