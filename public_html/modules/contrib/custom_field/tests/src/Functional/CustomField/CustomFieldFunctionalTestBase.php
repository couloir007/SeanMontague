<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Functional\CustomField;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\custom_field\Traits\CustomFieldTestTrait;

/**
 * Base class for Custom Field plugin functional tests.
 */
abstract class CustomFieldFunctionalTestBase extends BrowserTestBase {

  use CustomFieldTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'field_ui',
    'node',
    'custom_field',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with permission to administer content types and fields.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Base path for the field's path in the settings form.
   */
  protected const FIELD_PATH = 'fields[field_test][settings_edit_form][settings][fields]';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Page']);

    // Avoid TextareaWithSummaryWidget settingsSummary() NULL @rows noise
    // on Manage form display (default Body field).
    $body = FieldConfig::loadByName('node', 'page', 'body');
    $body?->delete();

    $this->adminUser = $this->drupalCreateUser([
      'administer content types',
      'administer node fields',
      'administer node form display',
      'administer node display',
      'access content',
      'create page content',
      'edit any page content',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Sets form display for a custom field, including per-subfield widgets.
   *
   * @param string $field_name
   *   Field machine name.
   * @param array $subfield_widgets
   *   Keyed by subfield name. Example:
   *   [
   *     'title' => [
   *       'type' => 'text',
   *       'weight' => 0,
   *       'label' => 'Title',
   *       'size' => 60,
   *       'placeholder' => 'Enter a title',
   *       'maxlength' => 255,
   *     ],
   *   ].
   * @param string $widget_type
   *   Overall widget: custom_stacked or custom_flexbox.
   * @param array $widget_settings
   *   Overall widget settings (wrapper, open, etc.).
   * @param string $bundle
   *   The content bundle type.
   * @param string $mode
   *   The display mode.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   An exception thrown if display doesn't create.
   */
  protected function setFormDisplay(
    string $field_name,
    array $subfield_widgets,
    string $widget_type = 'custom_stacked',
    array $widget_settings = [],
    string $bundle = 'page',
    string $mode = 'default',
  ): void {
    $form_display = EntityFormDisplay::load("node.{$bundle}.{$mode}");
    if (!$form_display) {
      $form_display = EntityFormDisplay::create([
        'targetEntityType' => 'node',
        'bundle' => $bundle,
        'mode' => $mode,
        'status' => TRUE,
      ]);
    }

    $settings = $widget_settings + [
      'wrapper' => 'details',
      'open' => TRUE,
      'fields' => $subfield_widgets,
    ];

    $form_display->setComponent($field_name, [
      'type' => $widget_type,
      'weight' => 10,
      'region' => 'content',
      'settings' => $settings,
    ])->save();
  }

  /**
   * Sets view display for a custom field.
   *
   * @param string $field_name
   *   Field machine name.
   * @param array $subfield_formatters
   *   Keyed by subfield name. Matches exported form display shape, e.g.:
   *   [
   *     'title' => [
   *       'weight' => 0,
   *       'format_type' => 'string',
   *       'wrappers' => [
   *         'field_wrapper_tag' => '',
   *         'field_wrapper_classes' => '',
   *         'field_tag' => '',
   *         'field_classes' => '',
   *         'label_tag' => '',
   *         'label_classes' => '',
   *       ],
   *       'formatter_settings' => [
   *         'key_label' => 'label',
   *         'label_display' => 'above',
   *         'field_label' => '',
   *         'prefix_suffix' => FALSE,
   *       ],
   *     ],
   *   ].
   * @param string $formatter_type
   *   The formatter type.
   * @param string $label
   *   The label position.
   * @param string $bundle
   *   The content bundle type.
   * @param string $mode
   *   The display mode.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown if the view display fails to save.
   */
  protected function setViewDisplay(
    string $field_name,
    array $subfield_formatters,
    string $formatter_type = 'custom_formatter',
    string $label = 'above',
    string $bundle = 'page',
    string $mode = 'default',
  ): void {
    $view_display = EntityViewDisplay::load("node.{$bundle}.{$mode}");
    if (!$view_display) {
      $view_display = EntityViewDisplay::create([
        'targetEntityType' => 'node',
        'bundle' => $bundle,
        'mode' => $mode,
        'status' => TRUE,
      ]);
    }

    $view_display->setComponent($field_name, [
      'type' => $formatter_type,
      'label' => $label,
      'weight' => 10,
      'region' => 'content',
      'settings' => [
        'fields' => $subfield_formatters,
      ],
    ])->save();
  }

}
