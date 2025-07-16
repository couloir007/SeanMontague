<?php

declare(strict_types=1);

namespace Drupal\geo_content_builder\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the SI Map type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "geo_content_builder_type",
 *   label = @Translation("SI Map type"),
 *   label_collection = @Translation("SI Map types"),
 *   label_singular = @Translation("si map type"),
 *   label_plural = @Translation("si maps types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count si maps type",
 *     plural = "@count si maps types",
 *   ),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\geo_content_builder\Form\SIMapTypeForm",
 *       "edit" = "Drupal\geo_content_builder\Form\SIMapTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\geo_content_builder\SIMapTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer geo_content_builder types",
 *   bundle_of = "geo_content_builder",
 *   config_prefix = "geo_content_builder_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/geo_content_builder_types/add",
 *     "edit-form" = "/admin/structure/geo_content_builder_types/manage/{geo_content_builder_type}",
 *     "delete-form" = "/admin/structure/geo_content_builder_types/manage/{geo_content_builder_type}/delete",
 *     "collection" = "/admin/structure/geo_content_builder_types",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   },
 * )
 */
final class SIMapType extends ConfigEntityBundleBase {

  /**
   * The machine name of this si map type.
   */
  protected string $id;

  /**
   * The human-readable name of the si map type.
   */
  protected string $label;

}
