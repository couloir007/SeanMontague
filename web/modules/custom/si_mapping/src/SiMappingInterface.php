<?php

declare(strict_types=1);

namespace Drupal\si_mapping;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a si mapping entity type.
 */
interface SiMappingInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
