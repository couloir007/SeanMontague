<?php

namespace Drupal\geolocation_geometry\GeometryFormat;

use Drupal\geolocation_geometry\GeometryType\GeometryTypeInterface;
use Drupal\geolocation_geometry\GeometryType\LineString;
use Drupal\geolocation_geometry\GeometryType\Point;

/**
 * GPX support class.
 */
class GPX extends XML implements GeometryFormatInterface { // phpcs:ignore

  /**
   * {@inheritdoc}
   */
  public static function geometryByXML(?\SimpleXMLElement $xml = NULL): ?GeometryTypeInterface {

    switch (strtolower($xml->getName())) {
      case 'gpx':
      case 'trk':
        return static::parseChildren($xml);

      case 'trkseg':
        return new LineString(static::parseTrkseg($xml));

      case 'rte':
        return new LineString(static::parseRte($xml));

      case 'wpt':
        return new Point(static::extractCoordinates($xml));

      default:
        return NULL;
    }
  }

  /**
   * Extract coordinates from XML.
   *
   * @param \SimpleXMLElement $xml
   *   XML.
   *
   * @return string[]
   *   Coordinates.
   */
  protected static function extractCoordinates(\SimpleXMLElement $xml): array {
    $attributes = $xml->attributes();
    $longitude = (string) $attributes['lon'];
    $latitude = (string) $attributes['lat'];
    if (!$longitude || !$latitude) {
      throw new \Exception("Could not parse Geometry");
    }
    return [$longitude, $latitude];
  }

  /**
   * Parse Track segment.
   *
   * @param \SimpleXMLElement $xml
   *   XML.
   *
   * @return \Drupal\geolocation_geometry\GeometryType\Point[]
   *   Points.
   */
  protected static function parseTrkseg(\SimpleXMLElement $xml): array {
    $res = [];
    foreach ($xml->children() as $elem) {
      if (strtolower($elem->getName()) == "trkpt") {
        $res[] = new Point(static::extractCoordinates($elem));
      }
    }
    return $res;
  }

  /**
   * Parse route.
   *
   * @param \SimpleXMLElement $xml
   *   XML.
   *
   * @return \Drupal\geolocation_geometry\GeometryType\Point[]
   *   Points.
   */
  protected static function parseRte(\SimpleXMLElement $xml): array {
    $res = [];
    foreach ($xml->children() as $elem) {
      if (strtolower($elem->getName()) == "rtept") {
        $res[] = new Point(static::extractCoordinates($elem));
      }
    }
    return $res;
  }

}
