<?php

namespace Drupal\physical\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Dimensions constraint.
 */
#[Constraint(
  id: "Dimensions",
  label: new TranslatableMarkup("Dimension", options: ["context" => "Validation"]),
  type: ["physical_dimensions"],
)]
class DimensionsConstraint extends SymfonyConstraint {

  /**
   * Empty Message variable.
   *
   * @var string
   */
  public $emptyMessage = '@name field is required.';

  /**
   * Invalid Message variable.
   *
   * @var string
   */
  public $invalidMessage = '@name field must be a number.';

}
