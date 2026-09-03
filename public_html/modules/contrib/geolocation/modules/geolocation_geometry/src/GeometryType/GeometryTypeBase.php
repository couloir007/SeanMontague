<?php

namespace Drupal\geolocation_geometry\GeometryType;

/**
 * Geometry base class.
 */
abstract class GeometryTypeBase implements GeometryTypeInterface {

  /**
   * Class name.
   *
   * @var string
   */
  protected string $type;

  /**
   * Components.
   *
   * @var GeometryTypeInterface[]
   */
  protected array $components;

  /**
   * Constructor.
   */
  public function __construct() {
    $this->type = (new \ReflectionClass($this))->getShortName();
  }

  /**
   * Calculate distance.
   *
   * Calculates the great-circle distance between two points, with the
   * Vincenty formula.
   */
  public static function distanceByCoordinates(float $latitudeFrom, float $longitudeFrom, float $latitudeTo, float $longitudeTo, float $earthRadius = 6371000): float {
    // Convert from degrees to radians.
    $latFrom = deg2rad($latitudeFrom);
    $lonFrom = deg2rad($longitudeFrom);
    $latTo = deg2rad($latitudeTo);
    $lonTo = deg2rad($longitudeTo);

    $lonDelta = $lonTo - $lonFrom;
    $a = pow(cos($latTo) * sin($lonDelta), 2) +
      pow(cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta), 2);
    $b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);

    $angle = atan2(sqrt($a), $b);
    return $angle * $earthRadius;
  }

}
