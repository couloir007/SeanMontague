<?php

declare(strict_types=1);

namespace Drupal\si_map;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a si map entity type.
 */
interface SIMapInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
