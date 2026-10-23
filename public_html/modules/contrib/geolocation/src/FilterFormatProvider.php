<?php

namespace Drupal\geolocation;

use Drupal\Component\Utility\DeprecationHelper;
use Drupal\filter\FilterFormatRepositoryInterface;

/**
 * Class FilterFormatProvider.
 *
 * Resolves text formats across all supported Drupal core versions.
 *
 * The FilterFormatRepositoryInterface service only exists as of Drupal 11.4,
 * while this module supports Drupal 10 and 11.3 as well. The procedural
 * functions it replaces are deprecated as of Drupal 11.4 and removed in
 * Drupal 13, so both code paths have to be supported for the time being.
 *
 * @see https://www.drupal.org/node/3035368
 *
 * @package Drupal\geolocation
 */
class FilterFormatProvider {

  /**
   * Get the ID of the default text format for the current user.
   *
   * @return string|null
   *   Default text format ID or NULL if the filter module isn't installed.
   */
  public static function getDefaultFormatId(): ?string {
    if (!\Drupal::moduleHandler()->moduleExists('filter')) {
      return NULL;
    }

    // @todo Remove the fallback once Drupal 11.3 support is dropped.
    return DeprecationHelper::backwardsCompatibleCall(
      currentVersion: \Drupal::VERSION,
      deprecatedVersion: '11.4.0',
      currentCallable: fn () => \Drupal::service(FilterFormatRepositoryInterface::class)->getDefaultFormat()->id(),
      deprecatedCallable: fn () => filter_default_format(),
    );
  }

}
