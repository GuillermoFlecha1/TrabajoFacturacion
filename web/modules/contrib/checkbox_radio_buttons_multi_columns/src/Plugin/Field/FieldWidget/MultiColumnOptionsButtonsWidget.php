<?php

namespace Drupal\checkbox_radio_buttons_multi_columns\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsButtonsWidget;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'multi_column_options_buttons' widget.
 *
 * @FieldWidget(
 *   id = "multi_column_options_buttons",
 *   label = @Translation("Check boxes/radio buttons (multi-columns)"),
 *   field_types = {
 *     "boolean",
 *     "entity_reference",
 *     "list_integer",
 *     "list_float",
 *     "list_string",
 *   },
 *   multiple_values = TRUE
 * )
 */
class MultiColumnOptionsButtonsWidget extends OptionsButtonsWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'columns' => 1,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['columns'] = [
      '#type' => 'number',
      '#title' => $this->t('Columns'),
      '#description' => $this->t('Amount of columns the checkboxes should be divided on.'),
      '#default_value' => $this->getSetting('columns'),
      '#required' => TRUE,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = $this->t('Number of columns: @columns', ['@columns' => $this->getSetting('columns')]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    if ($element['#type'] === 'checkboxes') {
      $element['#checkbox_radio_buttons_multi_columns'] = $this->getSetting('columns');
    }
    return $element;
  }

}
