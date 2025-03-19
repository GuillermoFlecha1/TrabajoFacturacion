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
    
    $productos = $form_state->get('productos') ?? [];
    $form['producto_cantidad_container'] = [
      '#type' => 'container',
      '#attributes' => ['style' => 'display: flex; align-items: center; gap: 10px;'],
    ];

    $form['producto_cantidad_container']['producto_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Producto'),
      '#options' => $this->getProductoOptions(),
      '#required' => TRUE,
    ];

    $form['producto_cantidad_container']['cantidad'] = [
      '#type' => 'number',
      '#title' => $this->t('Cantidad'),
      '#min' => 1,
      '#default_value' => 1,
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
      $importe = $producto['importe'];
      $impuesto_total_producto = ($importe * $producto['impuesto']) / 100;

      $lista_productos .= '<tr>
        <td>' . $producto['producto'] . '</td>
        <td>' . $producto['cantidad'] . '</td>
        <td>' . number_format($producto['precio'], 2) . '</td>
        <td>' . $producto['impuesto'] . '%</td>
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

    $precio = $this->getProductoPrecio($producto_id);
    $impuesto = $this->getProductoImpuesto($producto_id);
    $importe = $precio * $cantidad;

    // Agregar producto con precio e importe calculado
    $productos[$producto_id] = [
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

    // Obtener los valores del formulario.
    $user_value = $form_state->getValue('user_id');
    $productos = $form_state->get('productos') ?? [];

    $total_importe = 0;
    $total_impuesto = 0;
    $cantidad_total = 0;

    // Calcular totales
    foreach ($productos as $producto) {
        $total_importe += $producto['importe'];
        $total_impuesto += ($producto['importe'] * $producto['impuesto']) / 100;
        $cantidad_total += $producto['cantidad'];
      }

    $total_final = $total_importe + $total_impuesto;
    
    if (!empty($num_pedido_aleatorio)) {
      $this->entity->set('num_pedido', $num_pedido_aleatorio);
    }
    
    if (!empty($user_value)) {
      $this->entity->set('user_id', $user_value);
    }

    $this->entity->set('cantidad', $cantidad_total);
    $num_pedido_aleatorio = rand(100000, 999999); 
    $this->entity->set('num_pedido', $num_pedido_aleatorio);
    
    // Guardar el Total Final en la entidad sin mostrarlo en el formulario
    $this->entity->set('total_final', $total_final);

    $this->entity->save();

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
