<?php

namespace Drupal\flags\Entity;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of flag mapping entities.
 */
class FlagMappingListBuilder extends ConfigEntityListBuilder {

  /**
   * Array of all flags with their names.
   *
   * @var string[]
   */
  protected $flags;

  /**
   * Array of all countries with their names.
   *
   * @var string[]
   */
  protected $countries;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager'),
      $container->get('country_manager')->getList(),
      $container->get('flags.manager')->getList()
    );
  }

  /**
   * Constructs a new EntityListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param string[] $countries
   *   Array of all countries with their names.
   * @param string[] $flags
   *   Array of all flags with their names.
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    EntityTypeManagerInterface $entity_type_manager,
    $countries,
    $flags,
  ) {
    parent::__construct($entity_type, $entity_type_manager->getStorage($entity_type->id()));
    $this->flags = $flags;
    $this->countries = $countries;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['country'] = $this->t('Country');
    $header['flag'] = $this->t('Flag');
    $header['info'] = $this->t('Info');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    // Unfortunately countries are indexed with uppercase letters
    // se we make sure our ids are correct.
    /** @var FlagMapping $entity */
    $id = strtoupper($entity->getSource());

    $row['country'] = $this->countries[$id] ?? $id;
    $row['flag']['data'] = [
      '#theme' => 'flags',
      '#code' => strtolower($entity->getFlag()),
      '#source' => 'country',
    ];
    $row['info'] = $this->flags[$entity->getFlag()];

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['description'] = [
      '#markup' => $this->t(
        '<p>Country to flag mapping allows you to display flags from Flags module next to your country fields or country select forms.</p><p>Default mappings can be changed by adding configurations. You can also use the Operations column to edit and delete mappings.</p>'
      ),
    ];
    $build[] = parent::render();
    return $build;
  }

}
