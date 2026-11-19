<?php

namespace Drupal\trail_mapper_planner\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * The only code that touches the trailmapper/ directory at the repo root.
 */
class PlannerService {

  /**
   * Maximum number of segment ids per request.
   */
  const MAX_IDS = 200;

  protected ?\PDO $conn = NULL;

  protected bool $loaded = FALSE;

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Absolute path of trailmapper/, which sits outside the docroot.
   */
  protected function root(): string {
    return dirname(DRUPAL_ROOT) . '/trailmapper';
  }

  /**
   * TRUE if trailmapper/ is present, configured and the pgsql driver loaded.
   */
  public function isAvailable(): bool {
    $root = $this->root();
    return is_file($root . '/src/planner.php')
      && is_file($root . '/src/db.php')
      && is_file($root . '/config/settings.php')
      && extension_loaded('pdo_pgsql');
  }

  /**
   * Whether the area is configured here.
   */
  public function hasArea(string $area): bool {
    return isset(($this->configFactory->get('trail_mapper_planner.settings')->get('areas') ?? [])[$area]);
  }

  /**
   * GeoJSON string of the trail network, built live from PostGIS.
   */
  public function network(string $area): string {
    $this->checkArea($area);
    $conn = $this->connection();
    return \trailmapper_network_geojson($conn, $area);
  }

  /**
   * Elevation profile SVG for the given ordered segment ids.
   *
   * With $debug, the flip array and gaps are appended as an HTML comment.
   */
  public function profile(string $area, array $ids, bool $debug = FALSE): string {
    $this->checkArea($area);
    $ids = $this->checkIds($ids);
    $conn = $this->connection();
    return \trailmapper_profile_svg($conn, $ids, $area, $debug);
  }

  /**
   * GPX document for the given ordered segment ids.
   */
  public function gpx(string $area, array $ids): string {
    $this->checkArea($area);
    $ids = $this->checkIds($ids);
    $conn = $this->connection();
    return \trailmapper_gpx($conn, $ids, $area);
  }

  /**
   * @throws \InvalidArgumentException
   */
  protected function checkArea(string $area): void {
    if (!$this->hasArea($area)) {
      throw new \InvalidArgumentException('Unknown area');
    }
  }

  /**
   * @throws \InvalidArgumentException
   */
  protected function checkIds(array $ids): array {
    if (!$ids || count($ids) > self::MAX_IDS) {
      throw new \InvalidArgumentException('Invalid number of ids');
    }
    foreach ($ids as $id) {
      if (!is_string($id) || !preg_match('/^[a-z]+[0-9]+$/D', $id)) {
        throw new \InvalidArgumentException('Invalid segment id');
      }
    }
    return $ids;
  }

  /**
   * Requires the trailmapper/ sources once and opens the PDO connection.
   */
  protected function connection(): \PDO {
    if (!$this->isAvailable()) {
      throw new \RuntimeException('TrailMapper is not available');
    }
    if (!$this->loaded) {
      require_once $this->root() . '/src/planner.php';
      $this->loaded = TRUE;
    }
    if (!$this->conn) {
      $this->conn = require $this->root() . '/src/db.php';
    }
    return $this->conn;
  }

}
