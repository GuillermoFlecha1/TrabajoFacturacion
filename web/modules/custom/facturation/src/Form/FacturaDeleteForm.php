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

    $factura_label = $this->getEntity()->id() ?: $this->t("factura sin nombre");
     return $this->t('¿Estás seguro de que deseas eliminar la factura ?', [
       '%name' => $factura_label,
     ]);
   }
 }