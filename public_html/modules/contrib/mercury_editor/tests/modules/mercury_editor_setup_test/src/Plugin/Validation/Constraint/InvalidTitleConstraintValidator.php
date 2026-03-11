<?php

namespace Drupal\mercury_editor_setup_test\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Rejects entities with "Invalid" in the title.
 */
class InvalidTitleConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    if ($entity->hasField('title')) {
      $title = $entity->get('title')->value;
      if (stripos($title, 'Invalid') !== false) {
        $this->context->addViolation($constraint->message);
      }
    }
  }
}
