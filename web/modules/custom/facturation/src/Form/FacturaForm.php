<?php

namespace Drupal\facturation\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para gestionar Facturas.
 */
class FacturaForm extends ContentEntityForm {

  public function buildForm(array $form, FormStateInterface $form_state) {
    // Recuperar los productos ya almacenados.
    $stored_products = $form_state->get('productos') ?? [];
    // Identificar el elemento disparador.
    $trigger = $form_state->getTriggeringElement();

    if (!empty($trigger['#name']) && strpos($trigger['#name'], 'agregar') === false) {
      $submitted_products = $form_state->getValue('productos_agregados');
      if (!empty($submitted_products)) {
        $productos = [];
        foreach ($submitted_products as $key => $row) {
          // Procesar la fila solo si se ha seleccionado un producto.
          if (!empty($row['producto'])) {
            $producto_id = $row['producto'];
            $cantidad = isset($row['cantidad']) && $row['cantidad'] !== '' ? $row['cantidad'] : 1;
            $precio = $this->getProductoPrecio($producto_id);
            $impuesto = $this->getProductoImpuesto($producto_id);
            $importe = $precio * $cantidad * (1 + $impuesto / 100);
            $productos[] = [
              'producto_id' => $producto_id,
              'producto' => $this->getProductoOptions()[$producto_id],
              'cantidad' => $cantidad,
              'precio' => $precio,
              'impuesto' => $impuesto,
              'importe' => $importe,
            ];
          }
        }
        $form_state->set('productos', $productos);
      }
      else {
        $productos = $stored_products;
      }
    }
    else {
      // Si el disparador es el botón "Añadir", se conserva el array ya almacenado.
      $productos = $stored_products;
    }

    $form = parent::buildForm($form, $form_state);

    // Generar número de pedido si es necesario.
    $num_pedido_actual = $this->entity->get('num_pedido')?->value;
    if (empty($num_pedido_actual) && !$form_state->has('num_pedido_aleatorio')) {
      do {
        $num_pedido_actual = rand(10000000, 99999999);
      } while (\Drupal::entityQuery('facturas')
        ->condition('num_pedido', $num_pedido_actual)
        ->range(0, 1)
        ->accessCheck(FALSE)
        ->execute());
      $form_state->set('num_pedido_aleatorio', $num_pedido_actual);
      $this->entity->set('num_pedido', $num_pedido_actual);
    }

    $form['num_pedido'] = [
      '#type' => 'markup',
      '#markup' => $this->t('<b>Número de pedido único:</b> @num_pedido', ['@num_pedido' => $num_pedido_actual]),
    ];

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
    
    $productos = $form_state->get('productos') ?? [];
    // Contenedor para agregar productos.
    $form['producto_cantidad_container'] = [
      '#type' => 'container',
      '#attributes' => ['style' => 'display: flex; align-items: center; gap: 10px;'],
    ];

    $form['producto_cantidad_container']['producto_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Producto'),
      '#options' => $this->getProductoOptions(),
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

    // Contenedor AJAX para la tabla.
    $form['productos_agregados_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'productos-agregados-wrapper'],
    ];

    // Mostrar los productos agregados en una tabla editable.
    $form['productos_agregados_wrapper']['productos_agregados'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Producto'),
        $this->t('Cantidad'),
        $this->t('Precio (€)'),
        $this->t('Impuestos (%)'),
        $this->t('Importe (€)'),
        $this->t('Acciones'),
      ],
      '#empty' => $this->t('No hay productos agregados.'),
      '#tree' => TRUE,
    ];

