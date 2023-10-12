<?php

namespace Drupal\Tests\cars_list\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Cars List configuration form.
 */
class CarsListConfigFormTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['cars_list'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests access to the config form.
   */
  public function testAccess() {
    $configUrl = Url::fromRoute('cars_list.admin_settings');

    // Check that anonymous users can't access the config form.
    $this->drupalGet($configUrl);
    $this->assertSession()->statusCodeEquals(403);

    // Check that logged in users without permission can't access.
    $user = $this->drupalCreateUser([]);
    $this->drupalLogin($user);
    $this->drupalGet($configUrl);
    $this->assertSession()->statusCodeEquals(403);

    // Check that users with the appropriate permission can access.
    $user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($user);
    $this->drupalGet($configUrl);
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests the configuration form.
   */
  public function testConfigForm() {
    $user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($user);

    $configUrl = Url::fromRoute('cars_list.admin_settings');
    $this->drupalGet($configUrl);

    // Update the configuration.
    $edit = [
      'endpoint' => 'https://test.endpoint/api/',
      'cache_duration' => '1800',
    ];
    $this->submitForm($edit, 'Save');

    // Check if the values are saved and applied to the configuration.
    $config = $this->config('cars_list.settings');
    $this->assertEquals('https://test.endpoint/api/', $config->get('endpoint'));
    $this->assertEquals('1800', $config->get('cache_duration'));
  }

}
