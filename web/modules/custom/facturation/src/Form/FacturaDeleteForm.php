<?php
 
 namespace Drupal\facturation\Form;
 
 use Drupal\Core\Entity\ContentEntityDeleteForm;
 
 /**
  * Proporciona un formulario de confirmación para eliminar la entidad Factura.
  */
 class FacturaDeleteForm extends ContentEntityDeleteForm {
   
   /**
    * Personaliza el mensaje de confirmación al eliminar una factura.
    */
   public function getQuestion() {
     return $this->t('¿Estás seguro de que deseas eliminar la factura "%name"?', [
       '%name' => $this->getEntity()->label(),
     ]);
   }
 }