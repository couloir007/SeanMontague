<?php

declare(strict_types=1);

namespace Drupal\si_mapping;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a si mapping item entity type.
 */
interface SIMappingItemInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
