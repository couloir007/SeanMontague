<?php

namespace Drupal\trail_mapper\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\trail_mapper\GeoJsonGenerator;

/**
 * Controller for generating GeoJSON.
 */
class GeoJsonController extends ControllerBase {

  /**
   * The GeoJSON generator service.
   *
   * @var \Drupal\trail_mapper\GeoJsonGenerator
   */
  protected $geoJsonGenerator;

  /**
   * Constructs a new GeoJsonController object.
   *
   * @param \Drupal\trail_mapper\GeoJsonGenerator $geoJsonGenerator
   *   The geojson generator service.
   */
  public function __construct(GeoJsonGenerator $geoJsonGenerator) {
    $this->geoJsonGenerator = $geoJsonGenerator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('trail_mapper.geojson_generator')
    );
  }

  /**
   * Generates the GeoJSON file.
   *
   * @return array
   *   A render array.
   */
  public function generate() {
    $this->geoJsonGenerator->generateGeoJson();
    return [
      '#markup' => $this->t('GeoJSON file generation triggered.'),
    ];
  }
}
