<?php
 
 namespace Drupal\facturation\Entity;
 
 use Drupal\Core\Entity\EntityInterface;
 use Drupal\Core\Entity\EntityListBuilder;
 use Drupal\Core\Link;
 use Drupal\Core\Url;
 
 /**
  * Define el ListBuilder para las entidades de tipo Factura.
  */
 class FacturasListBuilder extends EntityListBuilder {
 
   /**
    * {@inheritdoc}
    */
   public function buildHeader() {
     $header['id'] = $this->t('ID');
     $header['num_pedido'] = $this->t('Número de Pedido');
     $header['fecha_creacion'] = $this->t('Fecha de Creación');
     $header['fecha_vencimiento'] = $this->t('Fecha de Vencimiento');
     $header['usuario'] = $this->t('Usuario');
     $header['producto'] = $this->t('Producto');
     $header['cantidad'] = $this->t('Cantidad');
     $header['total_final'] = $this->t('Total Final');
     $header['acciones'] = $this->t('Acciones');
     
     return $header;
   }
 
   /**
    * {@inheritdoc}
    */
   public function buildRow(EntityInterface $entity) {
     if (!$entity) {
       return;
     }
   
     // Define una fila para cada factura.
     $row['id'] = $entity->id();
     $row['num_pedido'] = $entity->get('num_pedido')->value;
   
     // Obtén las fechas como objetos DateTime.
     $fecha_creacion = $entity->get('fecha_creacion')->date;
     $fecha_vencimiento = $entity->get('fecha_vencimiento')->date;
   
     // Muestra solo la fecha (año-mes-día) sin la hora.
     $row['fecha_creacion'] = $fecha_creacion ? $fecha_creacion->format('Y-m-d') : $this->t('Fecha no disponible');
     $row['fecha_vencimiento'] = $fecha_vencimiento ? $fecha_vencimiento->format('Y-m-d') : $this->t('Fecha no disponible');
   
     // Obtener la información del usuario asociado.
     $usuario = $entity->get('user_id')->entity;
     if ($usuario) {
       $row['usuario'] = $usuario->toLink()->toString();
     }
     else {
       $row['usuario'] = $this->t('No asignado');
     }
   
     // Obtener la información del producto asociado.
     $producto = $entity->get('producto_id')->entity;
     if ($producto) {
       $row['producto'] = $producto->toLink()->toString();
     }
     else {
       $row['producto'] = $this->t('No asignado');
     }
   
     // Mostrar la cantidad de productos.
     $row['cantidad'] = $entity->get('cantidad')->value;
   
     // Mostrar el total final.
     $row['total_final'] = $entity->get('total_final')->value;
   
     // Enlaces de acciones.
     $edit_url = Url::fromRoute('facturas.edit_form', ['facturas' => $entity->id()]);
     $delete_url = Url::fromRoute('facturas.delete_form', ['facturas' => $entity->id()]);
     $row['acciones'] = [
       'data' => [
         Link::fromTextAndUrl($this->t('Editar'), $edit_url)->toRenderable(),
         ['#markup' => ' | '],
         Link::fromTextAndUrl($this->t('Eliminar'), $delete_url)->toRenderable(),
       ],
     ];
   
     return $row;
   }
   
 
   /**
    * {@inheritdoc}
    */
   public function render() {
     // Verifica si el botón de agregar factura se está mostrando correctamente
     $build['add_button'] = [
       '#type' => 'link',
       '#title' => $this->t('Agregar Factura'),
       '#url' => Url::fromRoute('facturas.add_form'),
       '#attributes' => [
         'class' => ['button', 'button--primary'],
         'style' => 'margin-bottom: 10px; display: inline-block;',
       ],
     ];
   
     // Llama al render de la clase base y agrega el botón de 'Agregar Factura'.
     $build += parent::render();
     return $build;
   } 
 }