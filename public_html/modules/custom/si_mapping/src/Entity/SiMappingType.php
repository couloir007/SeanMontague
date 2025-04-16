<?php

declare(strict_types=1);

namespace Drupal\si_mapping\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Si mapping type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "si_mapping_type",
 *   label = @Translation("Si mapping type"),
 *   label_collection = @Translation("Si mapping types"),
 *   label_singular = @Translation("si mapping type"),
 *   label_plural = @Translation("si mappings types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count si mappings type",
 *     plural = "@count si mappings types",
 *   ),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\si_mapping\Form\SiMappingTypeForm",
 *       "edit" = "Drupal\si_mapping\Form\SiMappingTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\si_mapping\SiMappingTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer si_mapping types",
 *   bundle_of = "si_mapping",
 *   config_prefix = "si_mapping_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/si_mapping_types/add",
 *     "edit-form" = "/admin/structure/si_mapping_types/manage/{si_mapping_type}",
 *     "delete-form" = "/admin/structure/si_mapping_types/manage/{si_mapping_type}/delete",
 *     "collection" = "/admin/structure/si_mapping_types",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   },
 * )
 */
final class SiMappingType extends ConfigEntityBundleBase {

  /**
   * The machine name of this si mapping type.
   */
  protected string $id;

  /**
   * The human-readable name of the si mapping type.
   */
  protected string $label;

}
