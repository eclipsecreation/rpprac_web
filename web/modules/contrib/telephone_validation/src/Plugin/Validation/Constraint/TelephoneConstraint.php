<?php

namespace Drupal\telephone_validation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Telephone constraint.
 *
 * @Constraint(
 *   id = "Telephone",
 *   label = @Translation("Telephone", context = "Validation")
 * )
 */
class TelephoneConstraint extends Constraint {

  /**
   * Violation for when a phone number is invalid.
   */
  public string $message = "@number is not a valid phone number.";

}
