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
/*
    $form['num_pedido']['#weight'] = 1;
    $form['fecha_creacion']['#weight'] = 2;
    $form['fecha_vencimiento']['#weight'] = 3;
    $form['user_id']['#weight'] = 4;
    $form['producto_cantidad_container']['#weight'] = 5;
    $form['productos_seleccionados']['#weight'] = 6;
    $form['total_final']['#weight'] = 7;
*/
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
    
    // Inicializar productos seleccionados
    $productos_seleccionados = $form_state->get('productos_seleccionados') ?? [];
    $form_state->set('productos_seleccionados', $productos_seleccionados);

    // Contenedor para seleccionar productos y cantidad
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
      '#ajax' => [
        'callback' => '::actualizarTablaProductos',
        'wrapper' => 'tabla-productos',
      ],
      '#attributes' => ['style' => 'margin-left: auto;'],
    ];

    // Tabla de productos seleccionados
    $form['productos_seleccionados'] = [
      '#type' => 'container',
      '#prefix' => '<div id="tabla-productos">',
      '#suffix' => '</div>',
    ];

    if (!empty($productos_seleccionados)) {
      $form['productos_seleccionados']['tabla'] = [
        '#type' => 'table',
        '#header' => [$this->t('Producto'), $this->t('Cantidad'), $this->t('Precio Unitario'), $this->t('Total')],
        '#rows' => [],
      ];

      foreach ($productos_seleccionados as $producto) {
        $form['productos_seleccionados']['tabla']['#rows'][] = [
          'nombre' => $producto['nombre'],
          'cantidad' => $producto['cantidad'],
          'precio_unitario' => $producto['precio'],
          'total' => $producto['cantidad'] * $producto['precio'],
        ];
      }
    }

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

  public function agregarProducto(array &$form, FormStateInterface $form_state) {
    $productos_seleccionados = $form_state->get('productos_seleccionados');
    if (!is_array($productos_seleccionados)) {
      $productos_seleccionados = [];
    }
  
    $producto_id = $form_state->getValue(['producto_cantidad_container', 'producto_id']);
    $cantidad = $form_state->getValue(['producto_cantidad_container', 'cantidad']);
  
    if (!empty($producto_id) && !empty($cantidad) && $cantidad > 0) {
      $productos_seleccionados[] = [
        'producto_id' => $producto_id,
        'nombre' => $this->getProductoOptions()[$producto_id] ?? 'Desconocido',
        'cantidad' => $cantidad,
        'precio' => $this->getProductoPrecio($producto_id),
      ];
    }
  
    $form_state->set('productos_seleccionados', $productos_seleccionados);
    $form_state->setRebuild(TRUE);
  }
  private function getProductoPrecio($producto_id) {
    $producto = \Drupal::entityTypeManager()->getStorage('producto')->load($producto_id);
    return $producto ? $producto->get('precio')->value : 0;
  }

  public function actualizarTablaProductos(array &$form, FormStateInterface $form_state) {
    return $form['productos_seleccionados'];
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    
    //$productos = $form_state->get('productos_seleccionados') ?? [];
    $num_pedido_aleatorio = $form_state->get('num_pedido_aleatorio');

    // Obtener los valores del formulario.
    $user_value = $form_state->getValue('user_id');
    //$producto_value = $form_state->getValue('producto_id');
    if (!empty($num_pedido_aleatorio)) {
      $this->entity->set('num_pedido', $num_pedido_aleatorio);
    }
    /*if (!empty($producto_value)) {
      $this->entity->set('producto_id', $producto_value);
    }*/
    if (!empty($user_value)) {
      $this->entity->set('user_id', $user_value);
    }
    $num_pedido_aleatorio = rand(100000, 999999); 
    $this->entity->set('num_pedido', $num_pedido_aleatorio);
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
    
}
