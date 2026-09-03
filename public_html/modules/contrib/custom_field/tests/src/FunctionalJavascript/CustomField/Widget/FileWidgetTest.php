<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\FunctionalJavascript\CustomField\Widget;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\file\Entity\File;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\custom_field\Traits\CustomFieldTestTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * FunctionalJavascript tests for the file_generic widget.
 *
 * Covers AJAX-managed file upload and remove on the node form.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
class FileWidgetTest extends WebDriverTestBase {

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
    'file',
    'custom_field',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Path to a temporary .txt fixture.
   *
   * @var string
   */
  protected string $txtPath;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Page']);
    $body = FieldConfig::loadByName('node', 'page', 'body');
    $body?->delete();

    $admin = $this->drupalCreateUser([
      'administer content types',
      'administer node fields',
      'administer node form display',
      'access content',
      'create page content',
      'edit any page content',
    ]);
    $this->drupalLogin($admin);

    $this->txtPath = $this->publicFilesDirectory . '/js-test-upload.txt';
    file_put_contents($this->txtPath, 'javascript upload content');

    $this->createCustomField(
      'field_test',
      [
        'doc' => [
          'name' => 'doc',
          'type' => 'file',
          'uri_scheme' => 'public',
          'target_type' => 'file',
        ],
      ],
      [
        'doc' => [
          'label' => 'Document',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'file_directory' => 'custom-field-js-test',
          'file_extensions' => 'txt',
          'max_filesize' => '',
        ],
      ],
    );

    $this->setFormDisplay('field_test', [
      'doc' => [
        'type' => 'file_generic',
        'weight' => 0,
        'label' => 'Document',
        'progress_indicator' => 'throbber',
      ],
    ]);
  }

  /**
   * Sets form display for a custom field, including per-subfield widgets.
   *
   * Mirrors CustomFieldFunctionalTestBase::setFormDisplay().
   *
   * @param string $field_name
   *   Field machine name.
   * @param array $subfield_widgets
   *   Keyed by subfield name with widget settings.
   * @param string $widget_type
   *   Overall widget plugin id (custom_stacked or custom_flex).
   * @param array $widget_settings
   *   Overall widget settings.
   * @param string $bundle
   *   Content type bundle.
   * @param string $mode
   *   Form mode.
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
   * Tests AJAX file upload then node save.
   */
  public function testAjaxFileUploadAndSave(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $page = $this->getSession()->getPage();
    $page->fillField('title[0][value]', 'JS file upload node');

    // With JS enabled, managed_file auto-uploads on file select (the Upload
    // button is js-hide). Attach and wait for the AJAX rebuild + filename.
    $assert->elementExists('css', 'input[type="file"][name^="files[field_test_0_doc"]');
    $page->attachFileToField('files[field_test_0_doc]', $this->txtPath);
    $assert->assertWaitOnAjaxRequest();
    $assert->waitForText('js-test-upload.txt');

    $page->pressButton('Save');
    $assert->waitForText('JS file upload node');

    $node = $this->loadNodeByTitle('JS file upload node');
    $fid = $node->get('field_test')->doc;
    $this->assertNotEmpty($fid);
    $file = File::load($fid);
    $this->assertNotNull($file);
    $this->assertStringContainsString('js-test-upload', $file->getFilename());
  }

}
