<?php

namespace Drupal\Tests\geolocation\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Support tests using JS.
 */
#[RunTestsInSeparateProcesses]
abstract class GeolocationJavascriptTestBase extends WebDriverTestBase {
  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

}
