<?php

declare(strict_types=1);

namespace Drupal\si_mapping\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the SI Mapping Item type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "si_mapping_si_mapping_item_type",
 *   label = @Translation("SI Mapping Item type"),
 *   label_collection = @Translation("SI Mapping Item types"),
 *   label_singular = @Translation("si mapping item type"),
 *   label_plural = @Translation("si mapping items types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count si mapping items type",
 *     plural = "@count si mapping items types",
 *   ),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\si_mapping\Form\SIMappingItemTypeForm",
 *       "edit" = "Drupal\si_mapping\Form\SIMappingItemTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\si_mapping\SIMappingItemTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "entity_reference" = {
 *       "selection" = "Drupal\your_module\Plugin\EntityReferenceSelection\YourCustomSelection",
 *     },
 *   },
 *   admin_permission = "administer si_mapping_si_mapping_item types",
 *   bundle_of = "si_mapping_si_mapping_item",
 *   config_prefix = "si_mapping_si_mapping_item_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/si_mapping_si_mapping_item_types/add",
 *     "edit-form" = "/admin/structure/si_mapping_si_mapping_item_types/manage/{si_mapping_si_mapping_item_type}",
 *     "delete-form" = "/admin/structure/si_mapping_si_mapping_item_types/manage/{si_mapping_si_mapping_item_type}/delete",
 *     "collection" = "/admin/structure/si_mapping_si_mapping_item_types",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   },
 * )
 */
final class SIMappingItemType extends ConfigEntityBundleBase {

  /**
   * The machine name of this si mapping item type.
   */
  protected string $id;

  /**
   * The human-readable name of the si mapping item type.
   */
  protected string $label;

}
