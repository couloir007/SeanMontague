<?php

declare(strict_types=1);

namespace Drupal\geo_content_builder;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a si map entity type.
 */
interface GeoContentInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
