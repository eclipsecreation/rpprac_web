<?php

namespace Drupal\stripe_webform_payment\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for stripe_webform_payment routes.
 */
class StripeWebformPaymentController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function build() {

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];

    return $build;
  }

}
