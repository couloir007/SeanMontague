<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field_media\Functional\Widget;

use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\Tests\custom_field\Functional\CustomField\CustomFieldFunctionalTestBase;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\TestFileCreationTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Functional tests for the media_library_widget.
 *
 * Library open/select/remove AJAX is covered in FunctionalJavascript.
 *
 * @group custom_field
 * @group custom_field_media
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[Group('custom_field_media')]
#[RunTestsInSeparateProcesses]
class MediaLibraryWidgetTest extends CustomFieldFunctionalTestBase {

  use MediaTypeCreationTrait;
  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'media',
    'media_library',
    'file',
    'image',
    'custom_field_media',
  ];

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

    $this->createMediaType('image', ['id' => 'image', 'label' => 'Image']);

    $file = $this->getTestFiles('image')[0];
    $file_entity = $this->container->get('file.repository')->writeData(
      file_get_contents($file->uri),
      'public://media-test.png'
    );
    $this->media = Media::create([
      'bundle' => 'image',
      'name' => 'Test image media',
      'field_media_image' => [
        'target_id' => $file_entity->id(),
        'alt' => 'Test alt',
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
   * Tests empty widget UI: empty message and Add media control.
   */
  public function testEmptyStateUi(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $assert->pageTextContains('No media item is selected.');
    $assert->buttonExists('Add media');
    $assert->elementExists('css', '.js-media-library-widget');
    $assert->elementExists('css', '.js-media-library-open-button');
  }

  /**
   * Tests a seeded media value renders selection and Remove on edit.
   */
  public function testPersistedSelectionOnEdit(): void {
    $assert = $this->assertSession();

    $node = Node::create([
      'type' => 'page',
      'title' => 'Media seeded node',
      'field_test' => [
        'media_ref' => $this->media->id(),
      ],
    ]);
    $node->save();

    // Confirm storage before checking the form.
    $this->assertEquals(
      $this->media->id(),
      $node->get('field_test')->media_ref
    );

    $this->drupalGet('node/' . $node->id() . '/edit');

    $assert->pageTextNotContains('No media item is selected.');
    $assert->buttonExists('Remove');
    $assert->elementExists('css', '.js-media-library-item');

    // Hidden target_id is only present when a media item is selected.
    $hidden = $this->getSession()->getPage()->find(
      'css',
      'input[type="hidden"][name="field_test[0][media_ref][selection][0][target_id]"]'
    );
    $this->assertNotNull(
      $hidden,
      'Expected hidden selection target_id for the seeded media item.'
    );
    $this->assertEquals((string) $this->media->id(), $hidden->getValue());
  }

  /**
   * Tests massageFormValue maps selection target_id to the stored media id.
   *
   * Selection is injected by the media library (JS). Here we only verify the
   * storage column holds a media id when set via the entity API, matching the
   * value shape massageFormValue() produces after a real selection.
   */
  public function testStoredMediaIdRoundTrip(): void {
    $node = Node::create([
      'type' => 'page',
      'title' => 'Media round trip node',
      'field_test' => [
        'media_ref' => $this->media->id(),
      ],
    ]);
    $node->save();

    $reloaded = $this->loadNodeByTitle('Media round trip node');
    $this->assertEquals(
      $this->media->id(),
      $reloaded->get('field_test')->media_ref
    );

    // Clear via API and confirm empty.
    $reloaded->set('field_test', [['media_ref' => NULL]]);
    $reloaded->save();
    $reloaded = $this->loadNodeByTitle('Media round trip node');
    $value = $reloaded->get('field_test')->media_ref ?? NULL;
    $this->assertTrue($value === NULL || $value === '');
  }

  /**
   * Tests empty submission stores NULL.
   */
  public function testEmptyValueStoresNull(): void {
    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Empty media node',
    ], 'Save');

    $node = $this->loadNodeByTitle('Empty media node');
    $value = $node->get('field_test')->media_ref ?? NULL;
    $this->assertTrue($value === NULL || $value === '');
  }

  /**
   * Tests required media subfield validation.
   */
  public function testRequiredValidation(): void {
    $assert = $this->assertSession();

    $required_field = $this->createCustomField(
      'field_media_required',
      [
        'media_required' => [
          'name' => 'media_required',
          'type' => 'entity_reference',
          'target_type' => 'media',
        ],
      ],
      [
        'media_required' => [
          'label' => 'Media required',
          'check_empty' => FALSE,
          'required' => TRUE,
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
    $required_field->setRequired(TRUE)->save();
    $this->container->get('entity_field.manager')
      ->clearCachedFieldDefinitions();

    $this->setFormDisplay('field_media_required', [
      'media_required' => [
        'type' => 'media_library_widget',
        'weight' => 0,
        'label' => 'Media required',
        'media_types' => [],
      ],
    ]);

    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Required media node',
    ], 'Save');

    $assert->pageTextNotContains('Required media node has been created');
    $assert->pageTextContains('Media required field is required.');
  }

  /**
   * Tests media_types settings table appears when multiple media types allowed.
   */
  public function testMediaTypesSettingWithMultipleTypes(): void {
    $assert = $this->assertSession();

    $this->createMediaType('file', ['id' => 'document', 'label' => 'Document']);

    $this->createCustomField(
      'field_multi_media',
      [
        'media_multi' => [
          'name' => 'media_multi',
          'type' => 'entity_reference',
          'target_type' => 'media',
        ],
      ],
      [
        'media_multi' => [
          'label' => 'Multi media',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'handler' => 'default:media',
          'handler_settings' => [
            'target_bundles' => [
              'image' => 'image',
              'document' => 'document',
            ],
            'sort' => ['field' => '_none', 'direction' => 'ASC'],
            'auto_create' => FALSE,
            'auto_create_bundle' => '',
          ],
        ],
      ],
    );

    $this->setFormDisplay('field_multi_media', [
      'media_multi' => [
        'type' => 'media_library_widget',
        'weight' => 0,
        'label' => 'Multi media',
        'media_types' => [],
      ],
    ]);

    $this->drupalGet('admin/structure/types/manage/page/form-display');
    $this->submitForm([], 'field_multi_media_settings_edit');

    $assert->pageTextContains('Tab order');
    $assert->pageTextContains('Image');
    $assert->pageTextContains('Document');
  }

}
