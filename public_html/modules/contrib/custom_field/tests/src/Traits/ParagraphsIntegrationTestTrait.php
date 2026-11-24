<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Traits;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\custom_field\Plugin\CustomField\FieldType\LinkType;
use Drupal\custom_field\Plugin\Field\FieldType\CustomItem;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\media\Entity\Media;
use Drupal\media\Entity\MediaType;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\views\Entity\View;

/**
 * Builds a node -> paragraph -> custom field structure for integration tests.
 *
 * The custom field carries one subfield of every type whose data type writes
 * extra properties into sibling columns - link, datetime, daterange,
 * time_range, image and viewfield - plus a plain string subfield as a control,
 * and optionally a media entity-reference subfield using the media library
 * widget (see ::setUpParagraphsIntegration()). Everything is created
 * programmatically so the tests need no test module.
 *
 * Requires \Drupal\Tests\custom_field\Traits\CustomFieldTestTrait for
 * createCustomField() and reloadNode(), and a PHPUnit test case for the
 * assertions and the service container.
 */
trait ParagraphsIntegrationTestTrait {

  /**
   * Machine name of the custom field on the paragraph.
   */
  protected string $paragraphField = 'field_content';

  /**
   * Machine name of the paragraph reference field on the node.
   */
  protected string $hostField = 'field_components';

  /**
   * Ids of the media entities created by ::createMediaFixtures().
   *
   * @var int[]
   */
  protected array $mediaIds = [];

  /**
   * Builds views, the paragraph type, the custom field and the host field.
   *
   * @param bool $with_media
   *   Whether to add a media entity-reference subfield with the media library
   *   widget, and the media type and entities it needs. Only the
   *   FunctionalJavascript test can drive the media library modal, so the
   *   non-JavaScript test leaves this off.
   */
  protected function setUpParagraphsIntegration(bool $with_media = FALSE): void {
    $this->createIntegrationViews();
    if ($with_media) {
      $this->createMediaFixtures();
    }
    $this->createCardParagraphType($with_media);
    $this->createParagraphsHostField();
  }

  /**
   * Creates an image media type and two media entities to select from.
   */
  protected function createMediaFixtures(): void {
    $storage = FieldStorageConfig::create([
      'field_name' => 'field_media_image',
      'entity_type' => 'media',
      'type' => 'image',
      'cardinality' => 1,
      'settings' => ['target_type' => 'file', 'uri_scheme' => 'public'],
    ]);
    $storage->save();

    MediaType::create([
      'id' => 'image',
      'label' => 'Image',
      'source' => 'image',
      'source_configuration' => ['source_field' => 'field_media_image'],
      'field_map' => [],
    ])->save();

    FieldConfig::create([
      'field_storage' => $storage,
      'bundle' => 'image',
      'label' => 'Image',
      'required' => TRUE,
      'settings' => ['alt_field' => TRUE, 'alt_field_required' => FALSE],
    ])->save();

    $repository = \Drupal::service('file.repository');
    $data = file_get_contents($this->fixtureImagePath());
    foreach (['Media one', 'Media two'] as $index => $name) {
      $file = $repository->writeData($data, 'public://media_' . $index . '.png');
      $file->setPermanent();
      $file->save();
      $media = Media::create([
        'bundle' => 'image',
        'name' => $name,
        'field_media_image' => ['target_id' => $file->id(), 'alt' => $name],
      ]);
      $media->save();
      $this->mediaIds[] = (int) $media->id();
    }
  }

  /**
   * Creates the two views the viewfield subfield can reference.
   */
  protected function createIntegrationViews(): void {
    foreach (['view_one' => 'View One', 'view_two' => 'View Two'] as $id => $label) {
      View::create([
        'id' => $id,
        'label' => $label,
        'base_table' => 'node_field_data',
        'status' => TRUE,
        'display' => [
          'default' => [
            'id' => 'default',
            'display_title' => 'Master',
            'display_plugin' => 'default',
            'position' => 0,
            'display_options' => [],
          ],
          'page_1' => [
            'id' => 'page_1',
            'display_title' => 'Page ' . $label,
            'display_plugin' => 'page',
            'position' => 1,
            'display_options' => ['path' => str_replace('_', '-', $id) . '-page'],
          ],
        ],
      ])->save();
    }
  }

