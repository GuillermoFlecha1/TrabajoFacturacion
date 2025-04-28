<?php

namespace Drupal\facturation\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Filtro personalizado para el Cliente.
 *
 * Permite buscar registros ingresando parte del nombre del cliente. Se busca el nombre en la tabla
 * de usuarios y se filtra por los uids correspondientes.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("facturation_cliente_filter")
 */
class FacturationClienteFilter extends FilterPluginBase {

  /**
   * Define las opciones de operador.
   *
   * En este caso se utiliza el operador "Contains" (LIKE).
   */
  public function operatorOptions() {
    return [
      'LIKE' => $this->t('Contains'),
    ];
  }

  /**
   * Construye el formulario expuesto para el filtro.
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state) {
    // Utilizamos como identificador el que se espera en la vista ("user_id").
    $exposed_id = !empty($this->options['exposed_identifier']) ? $this->options['exposed_identifier'] : 'user_id';
    
    $form[$exposed_id] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cliente'),
      '#default_value' => $this->value,
      '#description' => $this->t('Ejemplo: Guille, Ian o Admin'),
    ];
  }

  /**
   * Modifica la consulta de Views en función del filtro.
   */
  public function query() {
    // Obtener el valor ingresado y normalizarlo.
    $value = $this->value;
    if (is_array($value)) {
      $value = reset($value);
    }
    $value = trim((string) $value);
    if ($value === '' || $value === NULL) {
      return;
    }
  
    // Usar la conexión a la base de datos para escapar el LIKE.
    $escaped_value = \Drupal::database()->escapeLike($value);
  
    // Se realiza una consulta a la entidad 'user' para obtener los uids cuyos nombres contengan el texto.
    $uids = \Drupal::entityQuery('user')
      ->accessCheck(TRUE)
      ->condition('name', '%' . $escaped_value . '%', 'LIKE')
      ->execute();
  
    // Si no se encuentra ningún usuario, forzamos la condición a un uid imposible (por ejemplo, 0)
    if (empty($uids)) {
      $uids = [0];
    }
  
    // Asegurarse de tener el alias de la tabla donde se guarda el campo 'user_id'.
    $this->ensureMyTable();
    // Se asume que en la vista el campo es "user_id"; de lo contrario, se puede ajustar.
    $field = $this->realField;
  
    // Agregar la condición a la consulta: user_id debe estar en el array de $uids.
    $this->query->addWhere($this->options['group'], "$this->tableAlias.$field", $uids, 'IN');
  }  
}