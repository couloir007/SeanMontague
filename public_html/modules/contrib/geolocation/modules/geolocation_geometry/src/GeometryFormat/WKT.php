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
 * WKT geometry format.
 */
class WKT implements GeometryFormatInterface { // phpcs:ignore

  /**
   * {@inheritdoc}
   */
  public static function geometryByText(?string $text = NULL): ?GeometryTypeInterface {
    if (empty($text)) {
      return NULL;
    }

    if (!preg_match('/\s*(\w+)\s*\(\s*(.*)\s*\)\s*$/', strtolower($text), $matches)) {
      throw new \Exception("Could not parse Geometry");
    }

    $type = match($matches[1]) {
      'point' => 'Point',
      'multipoint' => 'MultiPoint',
      'linestring' => 'LineString',
      'multilinestring' => 'MultiLineString',
      'linerarring' => 'LinearRing',
      'polygon' => 'Polygon',
      'multipolygon' => 'MultiPolygon',
      'geometrycollection' => 'GeometryCollection',
      default => NULL,
    };

    $value = trim($matches[2]);

    return static::geometryByTypeAndValue($type, $value);
  }

  /**
   * Get geometry.
   *
   * @param string $type
   *   Type.
   * @param string $value
   *   Value.
   *
   * @return \Drupal\geolocation_geometry\GeometryType\GeometryTypeInterface|null
   *   Geometry.
   */
  public static function geometryByTypeAndValue(string $type, string $value): ?GeometryTypeInterface {

    switch ($type) {
      case 'Point':
        return new Point(preg_split('/\s+/', $value));

      case 'MultiPoint':
      case 'LineString':
      case 'LinearRing':
        $points = [];
        foreach (explode(',', $value) as $point) {
          $points[] = new Point(preg_split('/\s+/', $point));
        }

        $geometry = match($type) {
          'MultiPoint' => new MultiPoint($points),
          'LineString' => new LineString($points),
          'LinearRing' => new LinearRing($points),
        };
        return $geometry;

      case 'MultiLineString':
      case 'Polygon':
      case 'MultiPolygon':
        $components = [];
        /** @var string $subvalue */
        foreach (preg_split('/\)\s*,\s*\(/', $value) as $subvalue) {
          if (($subvalue[0] ?? FALSE) === '(') {
            $subvalue = substr($subvalue, 1);
          }
          if (($subvalue[strlen($subvalue) - 1] ?? FALSE) == ')') {
            $subvalue = substr($subvalue, 0, -1);
          }

          $components[] = static::geometryByTypeAndValue(match($type) {
            'MultiLineString' => 'LineString',
            'Polygon' => 'LinearRing',
            'MultiPolygon' => 'Polygon',
          }, $subvalue);
        }

        $geometry = match($type) {
          'MultiLineString' => new MultiLineString($components),
          'Polygon' => new Polygon($components),
          'MultiPolygon' => new MultiPolygon($components),
        };

        return $geometry;

      case 'GeometryCollection':
        $components = [];
        foreach (preg_split('/,\s*(?=[A-Za-z])/', $value) as $part) {
          $components[] = static::geometryByText($part);
        }
        return new GeometryCollection($components);

      default:
        throw new \Exception('Unknown WKT Type "' . $type . '" encountered.');
    }
  }

}