  /**
   * Creates the card paragraph type and its custom field.
   *
   * @param bool $with_media
   *   Whether to include the media subfield.
   */
  protected function createCardParagraphType(bool $with_media = FALSE): void {
    ParagraphsType::create(['id' => 'card', 'label' => 'Card'])->save();

    $media_column = $with_media
      ? ['media' => ['name' => 'media', 'type' => 'entity_reference', 'target_type' => 'media']]
      : [];
    $media_settings = $with_media
      ? [
        'media' => [
          'label' => 'Media',
          'check_empty' => FALSE,
          'handler' => 'default:media',
          'handler_settings' => ['target_bundles' => ['image' => 'image']],
        ],
      ]
      : [];
    $media_widget = $with_media
      ? [
        'media' => [
          'type' => 'media_library_widget',
          'weight' => 7,
          'label' => 'Media',
          'media_types' => ['image'],
        ],
      ]
      : [];

    $this->createCustomField(
      $this->paragraphField,
      $media_column + [
        'txt' => ['name' => 'txt', 'type' => 'string'],
        'lnk' => ['name' => 'lnk', 'type' => 'link'],
        'dt' => ['name' => 'dt', 'type' => 'datetime', 'datetime_type' => 'datetime'],
        'dr' => ['name' => 'dr', 'type' => 'daterange', 'datetime_type' => 'datetime'],
        'tr' => ['name' => 'tr', 'type' => 'time_range'],
        'img' => ['name' => 'img', 'type' => 'image', 'uri_scheme' => 'public', 'target_type' => 'file'],
        'vf' => ['name' => 'vf', 'type' => 'viewfield', 'target_type' => 'view'],
      ],
      $media_settings + [
        'txt' => ['label' => 'Text', 'check_empty' => FALSE],
        'lnk' => [
          'label' => 'Link',
          'check_empty' => FALSE,
          'title' => 1,
          'enabled_attributes' => ['target' => TRUE],
          'widget_default_open' => LinkType::WIDGET_OPEN_EXPAND_IF_VALUES_SET,
        ],
        'dt' => ['label' => 'Datetime', 'check_empty' => FALSE, 'timezone_enabled' => TRUE],
        'dr' => ['label' => 'Date range', 'check_empty' => FALSE],
        'tr' => ['label' => 'Time range', 'check_empty' => FALSE],
        'img' => [
          'label' => 'Image',
          'check_empty' => FALSE,
          'alt_field' => 1,
          'alt_field_required' => 0,
          'title_field' => 1,
          'title_field_required' => 0,
        ],
        'vf' => [
          'label' => 'Viewfield',
          'check_empty' => FALSE,
          'required' => FALSE,
          'force_default' => 0,
          'allowed_views' => [
            'view_one' => ['default' => 0, 'page_1' => 'page_1'],
            'view_two' => ['default' => 'default', 'page_1' => 'page_1'],
          ],
        ],
      ],
      'paragraph',
      'card',
    );

    $display = EntityFormDisplay::load('paragraph.card.default')
      ?: EntityFormDisplay::create([
        'targetEntityType' => 'paragraph',
        'bundle' => 'card',
        'mode' => 'default',
        'status' => TRUE,
      ]);

    $display->setComponent($this->paragraphField, [
      'type' => 'custom_stacked',
      'weight' => 0,
      'region' => 'content',
      'settings' => [
        'wrapper' => 'details',
        'open' => TRUE,
        'fields' => $media_widget + [
          'txt' => ['type' => 'text', 'weight' => 0, 'label' => 'Text'],
          'lnk' => ['type' => 'link_default', 'weight' => 1, 'label' => 'Link'],
          'dt' => ['type' => 'datetime_default', 'weight' => 2, 'label' => 'Datetime'],
          'dr' => ['type' => 'daterange_default', 'weight' => 3, 'label' => 'Date range'],
          'tr' => ['type' => 'time_range', 'weight' => 4, 'label' => 'Time range'],
          'img' => ['type' => 'image_image', 'weight' => 5, 'label' => 'Image'],
          'vf' => ['type' => 'viewfield_select', 'weight' => 6, 'label' => 'Viewfield'],
        ],
      ],
    ])->save();
  }

  /**
   * Adds the unlimited paragraph reference field to node.page.
   */
  protected function createParagraphsHostField(): void {
    $storage = FieldStorageConfig::create([
      'field_name' => $this->hostField,
      'entity_type' => 'node',
      'type' => 'entity_reference_revisions',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'settings' => ['target_type' => 'paragraph'],
    ]);
    $storage->save();

    FieldConfig::create([
      'field_storage' => $storage,
      'bundle' => 'page',
      'label' => 'Components',
      'settings' => [
        'handler' => 'default:paragraph',
        'handler_settings' => ['target_bundles' => ['card' => 'card'], 'negate' => 0],
      ],
    ])->save();

    $node_display = EntityFormDisplay::load('node.page.default');
    $node_display->setComponent($this->hostField, [
      'type' => 'paragraphs',
      'weight' => 10,
      'region' => 'content',
      'settings' => [
        'title' => 'Card',
        'title_plural' => 'Cards',
        'edit_mode' => 'open',
        'add_mode' => 'dropdown',
        'form_display_mode' => 'default',
        // '_none' is the sentinel: with a single allowed type, anything else
        // makes Paragraphs auto-place a card on a new host entity, which would
        // shift every delta the tests address.
        'default_paragraph_type' => '_none',
      ],
    ])->save();

    $this->container->get('entity_field.manager')->clearCachedFieldDefinitions();
  }

