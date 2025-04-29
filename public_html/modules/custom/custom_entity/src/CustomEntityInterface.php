<?php

declare(strict_types=1);

namespace Drupal\custom_entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a custom entity entity type.
 */
interface CustomEntityInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
