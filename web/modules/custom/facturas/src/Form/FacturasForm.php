<?php

namespace Drupal\facturas\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear y editar la entidad Facturas.
 */
class FacturasForm extends ContentEntityForm {

  /**
   * Construcción del formulario.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Llamamos al método de la clase base para construir el formulario.
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;

    // Establecer el número de pedido si es un nuevo pedido.
    if ($entity->isNew() && empty($entity->get('num_pedido')->value)) {
      $entity->set('num_pedido', $this->generateRandomOrderNumber());
    }

    // Mostrar el número de pedido como un label no editable.
    $form['num_pedido'] = [
      '#type' => 'item',
      '#title' => $this->t('Número de Pedido'),
      '#markup' => $entity->get('num_pedido')->value,
      '#weight' => -2,
    ];

    // Cargar productos desde la base de datos.
    $productos = \Drupal::entityTypeManager()
      ->getStorage('producto')
      ->loadMultiple();

    $producto_options = [];
    foreach ($productos as $producto) {
      $producto_options[$producto->id()] = $producto->label();
    }

    // Obtener el ID del producto si ya existe en la entidad.
    $id_producto_value = $entity->get('id_producto')->target_id ?? '';

    // Modificar el campo id_producto para usar un select en lugar de autocompletar.
    $form['id_producto'] = [
      '#type' => 'select',
      '#title' => $this->t('Producto'),
      '#options' => $producto_options,
      '#default_value' => $id_producto_value,
      '#required' => TRUE,
      '#description' => $this->t('Seleccione un producto relacionado con la factura.'),
      '#weight' => -1,
    ];

    // Agregar el campo Fecha de Creación.
    $form['fecha_creacion'] = [
      '#type' => 'date',
      '#title' => $this->t('Fecha de Creación'),
      '#default_value' => date('Y-m-d'),
      '#description' => $this->t('Fecha en que se generó la factura.'),
      '#weight' => 0,
    ];

    // Calcular la fecha de vencimiento (2 meses después de la fecha actual).
    $fecha_vencimiento_default = date('Y-m-d', strtotime('+2 months'));

    // Agregar el campo Fecha de Vencimiento con fecha por defecto calculada.
    $form['fecha_vencimiento'] = [
      '#type' => 'date',
      '#title' => $this->t('Fecha de Vencimiento'),
      '#default_value' => $fecha_vencimiento_default,
      '#description' => $this->t('Fecha límite de pago de la factura.'),
      '#weight' => 1,
    ];

    // Cargar usuarios desde la base de datos.
    $usuarios = \Drupal::entityTypeManager()
      ->getStorage('user')
      ->loadMultiple();

    $usuario_options = [];
    foreach ($usuarios as $usuario) {
      $usuario_options[$usuario->id()] = $usuario->getDisplayName();
    }

    // Obtener el ID del usuario si ya existe en la entidad.
    $id_usuario_value = $entity->get('id_user')->target_id ?? '';

    // Modificar el campo id_user para usar un select en lugar de autocompletar.
    $form['id_user'] = [
      '#type' => 'select',
      '#title' => $this->t('Usuario'),
      '#options' => $usuario_options,
      '#default_value' => $id_usuario_value,
      '#required' => TRUE,
      '#description' => $this->t('Seleccione el usuario relacionado con la factura.'),
      '#weight' => -1,
    ];
    
    // Agregar el campo Total Precio como un label informativo.
    $form['total_precio_calculado'] = [
      '#type' => 'item',
      '#title' => $this->t('Total Precio'),
      '#markup' => $this->t('El precio total se calculará en un futuro.'),
      '#weight' => 1,
    ];

    return $form;
  }

  /**
   * Genera un número de pedido aleatorio.
   */
  private function generateRandomOrderNumber() {
    return (string) rand(10000, 99999);
  }

  /**
   * Validación del formulario.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Obtener las fechas.
    $fecha_creacion = $form_state->getValue('fecha_creacion');
    $fecha_vencimiento = $form_state->getValue('fecha_vencimiento');

    // Validar que la fecha de vencimiento no sea anterior o igual a la fecha de creación.
    if ($fecha_vencimiento <= $fecha_creacion) {
      $form_state->setErrorByName('fecha_vencimiento', $this->t('La fecha de vencimiento no puede ser anterior o igual a la fecha de creación.'));
    }
  }

  /**
   * Guardado del formulario.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    \Drupal::messenger()->addMessage($this->t('La factura ha sido guardada correctamente.'));
  }
}