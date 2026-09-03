<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field_media\FunctionalJavascript\Widget;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\media\Entity\Media;
use Drupal\Tests\custom_field\Traits\CustomFieldTestTrait;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\TestFileCreationTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * FunctionalJavascript tests for media_library_widget.
 *
 * Covers opening the library modal, inserting a selection, and remove AJAX.
 *
 * @group custom_field
 * @group custom_field_media
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[Group('custom_field_media')]
#[RunTestsInSeparateProcesses]
class MediaLibraryWidgetTest extends WebDriverTestBase {

  use CustomFieldTestTrait;
  use MediaTypeCreationTrait;
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
    'media',
    'media_library',
    'views',
    'custom_field',
    'custom_field_media',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * An image media entity.
   *
   * @var \Drupal\media\MediaInterface
   */
  protected $media;

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
      'administer media',
      'view media',
      'view own unpublished media',
      'create media',
      'update media',
      'update any media',
      'access media overview',
    ]);
    $this->drupalLogin($admin);

    $this->createMediaType('image', ['id' => 'image', 'label' => 'Image']);

    $file = $this->getTestFiles('image')[0];
    $file_entity = $this->container->get('file.repository')->writeData(
      file_get_contents($file->uri),
      'public://js-media-test.png'
    );
    $this->media = Media::create([
      'bundle' => 'image',
      'name' => 'JS Test image media',
      'field_media_image' => [
        'target_id' => $file_entity->id(),
        'alt' => 'JS test alt',
      ],
      'status' => 1,
    ]);
    $this->media->save();

    $this->createCustomField(
      'field_test',
      [
        'media_ref' => [
          'name' => 'media_ref',
          'type' => 'entity_reference',
          'target_type' => 'media',
        ],
      ],
      [
        'media_ref' => [
          'label' => 'Media reference',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'handler' => 'default:media',
          'handler_settings' => [
            'target_bundles' => ['image' => 'image'],
            'sort' => ['field' => '_none', 'direction' => 'ASC'],
            'auto_create' => FALSE,
            'auto_create_bundle' => '',
          ],
        ],
      ],
    );

    $this->setFormDisplay('field_test', [
      'media_ref' => [
        'type' => 'media_library_widget',
        'weight' => 0,
        'label' => 'Media reference',
        'media_types' => [],
      ],
    ]);
  }

  /**
   * Sets form display for a custom field, including per-subfield widgets.
   *
   * @param string $field_name
   *   Field machine name.
   * @param array $subfield_widgets
   *   Keyed by subfield name with widget settings.
   * @param string $widget_type
   *   Overall widget plugin id.
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
   * Tests opening the media library modal.
   */
  public function testOpenMediaLibraryModal(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $page = $this->getSession()->getPage();
    $page->pressButton('Add media');
    $assert->assertWaitOnAjaxRequest();

    $assert->waitForElement('css', '.ui-dialog-content');
    $assert->elementExists('css', '.media-library-widget-modal, .ui-dialog .media-library-wrapper, .ui-dialog-content');
  }

  /**
   * Tests selecting media from the library and saving the node.
   */
  public function testSelectMediaAndSave(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $page = $this->getSession()->getPage();
    $page->fillField('title[0][value]', 'JS media select node');

    // Apply a selection the same way MediaLibraryOpener::getSelectionResponse()
    // does after the modal returns: set the hidden value and trigger the
    // update button. This exercises addItems / updateWidget / massageFormValue
    // without depending on media_library view markup or dialog buttons.
    $session = $this->getSession();
    $session->executeScript(sprintf(
      'var v = document.querySelector("[data-media-library-widget-value]");
       var u = document.querySelector("[data-media-library-widget-update]");
       if (!v || !u) { throw new Error("Media library widget update controls missing."); }
       v.value = %s;
       u.dispatchEvent(new MouseEvent("mousedown", {bubbles: true}));',
      json_encode((string) $this->media->id())
    ));
    $assert->assertWaitOnAjaxRequest();

    // Widget should show the selection and hide the empty message.
    $assert->waitForElement('css', '.js-media-library-item');
    $assert->pageTextNotContains('No media item is selected.');
    $assert->buttonExists('Remove');

    $page->pressButton('Save');
    $assert->waitForText('has been created');

    $node = $this->loadNodeByTitle('JS media select node');
    $this->assertEquals(
      $this->media->id(),
      $node->get('field_test')->media_ref
    );
  }

  /**
   * Tests removing a selected media item via AJAX.
   */
  public function testRemoveMediaAjax(): void {
    $assert = $this->assertSession();

    $node = $this->drupalCreateNode([
      'type' => 'page',
      'title' => 'JS media remove node',
      'field_test' => [
        'media_ref' => $this->media->id(),
      ],
    ]);

    $this->drupalGet('node/' . $node->id() . '/edit');
    $assert->buttonExists('Remove');

    $page = $this->getSession()->getPage();
    $page->pressButton('Remove');
    $assert->assertWaitOnAjaxRequest();

    $assert->waitForText('No media item is selected.');
    $assert->buttonExists('Add media');

    $page->pressButton('Save');
    $assert->waitForText('has been updated');

    $node = $this->loadNodeByTitle('JS media remove node');
    $value = $node->get('field_test')->media_ref ?? NULL;
    $this->assertTrue($value === NULL || $value === '');
  }

}
