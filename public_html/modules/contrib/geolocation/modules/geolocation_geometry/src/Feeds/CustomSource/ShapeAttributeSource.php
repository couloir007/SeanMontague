<?php

namespace Drupal\geolocation_geometry\Feeds\CustomSource;

use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\Feeds\CustomSource\BlankSource;

/**
 * A CSV source.
 *
 * @FeedsCustomSource(
 *   id = "shp",
 *   title = @Translation("Shape Attribute"),
 * )
 */
class ShapeAttributeSource extends BlankSource {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['value']['#description'] = $this->t('Shape Attribute Key');
    return $form;
  }

}
