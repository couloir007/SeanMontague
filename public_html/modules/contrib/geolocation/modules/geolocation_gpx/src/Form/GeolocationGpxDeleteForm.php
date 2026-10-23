<?php

namespace Drupal\geolocation_gpx\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Url;

/**
 * Form controller for the GPX entity delete form.
 */
class GeolocationGpxDeleteForm extends ContentEntityDeleteForm {

  use GeolocationGpxOverviewUrlTrait;

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return $this->overviewUrl();
  }

  /**
   * {@inheritdoc}
   */
  protected function getRedirectUrl(): Url {
    return $this->overviewUrl();
  }

}
