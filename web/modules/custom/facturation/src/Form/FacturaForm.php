<?php

namespace Drupal\facturation\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

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
      '#attributes' => ['style' => 'margin-top: 40px;'],
    ];

    // Mostrar los productos agregados
    $lista_productos = '<table border="1">
      <tr><th>Producto</th><th>Cantidad</th><th>Precio (€)</th><th>Impuesto (%)</th><th>Importe (€)</th></tr>';
    
    $total_importe = 0;
    $total_impuesto = 0;

    foreach ($productos as $producto) {
      $precio = $this->getProductoPrecio($producto['producto_id']);
      $impuesto = $this->getProductoImpuesto($producto['producto_id']);
      $importe = $precio * $producto['cantidad'];
      $impuesto_total_producto = ($importe * $impuesto) / 100;

      $lista_productos .= '<tr>
        <td>' . $producto['producto'] . '</td>
        <td>' . $producto['cantidad'] . '</td>
        <td>' . number_format($precio, 2) . '</td>
        <td>' . $impuesto . '%</td>
        <td>' . number_format($importe, 2) . '</td>
      </tr>';
      $total_importe += $importe;
      $total_impuesto += $impuesto_total_producto;
    }

    $total_final = $total_importe + $total_impuesto;

    $lista_productos .= '<tr><td colspan="4"><b>Total Importe</b></td><td><b>' . number_format($total_importe, 2) . '€</b></td></tr>';
    $lista_productos .= '<tr><td colspan="4"><b>Total Impuesto</b></td><td><b>' . number_format($total_impuesto, 2) . '€</b></td></tr>';
    $lista_productos .= '<tr><td colspan="4"><b>Total Final</b></td><td><b>' . number_format($total_final, 2) . '€</b></td></tr>';
    $lista_productos .= '</table>';

    $form['productos_agregados'] = [
      '#type' => 'markup',
      '#markup' => $lista_productos,
    ];


    // --- Fecha de vencimiento ---
    if (isset($form['fecha_vencimiento'])) {
      $form['fecha_vencimiento']['#element_validate'][] = [$this, 'validateFechaVencimiento'];
    }
    return $form;
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

  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $num_pedido_aleatorio = $form_state->get('num_pedido_aleatorio');
    $user_value = $form_state->getValue('user_id');

    // Obtener instancia de la factura y asignar valores ANTES de guardar
    $factura = $this->entity;

    if (!empty($num_pedido_aleatorio)) {
        $factura->set('num_pedido', $num_pedido_aleatorio);
    }

    if (!empty($user_value)) {
        $factura->set('user_id', $user_value);
    }

    $factura->save(); // Guardar la factura con sus datos antes de insertar productos
    $factura_id = $factura->id();
    
    // Obtener los productos desde el estado del formulario
    $productos = $form_state->get('productos') ?? [];

    // Eliminar productos existentes antes de insertar nuevos (para edición)
    \Drupal::database()->delete('factura_productos')
      ->condition('factura_id', $factura_id)
      ->execute();

    $total_importe = 0;
    $total_impuesto = 0;

    foreach ($productos as $producto) {
        if (!empty($producto['producto_id']) && $producto['cantidad'] > 0) {
            $factura_producto = \Drupal::entityTypeManager()->getStorage('factura_producto')->create([
                'factura_id' => $factura_id,
                'producto_id' => $producto['producto_id'],
                'cantidad' => $producto['cantidad'],
            ]);
            $factura_producto->save();

            // Calcular los totales
            $total_importe += $producto['importe'];
            $total_impuesto += ($producto['importe'] * $producto['impuesto']) / 100;
        } else {
            \Drupal::messenger()->addError(t('Error al agregar producto: ID o cantidad inválida.'));
        }
    }

    $total_final = $total_importe + $total_impuesto;

    // Actualizar total_final y guardar nuevamente solo si ha cambiado
    if ($factura->get('total_final')->value != $total_final) {
        $factura->set('total_final', $total_final);
        $factura->save(); // Ahora sí, solo guardamos una segunda vez si es necesario
    }

    \Drupal::messenger()->addMessage($this->t('La factura ha sido guardada con los productos.'));
}


  private function getUserOptions() {
    $options = [];
    $users = \Drupal::entityTypeManager()->getStorage('user')->loadMultiple();
    foreach ($users as $user) {
      $options[$user->id()] = $user->getDisplayName();
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
