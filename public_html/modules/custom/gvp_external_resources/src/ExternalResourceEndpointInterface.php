<?php

namespace Drupal\gvp_external_resources;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;

/**
 * Provides an interface for defining External resource endpoint entities.
 *
 * @ingroup gvp_external_resources
 */
interface ExternalResourceEndpointInterface extends ContentEntityInterface, RevisionLogInterface, EntityOwnerInterface, EntityChangedInterface, EntityPublishedInterface {

  /**
   * Gets the External resource endpoint name.
   *
   * @return string
   *   Name of the External resource endpoint.
   */
  public function getName();

  /**
   * Sets the External resource endpoint name.
   *
   * @param string $name
   *   The External resource endpoint name.
   *
   * @return \Drupal\gvp_external_resources\ExternalResourceEndpointInterface
   *   The called External resource endpoint entity.
   */
  public function setName($name);

  /**
   * Gets the External resource endpoint creation timestamp.
   *
   * @return int
   *   Creation timestamp of the External resource endpoint.
   */
  public function getCreatedTime();

  /**
   * Sets the External resource endpoint creation timestamp.
   *
   * @param int $timestamp
   *   The External resource endpoint creation timestamp.
   *
   * @return \Drupal\gvp_external_resources\ExternalResourceEndpointInterface
   *   The called External resource endpoint entity.
   */
  public function setCreatedTime($timestamp);

}
