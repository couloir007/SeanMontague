<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_jsonld_breadcrumb;

use Drupal\Component\Utility\DeprecationHelper;
use Drupal\Core\Breadcrumb\ChainBreadcrumbBuilderInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;
use Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdManagerInterface;

/**
 * The Schema.org JSON-LD breadcrumb manager.
 */
class SchemaDotOrgJsonLdBreadcrumbManager implements SchemaDotOrgJsonLdBreadcrumbManagerInterface {

  /**
   * Constructs a SchemaDotOrgJsonLdBreadcrumbManager object.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Breadcrumb\ChainBreadcrumbBuilderInterface $breadcrumb
   *   The breadcrumb service.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager
   *   The Schema.org schema type manager.
   * @param \Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdManagerInterface $schemaJsonldManager
   *   The Schema.org JSON-LD manager.
   */
  public function __construct(
    protected RendererInterface $renderer,
    protected ChainBreadcrumbBuilderInterface $breadcrumb,
    protected SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager,
    protected SchemaDotOrgJsonLdManagerInterface $schemaJsonldManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function jsonLd(RouteMatchInterface $route_match, BubbleableMetadata $bubbleable_metadata): ?array {
    if (!$this->breadcrumb->applies($route_match)) {
      return NULL;
    }

    $breadcrumb = $this->breadcrumb->build($route_match);
    $links = $breadcrumb->getLinks();
    if (empty($links)) {
      return NULL;
    }

    $bubbleable_metadata->addCacheableDependency($breadcrumb);

    $items = [];
    $position = 1;
    foreach ($links as $link) {
      $id = $link->getUrl()->setAbsolute()->toString();
      $text = $link->getText();
      if (is_array($text)) {
        $text = DeprecationHelper::backwardsCompatibleCall(
          currentVersion: \Drupal::VERSION,
          deprecatedVersion: '10.3',
          currentCallable: fn() => $this->renderer->renderInIsolation($text),
          deprecatedCallable: fn() => $this->renderer->renderPlain($text),
        );
      }

      $items[] = [
        '@type' => 'ListItem',
        'position' => $position,
        'item' => [
          '@id' => $id,
          'name' => (string) $text,
        ],
      ];
      $position++;
    }

    // Append the current route's entity to breadcrumb item list.
    $entity = $this->schemaJsonldManager->getRouteMatchEntity($route_match);
    if ($entity) {
      $title = $entity->label();
      $uri = Url::fromRouteMatch($route_match)->setAbsolute()->toString();
      $items[] = [
        '@type' => 'ListItem',
        'position' => $position,
        'item' => [
          '@id' => $uri,
          'name' => $title,
        ],
      ];
    }

    return [
      '@context' => 'https://schema.org',
      '@type' => 'BreadcrumbList',
      'itemListElement' => $items,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function jsonLdAlter(array &$data, RouteMatchInterface $route_match, BubbleableMetadata $bubbleable_metadata): void {
    // Make sure the breadcrumb's JSON-LD exists.
    if (!isset($data['schemadotorg_jsonld_breadcrumb_schemadotorg_jsonld'])) {
      return;
    }

    // Move the breadcrumb's JSON-LD to the first https://schema.org/WebPage
    // that supports the https://schema.org/breacrumb property.
    foreach ($data as &$jsonld) {
      $schema_type = $jsonld['@type'] ?? NULL;
      if ($schema_type
        && $this->schemaTypeManager->hasProperty($schema_type, 'breadcrumb')) {
        $jsonld['breadcrumb'] = $data['schemadotorg_jsonld_breadcrumb_schemadotorg_jsonld'];
        unset($data['schemadotorg_jsonld_breadcrumb_schemadotorg_jsonld']);
        return;
      }
    }
  }

}
