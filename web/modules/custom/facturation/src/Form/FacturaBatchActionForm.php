<?php

namespace Drupal\facturation\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class FacturaBatchActionForm extends FormBase {

  public function getFormId() {
    return 'factura_batch_action_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    // Elemento select para la acción en lote.
    $form['batch_action'] = [
      '#type' => 'select',
      '#title' => $this->t('Acción en lote'),
      '#options' => [
        '' => $this->t('-- Seleccionar --'),
        'delete' => $this->t('Eliminar'),
      ],
      '#required' => TRUE,
    ];
  
    // Campo oculto para almacenar los IDs seleccionados.
    $form['selected_invoices'] = [
      '#type' => 'hidden',
      '#attributes' => ['id' => 'edit-selected-invoices'],
      '#value' => '',
    ];
  
    // Botón submit.
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Aplicar'),
    ];
  
    // Se adjunta la librería para el JS, que en este caso puede ser la misma que usamos en checkbox_selector.
    $form['#attached']['library'][] = 'facturation/checkbox_selector';
  
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $selected = $form_state->getValue('selected_invoices');
    if (empty($selected)) {
      \Drupal::messenger()->addError($this->t('No seleccionaste ninguna factura.'));
      return;
    }
    // Convertir la cadena de IDs en array.
    $ids = array_filter(array_map('trim', explode(',', $selected)));

    if ($form_state->getValue('batch_action') === 'delete') {
      foreach ($ids as $id) {
         $entity = \Drupal::entityTypeManager()->getStorage('factura')->load($id);
         if ($entity) {
           $entity->delete();
         }
      }
      \Drupal::messenger()->addMessage($this->t('Se eliminaron @count facturas.', ['@count' => count($ids)]));
    }
  }
}
