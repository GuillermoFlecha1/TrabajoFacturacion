<?php

namespace Drupal\Tests\checkbox_radio_buttons_multi_columns\Kernel;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests the multi_column_options_buttons widget.
 *
 * @group checkbox_radio_buttons_multi_columns
 * @see \Drupal\checkbox_radio_buttons_multi_columns\Plugin\Field\FieldWidget\MultiColumnOptionsButtonsWidget
 */
class MultiColumnOptionsbuttonsTest extends FieldKernelTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'checkbox_radio_buttons_multi_columns',
    'field',
    'node',
    'options',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('node', 'node_access');
    $this->installEntitySchema('node');
    $this->installConfig(['field', 'node']);

    NodeType::create([
      'type' => 'node_test_type',
      'name' => $this->randomString(),
    ])->save();

    $fieldStorageDefinition = [
      'field_name' => 'test_multi_column_options',
      'entity_type' => 'node',
      'type' => 'list_integer',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'settings' => [
        'allowed_values' => [1 => 'One', 2 => 'Two', 3 => 'Three'],
      ],
    ];
    $fieldStorage = FieldStorageConfig::create($fieldStorageDefinition);
    $fieldStorage->save();

    FieldConfig::create([
      'field_storage' => $fieldStorage,
      'bundle' => 'node_test_type',
    ])->save();
  }

  /**
   * Tests the widget render-processing logic.
   */
  public function testFormElementCheckboxes() {
    // Test Form element rendering from an array.
    $columnsNumber = rand(20, 100);
    $test_element = [
      '#type' => 'checkboxes',
      '#field_name' => 'test_multi_column_options',
      '#checkbox_radio_buttons_multi_columns' => $columnsNumber,
    ];
    $output = \Drupal::service('renderer')->renderRoot($test_element);
    $this->assertMatchesRegularExpression('/"column-count: *' . $columnsNumber . '"/', $output);

    // Test Form element rendering from the node form.
    $user = $this->createUser([], $this->randomString());
    $node = $this->createNode(['type' => 'node_test_type', 'uid' => $user->id()]);
    $columnsNumber = rand(20, 100);
    \Drupal::service('entity_display.repository')
      ->getFormDisplay('node', 'node_test_type')
      ->setComponent('test_multi_column_options', [
        'type' => 'multi_column_options_buttons',
        'settings' => ['columns' => $columnsNumber],
      ])
      ->save();
    $node_form = \Drupal::service('entity.form_builder')->getForm($node);
    $this->assertArrayHasKey('test_multi_column_options', $node_form);
    $output = \Drupal::service('renderer')->renderRoot($node_form['test_multi_column_options']);
    $this->assertMatchesRegularExpression('/"column-count: *' . $columnsNumber . '"/', $output);

    // Change the number of columns and test again.
    $columnsNumber = rand(20, 100);
    \Drupal::service('entity_display.repository')
      ->getFormDisplay('node', 'node_test_type')
      ->setComponent('test_multi_column_options', [
        'type' => 'multi_column_options_buttons',
        'settings' => ['columns' => $columnsNumber],
      ])
      ->save();
    $node_form = \Drupal::service('entity.form_builder')->getForm($node);
    $this->assertArrayHasKey('test_multi_column_options', $node_form);
    $output = \Drupal::service('renderer')->renderRoot($node_form['test_multi_column_options']);
    $this->assertMatchesRegularExpression('/"column-count: *' . $columnsNumber . '"/', $output);

  }

}
