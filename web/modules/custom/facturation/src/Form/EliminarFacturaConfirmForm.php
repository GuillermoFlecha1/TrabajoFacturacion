<?php

namespace Drupal\facturation\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Formulario de confirmación para eliminar facturas.
 */
class EliminarFacturaConfirmForm extends ConfirmFormBase {

  /**
   * IDs de las facturas a eliminar.
   *
   * @var array
   */
  protected $facturaIds = [];

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'eliminar_factura_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->formatPlural(
      count($this->facturaIds),
      '¿Está seguro de que desea eliminar esta factura?',
      '¿Está seguro de que desea eliminar estas @count facturas?'
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('view.listafacturas.page_1');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Esta acción no se puede deshacer.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Eliminar');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, array $selection = NULL) {
    $this->facturaIds = $selection;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (!empty($this->facturaIds)) {
      $database = \Drupal::database();
      $database->delete('facturas')
        ->condition('id', $this->facturaIds, 'IN')
        ->execute();
      
      $this->messenger()->addStatus($this->formatPlural(
        count($this->facturaIds),
        'Se ha eliminado 1 factura.',
        'Se han eliminado @count facturas.'
      ));
    }
    
    $form_state->setRedirect('view.listafacturas.page_1');
  }
}