<?php

declare(strict_types=1);

namespace Drupal\drupalsky\Hook;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 *
 */
class DrupalSkyHooks {

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_name) {
      case 'help.page.drupalsky':
        $output  = <<<EOF
          <h2>DrupalSky Help</h2>
          <p>This module provides integration with Bluesky.</p>
          <h3>Setup</h3>
          <ol>
            <li>Obtain an <a href="https://blueskyfeeds.com/en/faq-app-password">App Password</a> for your BlueSky account. Do not use your login password.</li>
            <li>Create a new Key at <a href="/admin/config/system/keys">/admin/config/system/keys</a>. This will be an Authentication key and will hold your App Password.</li>
            <li>Go to the Drupalsky settings at <a href="/admin/config/services/dskysettings">/admin/config/services/dskysettings</a>. Enter your Bluesky handle and select the Key you saved</li>
            <li>Go to your user profile and you will now see a Bluesky tab</li>
          </ol>
        EOF;

        return $output;
    }
  }


  // End of class.
}
