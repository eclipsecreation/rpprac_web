<?php

declare(strict_types=1);

namespace Drupal\Tests\stripe\FunctionalJavascript;

use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the JavaScript functionality of the Stripe module.
 */
#[Group('stripe')]
final class ExampleModuleTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'claro';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'stripe',
    'stripe_examples',
  ];

  /**
   * Test callback.
   */
  public function testExampleModule(): void {
    $this->drupalGet(Url::fromRoute('stripe_examples.stripe_examples_simple_checkout'));
    $this->assertSession()->pageTextContains('Anything other than "John" would give a validation error to test different scenarios.');
    $this->submitForm([
      'First name' => $this->randomString(),
      'Last name' => $this->randomString(),
    ], 'Pay $25');
    $this->assertSession()->pageTextContains('You must configure the API Keys on test for the stripe element to work.');

    $this->submitForm([
      'First name' => 'John',
      'Last name' => $this->randomString(),
    ], 'Pay $25');
    $this->assertSession()->pageTextContains('first: John');
  }

}
