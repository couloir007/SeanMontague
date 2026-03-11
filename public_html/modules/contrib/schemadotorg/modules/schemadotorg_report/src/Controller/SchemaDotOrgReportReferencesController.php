<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_report\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Url;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeBuilderInterface;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;
use Drupal\schemadotorg_report\Traits\SchemaDotOrgReportBuildTrait;

/**
 * Returns responses for Schema.org report references routes.
 */
class SchemaDotOrgReportReferencesController extends ControllerBase {
  use SchemaDotOrgReportBuildTrait;

  /**
   * Constructs a SchemaDotOrgReportReferencesController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager
   *   The Schema.org schema type manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeBuilderInterface $schemaTypeBuilder
   *   The Schema.org schema type builder.
   */
  public function __construct(
    protected Connection $database,
    protected SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager,
    protected SchemaDotOrgSchemaTypeBuilderInterface $schemaTypeBuilder,
  ) {}

  /**
   * Builds the Schema.org references.
   *
   * @return array
   *   A renderable array containing the Schema.org references.
   */
  public function index(): array {
    $config = $this->config('schemadotorg_report.settings');

    $build = [];

    // About.
    $about = $config->get('about');
    if ($about) {
      $build['about'] = [
        'title' => [
          '#markup' => $this->t('About'),
          '#prefix' => '<h2>',
          '#suffix' => '</h2>',
        ],
        'links' => [
          '#theme' => 'item_list',
          '#items' => $this->buildReportLinks($about),
        ],
      ];
    }

    // Links to references and issues/discussions.
    $links = [
      'types' => $this->t('References'),
      'issues' => $this->t('Issues/Discussions'),
    ];
    foreach ($links as $name => $title) {
      $type_links = $config->get($name);
      if ($type_links) {
        $build[$name]['title'] = [
          '#markup' => $title,
          '#prefix' => '<h2>',
          '#suffix' => '</h2>',
        ];
        $build[$name]['types'] = [];
        foreach ($type_links as $type => $links) {
          $build[$name]['types'][$type] = [
            '#theme' => 'item_list',
            '#title' => [
              '#type' => 'link',
              '#title' => $type,
              '#url' => Url::fromRoute('schemadotorg_report', ['id' => $type]),
              '#attributes' => ['id' => $type],
            ],
            '#items' => $this->buildReportLinks($links),
          ];
        }
      }
    }

    return $build;
  }

}
