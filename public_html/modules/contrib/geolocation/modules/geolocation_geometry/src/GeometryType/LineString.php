<?php

namespace Drupal\geolocation_geometry\GeometryType;

/**
 * Line string.
 *
 * @property \Drupal\geolocation_geometry\GeometryType\Point[] $components
 */
class LineString extends MultiPoint {

  /**
   * Constructor.
   */
  public function __construct(array $components) {
    parent::__construct($components);

    if (count($components) < 2) {
      throw new \Exception("LineString must have at least 2 points");
    }
    parent::__construct($components);
  }

  /**
   * {@inheritdoc}
   */
  public function toKML(): string {

    $kml = '<' . $this->type . '><coordinates>';
    foreach ($this->components as $point) {
      $kml .= $point->getLongitude() . ',' . $point->getLatitude() . " ";
    }
    $kml .= '</coordinates></' . $this->type . '>';

    return $kml;
  }

  /**
   * {@inheritdoc}
   */
  public function toGPX(?string $mode = 'trkseg'): string {

    switch ($mode) {

      case 'trkseg':
        $gpx = '<trkseg>';
        foreach ($this->components as $point) {
          $gpx .= '<trkpt lon="' . $point->getLongitude() . '" lat="' . $point->getLatitude() . '"></trkpt>';
        }
        $gpx .= '</trkseg>';
        return $gpx;

      case 'rte':
        $gpx = '<rte>';
        foreach ($this->components as $point) {
          $gpx .= '<rtept lon="' . $point->getLongitude() . '" lat="' . $point->getLatitude() . '"></rtept>';
        }
        $gpx .= '</rte>';
        return $gpx;

      default:
        throw new \Exception("GPX mode unimplemented");
    }
  }

}
