<?php

declare(strict_types=1);

namespace Drupal\si_map\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the SI Map type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "si_map_type",
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
 *       "add" = "Drupal\si_map\Form\SIMapTypeForm",
 *       "edit" = "Drupal\si_map\Form\SIMapTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\si_map\SIMapTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer si_map types",
 *   bundle_of = "si_map",
 *   config_prefix = "si_map_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/si_map_types/add",
 *     "edit-form" = "/admin/structure/si_map_types/manage/{si_map_type}",
 *     "delete-form" = "/admin/structure/si_map_types/manage/{si_map_type}/delete",
 *     "collection" = "/admin/structure/si_map_types",
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
