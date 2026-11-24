<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\FunctionalJavascript\CustomField\Widget;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\file\Entity\File;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\custom_field\Traits\CustomFieldTestTrait;
use Drupal\Tests\TestFileCreationTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * FunctionalJavascript tests for the image_image widget.
 *
 * Covers AJAX upload, alt/title fields appearing after upload, optional
 * preview, and persist of fid + alt/title.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
class ImageWidgetTest extends WebDriverTestBase {

  use CustomFieldTestTrait;
  use TestFileCreationTrait;

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
    'image',
    'custom_field',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Path to a temporary image fixture.
   *
   * @var string
   */
  protected string $imagePath;

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

    $images = $this->getTestFiles('image');
    $this->assertNotEmpty($images);
    $this->imagePath = $this->container->get('file_system')
      ->realpath($images[0]->uri);

    $this->createCustomField(
      'field_test',
      [
        'photo' => [
          'name' => 'photo',
          'type' => 'image',
          'uri_scheme' => 'public',
          'target_type' => 'file',
        ],
      ],
      [
        'photo' => [
          'label' => 'Photo',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'file_directory' => 'custom-field-js-images',
          'file_extensions' => 'png gif jpg jpeg webp',
          'max_filesize' => '',
          'max_resolution' => '',
          'min_resolution' => '',
          'alt_field' => 1,
          'alt_field_required' => 0,
          'title_field' => 1,
          'title_field_required' => 0,
        ],
      ],
    );

    $this->setFormDisplay('field_test', [
      'photo' => [
        'type' => 'image_image',
        'weight' => 0,
        'label' => 'Photo',
        'progress_indicator' => 'throbber',
        'preview_image_style' => 'thumbnail',
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
   * Tests AJAX image upload, alt/title, and save.
   */
  public function testAjaxImageUploadAltTitleAndSave(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $page = $this->getSession()->getPage();
    $page->fillField('title[0][value]', 'JS image upload node');

    // With JS enabled, managed_file auto-uploads on file select (the Upload
    // button is js-hide). Attach and wait for the AJAX rebuild + filename.
    $assert->elementExists('css', 'input[type="file"][name^="files[field_test_0_photo"]');
    $page->attachFileToField('files[field_test_0_photo]', $this->imagePath);
    $assert->assertWaitOnAjaxRequest();

    $filename = basename($this->imagePath);
    $assert->waitForText($filename);

    // Alt/title appear after a successful upload (#access requires #files).
    $assert->waitForField('field_test[0][photo][alt]');
    $assert->fieldExists('field_test[0][photo][alt]');
    $assert->fieldExists('field_test[0][photo][title]');

    $page->fillField('field_test[0][photo][alt]', 'Accessible alt');
    $page->fillField('field_test[0][photo][title]', 'Hover title');

    // Thumbnail preview when preview_image_style is set.
    $assert->elementExists('css', 'img');

    $page->pressButton('Save');
    $assert->waitForText('JS image upload node');

    $node = $this->loadNodeByTitle('JS image upload node');
    $fid = $node->get('field_test')->photo;
    $this->assertNotEmpty($fid);
    $this->assertNotNull(File::load($fid));
    $this->assertEquals('Accessible alt', $node->get('field_test')->photo__alt);
    $this->assertEquals('Hover title', $node->get('field_test')->photo__title);
  }

}
