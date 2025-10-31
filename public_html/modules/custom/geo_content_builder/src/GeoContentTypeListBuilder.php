<?php

declare(strict_types=1);

namespace Drupal\geo_content_builder;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of si map type entities.
 *
 * @see \Drupal\geo_content_builder\Entity\GeoContentType
 */
final class GeoContentTypeListBuilder extends ConfigEntityListBuilder {

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
      'No si map types available. <a href=":link">Add si map type</a>.',
      [':link' => Url::fromRoute('entity.geo_content_builder_type.add_form')->toString()],
    );

    return $build;
  }

}
