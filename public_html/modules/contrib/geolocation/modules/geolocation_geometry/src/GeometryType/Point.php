<?php

namespace Drupal\geolocation_geometry\GeometryType;

/**
 * Point geometry type.
 */
class Point extends GeometryTypeBase {

  /**
   * Longitude.
   *
   * @var float
   */
  private float $longitude;

  /**
   * Latitude.
   *
   * @var float
   */
  private float $latitude;

  /**
   * Constructor.
   */
  public function __construct(array $coordinates) {
    parent::__construct();

    if (count($coordinates) < 2) {
      throw new \Exception("Point must have two coordinates");
    }

    $longitude = $coordinates[0];
    if (
      !is_numeric($longitude)
      || $longitude < -180.01
      || $longitude > 180.01
    ) {
      throw new \Exception('Longitude ' . $longitude . ' is out of range');
    }
    $this->longitude = (float) $longitude;

    $latitude = $coordinates[1];
    if (
      !is_numeric($latitude)
      || $latitude < -90.01
      || $latitude > 90.01
    ) {
      throw new \Exception('Latitude ' . $latitude . ' is out of range');
    }
    $this->latitude = (float) $latitude;
  }

  /**
   * Get longitude.
   *
   * @return ?float
   *   Longitude.
   */
  public function getLongitude(): ?float {
    return $this->longitude;
  }

  /**
   * Get Latitude.
   *
   * @return ?float
   *   Latitude.
   */
  public function getLatitude(): ?float {
    return $this->latitude;
  }

  /**
   * {@inheritdoc}
   */
  public function toWKT(): string {
    return strtoupper($this->type) . "(" . $this->longitude . " " . $this->latitude . ")";
  }

  /**
   * {@inheritdoc}
   */
  public function toKML(): string {
    return "<" . $this->type . "><coordinates>" . $this->longitude . ',' . $this->latitude . "</coordinates></" . $this->type . ">";
  }

  /**
   * {@inheritdoc}
   */
  public function toGPX(?string $mode = NULL): string {
    if (!$mode) {
      $mode = "wpt";
    }
    if ($mode != "wpt") {
      throw new \Exception("Unimplemented");
    }
    return '<wpt lon="' . $this->longitude . '" lat="' . $this->latitude . '"></wpt>"';
  }

  /**
   * {@inheritdoc}
   */
  public function toGeoJSON(): string {
    $value = (object) ['type' => $this->type, 'coordinates' => [$this->longitude, $this->latitude]];
    return json_encode($value);
  }

  /**
   * {@inheritdoc}
   */
  public function equals(GeometryTypeInterface $geometry): bool {
    /** @var \Drupal\geolocation_geometry\GeometryType\Point $geometry */
    if ($geometry->type != $this->type) {
      return FALSE;
    }
    return $geometry->latitude == $this->latitude && $geometry->longitude == $this->longitude;
  }

}
