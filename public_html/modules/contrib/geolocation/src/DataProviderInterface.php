<?php

namespace Drupal\geolocation;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Defines an interface for geolocation DataProvider plugins.
 */
interface DataProviderInterface extends PluginInspectionInterface {

  /**
   * Determine valid views option.
   *
   * @param \Drupal\views\Plugin\views\field\FieldPluginBase $viewsField
   *   Views field definition.
   *
   * @return bool
   *   Yes or no.
   */
  public function isViewsGeoOption(FieldPluginBase $viewsField): bool;

  /**
   * Determine valid field geo option.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $fieldDefinition
   *   Field definition.
   *
   * @return bool
   *   Yes or no.
   */
  public function isFieldGeoOption(FieldDefinitionInterface $fieldDefinition): bool;

  /**
   * Get locations from views row.
   *
   * @param \Drupal\views\ResultRow $row
   *   Row.
   * @param \Drupal\views\Plugin\views\field\FieldPluginBase|null $viewsField
   *   Views field definition.
   *
   * @return array
   *   Renderable locations.
   */
  public function getLocationsFromViewsRow(ResultRow $row, ?FieldPluginBase $viewsField = NULL): array;

  /**
   * Get shapes from views row.
   *
   * @param \Drupal\views\ResultRow $row
   *   Row.
   * @param \Drupal\views\Plugin\views\field\FieldPluginBase|null $viewsField
   *   Views field definition.
   *
   * @return array
   *   Renderable shapes.
   */
  public function getShapesFromViewsRow(ResultRow $row, ?FieldPluginBase $viewsField = NULL): array;

  /**
   * Get locations from field item list.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $fieldItem
   *   Views field definition.
   *
   * @return array
   *   Renderable locations.
   */
  public function getLocationsFromItem(FieldItemInterface $fieldItem): array;

  /**
   * Get shapes from field item list.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $fieldItem
   *   Views field definition.
   *
   * @return array
   *   Renderable shapes.
   */
  public function getShapesFromItem(FieldItemInterface $fieldItem): array;

  /**
   * Replace token.
   *
   * @param string $text
   *   Token piece.
   * @param \Drupal\Core\Field\FieldItemInterface $fieldItem
   *   Field for token replacement.
   *
   * @return string
   *   Replaced token.
   */
  public function replaceFieldItemTokens(string $text, FieldItemInterface $fieldItem): string;

  /**
   * Get token help.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface|null $fieldDefinition
   *   Field definition.
   *
   * @return array
   *   Token tree.
   */
  public function getTokenHelp(?FieldDefinitionInterface $fieldDefinition = NULL): array;

  /**
   * Get settings form.
   *
   * @param array $settings
   *   Settings.
   * @param array $parents
   *   Form parents.
   *
   * @return array
   *   Form render array.
   */
  public function getSettingsForm(array $settings, array $parents = []): array;

  /**
   * Set views field.
   *
   * @param \Drupal\views\Plugin\views\field\FieldPluginBase $viewsField
   *   Views field.
   */
  public function setViewsField(FieldPluginBase $viewsField): void;

  /**
   * Set field definition.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $fieldDefinition
   *   Field definition.
   */
  public function setFieldDefinition(FieldDefinitionInterface $fieldDefinition): void;

}
