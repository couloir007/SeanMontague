<?php

namespace Drupal\gvp_external_resources;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

use Drupal\Core\Entity\EntityPublishedInterface;

/**
 * Provides an interface for defining External resource entities.
 *
 * @ingroup gvp_external_resources
 */
interface ExternalResourceInterface extends ContentEntityInterface, RevisionLogInterface, EntityOwnerInterface, EntityChangedInterface, EntityPublishedInterface {

  /**
   * Gets the External resource name.
   *
   * @return string
   *   Name of the External resource.
   */
  public function getName();

  /**
   * Sets the External resource name.
   *
   * @param string $name
   *   The External resource name.
   *
   * @return \Drupal\gvp_external_resources\ExternalResourceInterface
   *   The called External resource entity.
   */
  public function setName($name);

  /**
   * Gets the External resource creation timestamp.
   *
   * @return int
   *   Creation timestamp of the External resource.
   */
  public function getCreatedTime();

  /**
   * Sets the External resource creation timestamp.
   *
   * @param int $timestamp
   *   The External resource creation timestamp.
   *
   * @return \Drupal\gvp_external_resources\ExternalResourceInterface
   *   The called External resource entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the External resource published status indicator.
   *
   * Unpublished External resource are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the External resource is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a External resource.
   *
   * @param bool $published
   *   TRUE to set this External resource to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\gvp_external_resources\ExternalResourceInterface
   *   The called External resource entity.
   */
  public function setPublished($published = NULL);

}
