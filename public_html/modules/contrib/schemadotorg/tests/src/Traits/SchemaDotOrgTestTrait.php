<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg\Traits;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\DeprecationHelper;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Provides convenience methods for Schema.org assertions.
 */
trait SchemaDotOrgTestTrait {

  /**
   * Convert all array values (i.e., markup and urls) into strings.
   *
   * @param array $elements
   *   An associative array of values converted to strings.
   */
  protected function convertArrayValuesToStrings(array &$elements): void {
    foreach ($elements as $key => &$value) {
      if (is_array($value)) {
        $this->convertArrayValuesToStrings($value);
      }
      elseif ($value instanceof MarkupInterface) {
        $elements[$key] = (string) $value;
      }
      elseif ($value instanceof Url) {
        $elements[$key] = $value->toString();
      }
    }
  }

  /**
   * Create Schema.org field.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $schema_type
   *   The Schema.org type.
   * @param string $schema_property
   *   The Schema.org property.
   * @param string $field_type
   *   (optional) The field type.  Defaults to 'string'.
   */
  protected function createSchemaDotOrgField(
    string $entity_type_id,
    string $schema_type,
    string $schema_property = 'alternateName',
    string $field_type = 'string',
  ): void {
    /** @var \Drupal\schemadotorg\SchemaDotOrgNamesInterface $schema_names */
    $schema_names = $this->container->get('schemadotorg.names');

    $bundle = $schema_names->camelCaseToSnakeCase($schema_type);
    $field = [
      'type' => $field_type,
      'field_name' => $schema_names->getFieldPrefix() . $schema_names->schemaIdToDrupalName('properties', $schema_property),
      'label' => $schema_names->camelCaseToSentenceCase($schema_property),
      'schema_type' => $schema_type,
      'schema_property' => $schema_property,
    ];

    /** @var \Drupal\schemadotorg\SchemaDotOrgEntityTypeBuilderInterface $schema_entity_type_builder */
    $schema_entity_type_builder = $this->container->get('schemadotorg.entity_type_builder');
    $schema_entity_type_builder->addFieldToEntity($entity_type_id, $bundle, $field);

  }

  /**
   * Creates body field storage for tests.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   */
  protected function createBodyFieldStorage(string $entity_type_id): void {
    if ($entity_type_id === 'node') {
      DeprecationHelper::backwardsCompatibleCall(
        currentVersion: \Drupal::VERSION,
        deprecatedVersion: '11.0.0',
        currentCallable: fn() => $this->doCreateBodyFieldStorage($entity_type_id),
        deprecatedCallable: fn() => NULL,
      );
      return;
    }

    $this->doCreateBodyFieldStorage($entity_type_id);
  }

  /**
   * Creates body field storage for tests.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   */
  protected function doCreateBodyFieldStorage(string $entity_type_id): void {
    $field_storage = FieldStorageConfig::load($entity_type_id . '.body');
    if ($field_storage) {
      if ($field_storage->getType() === 'text_with_summary') {
        return;
      }
      $field_storage->delete();
    }

    FieldStorageConfig::create([
      'entity_type' => $entity_type_id,
      'field_name' => 'body',
      'type' => 'text_with_summary',
      'settings' => [],
      'module' => 'text',
      'cardinality' => 1,
      'translatable' => TRUE,
      'persist_with_no_fields' => TRUE,
    ])->save();
  }

  /**
   * Append properties to a Schema.org type's default properties.
   *
   * @param string $type
   *   The Schema.org type.
   * @param array|string $property
   *   The Schema.org property or an array of Schema.org properties.
   */
  protected function appendSchemaTypeDefaultProperties(string $type, array|string $property): void {
    $config = \Drupal::configFactory()->getEditable('schemadotorg.settings');
    $default_properties = $config->get('schema_types.default_properties');
    $default_properties[$type] = $default_properties[$type] ?? [];
    $default_properties[$type] = array_merge($default_properties[$type], (array) $property);
    $default_properties[$type] = array_unique($default_properties[$type]);
    asort($default_properties[$type]);
    $config->set('schema_types.default_properties', $default_properties);
    $config->save();
  }

}
