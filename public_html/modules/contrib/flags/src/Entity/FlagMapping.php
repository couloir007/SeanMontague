<?php

namespace Drupal\flags\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the flag mapping entity.
 *
 * Inherit from this class and add config entity annotation.
 */
abstract class FlagMapping extends ConfigEntityBase {

  /**
   * Source language code.
   *
   * @var string
   */
  protected $source;

  /**
   * Mapping UUID.
   *
   * @var string
   */
  protected $uuid;

  /**
   * Target territory flag.
   *
   * @var string
   */
  protected $flag;

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->source;
  }

  /**
   * {@inheritdoc}
   *
   * This method is required because for some reason entity_keys in config
   * entity annotation are ignored.
   */
  public function id() {
    return $this->source;
  }

  /**
   * Sets ID.
   *
   * This method is required because for some reason entity_keys in config
   * entity annotation are ignored.
   *
   * @param string $id
   *   The entity ID.
   *
   * @return $this
   *   Returns this entity.
   */
  public function setId($id) {
    $this->source = $id;

    return $this;
  }

  /**
   * Gets source of the mapping.
   *
   * @return string
   *   The source value.
   */
  public function getSource() {
    return $this->source;
  }

  /**
   * Sets source for the mapping.
   *
   * @param string $source
   *   The source value to set.
   *
   * @return FlagMapping
   *   Returns this entity.
   */
  public function setSource($source) {
    $this->source = $source;

    return $this;
  }

  /**
   * Gets uuid.
   *
   * @return string
   *   The UUID.
   */
  public function getUuid() {
    return $this->uuid;
  }

  /**
   * Sets uuid.
   *
   * @param string $uuid
   *   The UUID to set.
   *
   * @return FlagMapping
   *   Returns this entity.
   */
  public function setUuid($uuid) {
    $this->uuid = $uuid;

    return $this;
  }

  /**
   * Gets target flag.
   *
   * @return string
   *   The flag code.
   */
  public function getFlag() {
    return $this->flag;
  }

  /**
   * Sets target flag.
   *
   * @param string $flag
   *   The flag code to set.
   *
   * @return FlagMapping
   *   Returns this entity.
   */
  public function setFlag($flag) {
    // Make sure that the flag is lowercase.
    $this->flag = strtolower($flag);

    return $this;
  }

}
