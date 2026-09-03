<?php

namespace Drupal\geolocation_geometry\GeometryFormat;

use Drupal\geolocation_geometry\GeometryType\GeometryCollection;
use Drupal\geolocation_geometry\GeometryType\GeometryTypeInterface;
use Drupal\geolocation_geometry\GeometryType\LineString;
use Drupal\geolocation_geometry\GeometryType\LinearRing;
use Drupal\geolocation_geometry\GeometryType\MultiLineString;
use Drupal\geolocation_geometry\GeometryType\MultiPoint;
use Drupal\geolocation_geometry\GeometryType\MultiPolygon;
use Drupal\geolocation_geometry\GeometryType\Point;
use Drupal\geolocation_geometry\GeometryType\Polygon;

/**
 * KML format type.
 */
class KML extends XML implements GeometryFormatInterface { // phpcs:ignore

  /**
   * {@inheritdoc}
   */
  public static function geometryByXML(?\SimpleXMLElement $xml = NULL): ?GeometryTypeInterface {

    switch (strtolower($xml->getName())) {
      case "kml":
      case "document":
      case "placemark":
        return static::parseChildren($xml);

      case "point":
        return new Point(static::parsePoint($xml));

      case "linestring":
        return new LineString(static::parseLineString($xml));

      case "linearring":
        return new LinearRing(static::parseLinearRing($xml));

      case "polygon":
        return new Polygon(static::parsePolygon($xml));

      case "multigeometry":
        $components = static::parseMultiGeometry($xml);

        if (count($components)) {
          $possibletype = $components[0]::TYPE;
          $sametype = TRUE;
          foreach (array_slice($components, 1) as $component) {
            if ($component::TYPE != $possibletype) {
              $sametype = FALSE;
              break;
            }
          }
          if ($sametype) {
            switch ($possibletype) {
              case "Point":
                return new MultiPoint($components);

              case "LineString":
                return new MultiLineString($components);

              case "Polygon":
                return new MultiPolygon($components);

              default:
                break;
            }
          }
        }
        return new GeometryCollection($components);

      default:
        return NULL;
    }
  }

  /**
   * Parse point.
   *
   * @param \SimpleXMLElement $xml
   *   XML.
   *
   * @return array
   *   Coordinates.
   */
  protected static function parsePoint(\SimpleXMLElement $xml): array {
    $coordinates = static::extractCoordinates($xml);

    $coords = explode(',', $coordinates[0]);
    return array_map("trim", $coords);
  }

  /**
   * Parse Line String.
   *
   * @param \SimpleXMLElement $xml
   *   XML.
   *
   * @return array
   *   Components.
   */
  protected static function parseLineString(\SimpleXMLElement $xml): array {
    $components = [];
    $coordinates = static::extractCoordinates($xml);
    foreach (preg_split('/\s+/', trim((string) $coordinates[0])) as $compstr) {
      $coords = explode(',', $compstr);
      $components[] = new Point($coords);
    }
    return $components;
  }

  /**
   * Parse Linear Ring.
   *
   * @param \SimpleXMLElement $xml
   *   XML.
   *
   * @return array
   *   Components.
   */
  protected static function parseLinearRing(\SimpleXMLElement $xml): array {
    return static::parseLineString($xml);
  }

  /**
   * Parse Polygon.
   *
   * @param \SimpleXMLElement $xml
   *   XML.
   *
   * @return array
   *   Components.
   */
  protected static function parsePolygon(\SimpleXMLElement $xml): array {
    $ring = [];
    foreach (static::childElements($xml, 'outerboundaryis') as $elem) {
      $ring = array_merge($ring, static::childElements($elem, 'linearring'));
    }

    if (count($ring) != 1) {
      throw new \Exception("Could not parse Geometry");
    }

    $components = [new LinearRing(static::parseLinearRing($ring[0]))];
    foreach (static::childElements($xml, 'innerboundaryis') as $elem) {
      foreach (static::childElements($elem, 'linearring') as $ring) {
        $components[] = new LinearRing(static::parseLinearRing($ring[0]));
      }
    }
    return $components;
  }

  /**
   * Parse Multi Geometry.
   *
   * @param \SimpleXMLElement $xml
   *   XML.
   *
   * @return array
   *   Components.
   */
  protected static function parseMultiGeometry(\SimpleXMLElement $xml): array {
    $components = [];
    foreach ($xml->children() as $child) {
      $components[] = static::geometryByXML($child);
    }
    return $components;
  }

  /**
   * Extract Coordinates.
   *
   * @param \SimpleXMLElement $xml
   *   XML.
   *
   * @return array
   *   Coordinates.
   */
  protected static function extractCoordinates(\SimpleXMLElement $xml): array {
    $coordinates = static::childElements($xml, 'coordinates');
    if (count($coordinates) != 1) {
      throw new \Exception("Could not parse Geometry");
    }
    return $coordinates;
  }

}
