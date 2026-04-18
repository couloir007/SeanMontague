<?php

namespace Drupal\gvp_external_resources;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;

/**
 * Provides an interface for defining Volcano external resource entities.
 *
 * @ingroup gvp_external_resources
 */
interface VolcanoExternalResourceInterface extends ContentEntityInterface, RevisionLogInterface, EntityOwnerInterface, EntityChangedInterface, EntityPublishedInterface {

  /**
   * Gets the Volcano external resource creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Volcano external resource.
   */
  public function getCreatedTime();

  /**
   * Sets the Volcano external resource creation timestamp.
   *
   * @param int $timestamp
   *   The Volcano external resource creation timestamp.
   *
   * @return \Drupal\gvp_external_resources\VolcanoExternalResourceInterface
   *   The called Volcano external resource entity.
   */
  public function setCreatedTime($timestamp);

}
