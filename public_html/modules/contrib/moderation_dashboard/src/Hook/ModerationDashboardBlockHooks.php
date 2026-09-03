<?php

namespace Drupal\moderation_dashboard\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Hooks for blocks.
 */
class ModerationDashboardBlockHooks {

  public function __construct(protected RouteMatchInterface $routeMatch) {}

  /**
   * Implements hook_preprocess_block().
   */
  #[Hook('preprocess_block')]
  public function preprocessBlock(&$variables): void {
    $route_name = $this->routeMatch->getRouteName();

    if (!empty($route_name) && str_starts_with($route_name, 'view.moderation_dashboard.page_1')) {
      $variables['attributes']['class'][] = 'moderation-dashboard-block';
    }
  }

  /**
   * Implements hook_plugin_filter_TYPE__CONSUMER_alter().
   *
   * Remove moderation dashboard visibility conditions from general block
   * configuration.
   */
  #[Hook('plugin_filter_condition__block_ui_alter')]
  public function pluginFilterConditionBlockUiAlter(array &$definitions): void {
    unset($definitions['has_moderated_content_type']);
    unset($definitions['moderation_dashboard_access']);
  }

}
