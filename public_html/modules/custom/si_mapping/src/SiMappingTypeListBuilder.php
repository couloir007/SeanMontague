<?php

declare(strict_types=1);

namespace Drupal\si_mapping;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of si mapping type entities.
 *
 * @see \Drupal\si_mapping\Entity\SiMappingType
 */
final class SiMappingTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['label'] = $this->t('Label');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $row['label'] = $entity->label();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    $build = parent::render();

    $build['table']['#empty'] = $this->t(
      'No si mapping types available. <a href=":link">Add si mapping type</a>.',
      [':link' => Url::fromRoute('entity.si_mapping_type.add_form')->toString()],
    );

    return $build;
  }

}