    foreach ($productos as $index => $producto) {
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
          'wrapper' => 'productos-agregados-wrapper',
          'event' => 'change',
        ],
      ];
      $form['productos_agregados_wrapper']['productos_agregados'][$index]['precio'] = [
        '#type' => 'textfield',
        '#default_value' => number_format($producto['precio'], 2),
        '#disabled' => TRUE,
      ];
      $form['productos_agregados_wrapper']['productos_agregados'][$index]['impuesto'] = [
        '#type' => 'textfield',
        '#default_value' => number_format($producto['impuesto'], 2) . '%',
        '#disabled' => TRUE,
      ];
      $form['productos_agregados_wrapper']['productos_agregados'][$index]['importe'] = [
        '#type' => 'textfield',
        '#default_value' => number_format($producto['importe'], 2),
        '#disabled' => TRUE,
      ];
      $form['productos_agregados_wrapper']['productos_agregados'][$index]['acciones'] = [
        '#type' => 'submit',
        '#value' => $this->t('Eliminar'),
        '#submit' => ['::eliminarProducto'],
        '#limit_validation_errors' => [],
        '#name' => 'eliminar_producto_' . $index,
      ];
    }

    // --- Fecha de vencimiento ---
    if (isset($form['fecha_vencimiento'])) {
      $form['fecha_vencimiento']['#element_validate'][] = [$this, 'validateFechaVencimiento'];
    }
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

    if (is_array($fecha_creacion)) {
      $fecha_creacion = isset($fecha_creacion[0]['value']) ? $fecha_creacion[0]['value'] : $fecha_creacion['value'];
    }
    if (is_array($fecha_vencimiento)) {
      $fecha_vencimiento = isset($fecha_vencimiento[0]['value']) ? $fecha_vencimiento[0]['value'] : $fecha_vencimiento['value'];
    }

    if (strtotime($fecha_vencimiento) === false) {
      $form_state->setError($element, t('La fecha de vencimiento no es válida.'));
    }
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

    $precio = $this->getProductoPrecio($producto_id);
    $impuesto = $this->getProductoImpuesto($producto_id);
    $importe = $precio * $cantidad;

    $productos[] = [
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

  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($values = $form_state->getValue('productos_agregados')) {
      $productos_updated = [];
      foreach ($values as $row) {
        $producto_id = $row['producto'];
        $cantidad = $row['cantidad'];
        $precio = $this->getProductoPrecio($producto_id);
        $impuesto = $this->getProductoImpuesto($producto_id);
        $importe = $precio * $cantidad * (1 + $impuesto / 100);
        $productos_updated[$producto_id] = [
          'producto_id' => $producto_id,
          'producto' => $this->getProductoOptions()[$producto_id],
          'cantidad' => $cantidad,
          'precio' => $precio,
          'impuesto' => $impuesto,
          'importe' => $importe,
        ];
      }
      $form_state->set('productos', $productos_updated);
    }

    parent::submitForm($form, $form_state);

    $num_pedido_aleatorio = $form_state->get('num_pedido_aleatorio');
    $user_value = $form_state->getValue('user_id');

    if (!empty($num_pedido_aleatorio)) {
      $this->entity->set('num_pedido', $num_pedido_aleatorio);
    }
    if (!empty($user_value)) {
      $this->entity->set('user_id', $user_value);
    }
    $num_pedido_aleatorio = rand(100000, 999999);
    $this->entity->set('num_pedido', $num_pedido_aleatorio);
    $this->entity->save();

    \Drupal::messenger()->addMessage($this->t('La factura ha sido guardada con los productos.'));
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


  private function getUserOptions() {
    $options = [];
    $users = \Drupal::entityTypeManager()->getStorage('user')->loadMultiple();
    foreach ($users as $user) {
      $options[$user->id()] = $user->getDisplayName();
    }
    return $options;
  }

  private function getProductoOptions() {
    $options = [];
    $productos = \Drupal::entityTypeManager()->getStorage('producto')->loadMultiple();
    foreach ($productos as $producto) {
      $options[$producto->id()] = $producto->get('nombre')->value;
    }
    return $options;
  }

  /**
   * Obtener el precio de un producto.
   */
  private function getProductoPrecio($producto_id) {
    $producto = \Drupal::entityTypeManager()->getStorage('producto')->load($producto_id);
    return $producto ? $producto->get('precio')->value : 0;
  }

  private function getProductoImpuesto($producto_id) {
    $producto = \Drupal::entityTypeManager()->getStorage('producto')->load($producto_id);

    if ($producto && $producto->hasField('id_impuesto')) {

      $id_impuesto = $producto->get('id_impuesto')->target_id;
      $impuesto = \Drupal::entityTypeManager()->getStorage('impuesto')->load($id_impuesto);

      return ($impuesto && $impuesto->hasField('valor')) ? $impuesto->get('valor')->value : 0;
    }
    return 0;
  }
}