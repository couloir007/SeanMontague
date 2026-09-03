<?php

namespace Drupal\moderation_dashboard\Hook;

use Drupal\Core\Asset\LibrariesDirectoryFileFinder;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;

/**
 * General hooks.
 */
class ModerationDashboardGeneralHooks {

  public function __construct(
    protected AccountProxyInterface $currentUser,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ConfigFactoryInterface $configFactory,
    protected LibrariesDirectoryFileFinder $librariesDirectoryFileFinder,
  ) {
  }

  /**
   * Implements preprocess_HOOK().
   */
  #[Hook('preprocess_links__toolbar_user')]
  public function preprocessLinksToolbarUser(&$variables): void {
    if (isset($variables['links'])) {
      $user = $this->currentUser;

      if ($user->hasPermission('use moderation dashboard')) {
        $view = $this->entityTypeManager
          ->getStorage('view')
          ->load('moderation_dashboard');
        if ($view && $view->status()) {
          $view = $view->getExecutable();
          $view->setDisplay('page_1');

          $link_element = [
            '#type' => 'link',
            '#title' => $view->getTitle(),
            '#url' => Url::fromRoute('view.moderation_dashboard.page_1', ['user' => $user->id()]),
            '#attributes' => [
              'title' => t('View the @title page', ['@title' => $view->getTitle()]),
            ],
          ];

          $variables['links']['moderation_dashboard'] = [
            'link' => $link_element,
            'text' => $view->getTitle(),
          ];
        }
      }

    }
  }

  /**
   * Implements library_info_alter().
   */
  #[Hook('library_info_alter')]
  public function libraryInfoAlter(&$libraries, $extension): void {
    if ($extension === 'moderation_dashboard' && isset($libraries['activity'])) {
      if (!$this->configFactory->get('moderation_dashboard.settings')->get('chart_js_cdn')) {
        $libraries_directory_file_finder = $this->librariesDirectoryFileFinder;
        $lib_chartjs = $libraries_directory_file_finder->find('chartjs/dist');
        $lib_chartjs_only = $libraries_directory_file_finder->find('chartjs');

        $chart_js_internal = 'moderation_dashboard/chart.js.internal';
        $chart_js_internal_js_only = 'moderation_dashboard/chart.js.internal_js_only';
        $chart_js_internal_npm = 'moderation_dashboard/chart.js.internal_npm';

        $libraries['activity']['dependencies'][] = $lib_chartjs ? $chart_js_internal : ($lib_chartjs_only ? $chart_js_internal_js_only : $chart_js_internal_npm);
        return;
      }
      $libraries['activity']['dependencies'][] = 'moderation_dashboard/chart.js.external';
    }
  }

}
