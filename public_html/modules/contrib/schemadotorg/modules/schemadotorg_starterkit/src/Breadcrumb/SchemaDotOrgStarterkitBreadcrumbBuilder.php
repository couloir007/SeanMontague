<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_starterkit\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides a breadcrumb builder for Schema.org starter kit.
 */
class SchemaDotOrgStarterkitBreadcrumbBuilder implements BreadcrumbBuilderInterface {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match, ?CacheableMetadata $cacheable_metadata = NULL): bool {
    // @todo Remove null safe operator in Drupal 12.0.0, see
    //   https://www.drupal.org/project/drupal/issues/3459277.
    $cacheable_metadata?->addCacheContexts(['route']);

    $route_name = $route_match->getRouteName() ?? '';
    return ((bool) preg_match('/^schemadotorg_starterkit\./', $route_name));
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match): Breadcrumb {
    $breadcrumb = new Breadcrumb();
    $breadcrumb->addLink(Link::createFromRoute($this->t('Home'), '<front>'));
    $breadcrumb->addLink(Link::createFromRoute($this->t('Administration'), 'system.admin'));
    $breadcrumb->addLink(Link::createFromRoute($this->t('Configuration'), 'system.admin_config'));
    $breadcrumb->addLink(Link::createFromRoute($this->t('Schema.org'), 'schemadotorg'));
    if (in_array($route_match->getRouteName(), ['schemadotorg_starterkit.confirm_form', 'schemadotorg_starterkit.details'])) {
      $breadcrumb->addLink(Link::createFromRoute($this->t('Starter kits'), 'schemadotorg_starterkit.overview'));
    }

    // This breadcrumb builder is based on a route parameter, and hence it
    // depends on the 'route' cache context.
    $breadcrumb->addCacheContexts(['route']);

    return $breadcrumb;
  }

}