  /**
   * Returns the form element name for a subfield inside a paragraph delta.
   *
   * @param int $delta
   *   The paragraph delta.
   * @param string $subfield
   *   The custom field subfield machine name.
   * @param string $suffix
   *   Remaining element path, e.g. '[value][date]'.
   *
   * @return string
   *   The form element name.
   */
  protected function cardElement(int $delta, string $subfield, string $suffix = ''): string {
    return sprintf(
      '%s[%d][subform][%s][0][%s]%s',
      $this->hostField,
      $delta,
      $this->paragraphField,
      $subfield,
      $suffix
    );
  }

  /**
   * Returns the file-upload element name for the image subfield.
   *
   * @param int $delta
   *   The paragraph delta.
   *
   * @return string
   *   The upload element name.
   */
  protected function cardImageUpload(int $delta): string {
    return sprintf(
      'files[%s_%d_subform_%s_0_img]',
      $this->hostField,
      $delta,
      $this->paragraphField
    );
  }

  /**
   * Returns the upload button name for the image subfield.
   *
   * Core names the button after the same parents the file input is keyed by.
   *
   * @param int $delta
   *   The paragraph delta.
   *
   * @return string
   *   The button name.
   *
   * @see \Drupal\file\Element\ManagedFile::processManagedFile()
   */
  protected function cardImageUploadButton(int $delta): string {
    return sprintf(
      '%s_%d_subform_%s_0_img_upload_button',
      $this->hostField,
      $delta,
      $this->paragraphField
    );
  }

  /**
   * Returns the media library "Add media" button name for a paragraph delta.
   *
   * Every paragraph renders its own button, and a filled one is disabled, so
   * the button must be addressed per delta rather than by label.
   *
   * @param int $delta
   *   The paragraph delta.
   *
   * @return string
   *   The button name attribute.
   */
  protected function cardMediaOpenButton(int $delta): string {
    return sprintf(
      '%s_%d_subform_%s_0_media_media_open',
      $this->hostField,
      $delta,
      $this->paragraphField
    );
  }

  /**
   * Returns the custom field item of a paragraph on a freshly loaded node.
   *
   * @param int $nid
   *   The node id.
   * @param int $delta
   *   The paragraph delta.
   *
   * @return \Drupal\custom_field\Plugin\Field\FieldType\CustomItem
   *   The reloaded field item.
   */
  protected function reloadCardItem(int $nid, int $delta): CustomItem {
    $node = $this->reloadNode($nid);
    $paragraph = $node->get($this->hostField)->get($delta)->entity;
    $this->assertNotNull($paragraph, sprintf('Paragraph at delta %d exists.', $delta));

    return $paragraph->get($this->paragraphField)->get(0);
  }

  /**
   * Asserts the stored extra properties of one paragraph delta.
   *
   * @param int $nid
   *   The node id.
   * @param int $delta
   *   The paragraph delta.
   * @param array<string, mixed> $expected
   *   Expected values keyed by subfield property name.
   */
  protected function assertStoredCard(int $nid, int $delta, array $expected): void {
    $item = $this->reloadCardItem($nid, $delta);
    foreach ($expected as $property => $value) {
      $this->assertSame(
        $value,
        $item->{$property},
        sprintf('Paragraph %d property %s.', $delta, $property)
      );
    }
  }

  /**
   * Asserts how many paragraphs a node holds.
   *
   * Guards against the fixture quietly creating more paragraphs than the
   * scenario describes, which would shift every delta the tests address.
   *
   * @param int $nid
   *   The node id.
   * @param int $expected
   *   The expected number of paragraphs.
   */
  protected function assertCardCount(int $nid, int $expected): void {
    $this->assertCount(
      $expected,
      $this->reloadNode($nid)->get($this->hostField),
      'Number of paragraphs on the node.'
    );
  }

  /**
   * Path to the fixture image shipped with the module.
   *
   * @return string
   *   An absolute path.
   */
  protected function fixtureImagePath(): string {
    return \Drupal::service('file_system')->realpath(
      \Drupal::service('extension.list.module')->getPath('custom_field')
      . '/tests/resources/image/image_1.png'
    );
  }

}
