<?php

namespace Drupal\mercury_editor;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Entity\ContentEntityFormInterface;
use Drupal\content_moderation\EntityTypeInfo as ContentModerationEntityTypeInfo;

/**
 * Provides entity type information for content moderation.
 */
class EntityTypeInfo extends ContentModerationEntityTypeInfo {

  /**
   * {@inheritDoc}
   */
  protected function isModeratedEntityEditForm(FormInterface $form_object) {
    return parent::isModeratedEntityEditForm($form_object) ||
      (
        $form_object instanceof ContentEntityFormInterface &&
        $form_object->getOperation() == 'mercury_editor' &&
        $this->moderationInfo->isModeratedEntity($form_object->getEntity())
      );
  }

}
