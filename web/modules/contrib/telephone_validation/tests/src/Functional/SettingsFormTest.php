<?php

declare(strict_types=1);

namespace Drupal\Tests\telephone_validation\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the telephone validation settings form.
 *
 * @group telephone_validation
 */
class SettingsFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['telephone_validation'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the settings form loads and submits successfully.
   */
  public function testSettingsForm(): void {
    $account = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($account);

    $this->drupalGet('/admin/config/content/telephone_validation');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists('format');
    $this->assertSession()->fieldExists('country[]');

    $this->submitForm(['format' => 0, 'country[]' => 'CA'], 'Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
  }

}
