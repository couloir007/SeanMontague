<?php

declare(strict_types=1);

namespace Drupal\Tests\paragraphs_usage\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field\Traits\EntityReferenceFieldCreationTrait;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\User;

/**
 * Base class for tests.
 */
abstract class ParagraphsUsageTestBase extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'entity_reference_revisions',
    'field',
    'field_ui',
    'paragraphs',
    'paragraphs_usage',
    'system',
    'user',
  ];

  /**
   * User permissions.
   *
   * @var array
   */
  protected array $adminPermissions;


  /**
   * The User Entity.
   *
   * @var \Drupal\user\Entity\User|false
   */
  protected $adminUser;

  use EntityReferenceFieldCreationTrait;
  use FieldUiTestTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->adminPermissions = [
      'administer paragraph fields',
      'administer paragraphs types',
      'administer paragraph form display',
    ];

    // Place the breadcrumb, tested in fieldUIAddNewField().
    $this->drupalPlaceBlock('system_breadcrumb_block');
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('page_title_block');

  }

  /**
   * Creates an user with admin permissions and log in.
   *
   * @param array $additional_permissions
   *   Additional permissions that will be granted to admin user.
   *
   * @return \Drupal\user\Entity\User|false
   *   Newly created and logged in user object.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function loginAsAdmin(array $additional_permissions = []): User|false {
    $permissions = $this->adminPermissions;

    if (!empty($additional_permissions)) {
      $permissions = array_merge($permissions, $additional_permissions);
    }

    $this->adminUser = $this->drupalCreateUser($permissions);
    if ($this->adminUser) {
      $this->drupalLogin($this->adminUser);
    }
    return $this->adminUser;
  }

  /**
   * Adds a Paragraphs field to a given entity type.
   *
   * @param string $bundle
   *   Bundle to be used.
   * @param string $paragraphs_field_name
   *   Paragraphs field name to be used.
   * @param string $entity_type
   *   Entity type where to add the field.
   * @param string $widget_type
   *   (optional) Declares if we use experimental or classic widget.
   *   Defaults to 'paragraphs' for experimental widget.
   *   Use 'entity_reference_paragraphs' for classic widget.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function addParagraphsField(string $bundle, string $paragraphs_field_name, string $entity_type, string $widget_type = 'paragraphs'): void {
    $field_storage = FieldStorageConfig::loadByName($entity_type, $paragraphs_field_name);
    if (!$field_storage) {
      // Add a paragraphs field.
      $field_storage = FieldStorageConfig::create([
        'field_name' => $paragraphs_field_name,
        'entity_type' => $entity_type,
        'type' => 'entity_reference_revisions',
        'cardinality' => '-1',
        'settings' => [
          'target_type' => 'paragraph',
        ],
      ]);
      $field_storage->save();
    }
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $bundle,
      'settings' => [
        'handler' => 'default:paragraph',
        'handler_settings' => ['target_bundles' => [$paragraphs_field_name => $paragraphs_field_name]],
      ],
    ]);
    $field->save();

    $form_display = \Drupal::service('entity_display.repository')->getFormDisplay($entity_type, $bundle);
    $form_display = $form_display->setComponent($paragraphs_field_name, ['type' => $widget_type]);
    $form_display->save();

    $view_display = \Drupal::service('entity_display.repository')->getViewDisplay($entity_type, $bundle);
    $view_display->setComponent($paragraphs_field_name, ['type' => 'entity_reference_revisions_entity_view']);
    $view_display->save();
  }

  /**
   * Adds a Paragraphs type.
   *
   * @param string $paragraphs_type_name
   *   Paragraph type name used to create.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function addParagraphsType(string $paragraphs_type_name): void {
    $paragraphs_type = ParagraphsType::create([
      'id' => $paragraphs_type_name,
      'label' => $paragraphs_type_name,
    ]);
    $paragraphs_type->save();
  }

  /**
   * Create Vocabulary.
   *
   * @param string $name
   *   Vocabulary.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createVocabulary(string $name): void {
    $vocabulary = Vocabulary::create([
      'name' => $name,
      'vid' => mb_strtolower($name),
    ]);
    $vocabulary->save();
  }

  /**
   * Create Taxonomy Term.
   *
   * @param string $name
   *   Term name.
   * @param string $vid
   *   Vocabulary id.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createTerm(string $name, string $vid): void {
    $term = Term::create([
      'name' => $name,
      'vid' => $vid,
    ]);
    $term->save();
  }

  /**
   * Sets the allowed Paragraphs types that can be added.
   *
   * @param string $content_type
   *   Content type name that contains the paragraphs field.
   * @param array $paragraphs_types
   *   Array of paragraphs types that will be modified.
   * @param bool $selected
   *   Whether or not the paragraphs types will be enabled.
   * @param string $paragraphs_field
   *   Paragraphs field name that does the reference.
   * @param bool $excludeParagraphs
   *   The exclusion parameter.
   */
  protected function setAllowedParagraphsTypes(string $content_type, array $paragraphs_types, bool $selected, string $paragraphs_field, bool $excludeParagraphs): void {
    $edit = [];
    $edit['settings[handler_settings][negate]'] = '0';
    $this->drupalGet('admin/structure/types/manage/' . $content_type . '/fields/node.' . $content_type . '.' . $paragraphs_field);

    if ($excludeParagraphs) {
      $edit['settings[handler_settings][negate]'] = '1';
    }

    foreach ($paragraphs_types as $paragraphs_type) {
      $edit['settings[handler_settings][target_bundles_drag_drop][' . $paragraphs_type . '][enabled]'] = $selected;
    }
    $this->submitForm($edit, $this->t('Save settings'));
  }

}
