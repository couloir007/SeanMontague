<?php

namespace Drupal\geolocation_gpx\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the GPX entity edit form.
 */
class GeolocationGpxForm extends ContentEntityForm {

  use GeolocationGpxOverviewUrlTrait;

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $this->messenger()->addStatus($this->t('Saved GPX %label.', [
      '%label' => $this->entity->label(),
    ]));

    $form_state->setRedirectUrl($this->overviewUrl());

    return $result;
  }

}
