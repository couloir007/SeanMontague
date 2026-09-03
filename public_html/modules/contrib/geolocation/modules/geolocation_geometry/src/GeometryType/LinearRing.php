<?php

namespace Drupal\geolocation_geometry\GeometryType;

/**
 * Linear ring.
 *
 * @property \Drupal\geolocation_geometry\GeometryType\Point[] $components
 */
class LinearRing extends LineString {

  /**
   * Constructor.
   */
  public function __construct(array $components) {
    parent::__construct($components);

    if (!(reset($components)->equals(end($components)))) {
      throw new \Exception("LinearRing must be closed");
    }
    parent::__construct($components);
  }

  /**
   * Contains if ring contains geometry.
   *
   * @param \Drupal\geolocation_geometry\GeometryType\GeometryTypeInterface $geometry
   *   Geometry.
   *
   * @return bool
   *   Contains or not.
   */
  public function contains(GeometryTypeInterface $geometry): bool {
    if ($geometry instanceof Collection) {
      foreach ($geometry->components as $point) {
        if (!$this->contains($point)) {
          return FALSE;
        }
      }
      return TRUE;
    }
    elseif ($geometry instanceof Point) {
      return $this->containsPoint($geometry);
    }
    else {
      throw new \Exception("Not implemented");
    }
  }

  /**
   * Determine if ring contains point.
   *
   * @param \Drupal\geolocation_geometry\GeometryType\Point $point
   *   Point.
   *
   * @return bool
   *   Contains point or not.
   */
  protected function containsPoint(Point $point): bool {
    $px = round($point->getLongitude(), 14);
    $py = round($point->getLatitude(), 14);

    $crosses = 0;
    foreach (range(0, count($this->components) - 2) as $i) {
      $start = $this->components[$i];
      $x1 = round($start->getLongitude(), 14);
      $y1 = round($start->getLatitude(), 14);
      $end = $this->components[$i + 1];
      $x2 = round($end->getLongitude(), 14);
      $y2 = round($end->getLatitude(), 14);

      if ($y1 == $y2) {
        // Horizontal edge.
        if ($py == $y1) {
          // Point on horizontal line.
          if (
            // Right or vertical.
            $x1 <= $x2 && ($px >= $x1 && $px <= $x2)
            // Left or vertical.
            || $x1 >= $x2 && ($px <= $x1 && $px >= $x2)
          ) {
            // Point on edge.
            $crosses = -1;
            break;
          }
        }
        // Ignore other horizontal edges.
        continue;
      }

      $cx = round(((($x1 - $x2) * $py) + (($x2 * $y1) - ($x1 * $y2))) / ($y1 - $y2), 14);

      if ($cx == $px) {
        // Point on the line.
        if (
          // Upward.
          $y1 < $y2 && ($py >= $y1 && $py <= $y2)
          // Downward.
          ||$y1 > $y2 && ($py <= $y1 && $py >= $y2)
        ) {
          // Point on edge.
          $crosses = -1;
          break;
        }
      }
      if ($cx <= $px) {
        // No crossing to the right.
        continue;
      }
      if (
        $x1 != $x2
        && (
          $cx < min($x1, $x2)
          || $cx > max($x1, $x2)
        )
      ) {
        // No crossing.
        continue;
      }
      if (
        // Upward.
        $y1 < $y2 && ($py >= $y1 && $py < $y2)
        // Downward.
        || $y1 > $y2 && ($py < $y1 && $py >= $y2)
      ) {
        $crosses++;
      }
    }

    if ($crosses == -1) {
      // Point is on the edge.
      $contained = TRUE;
    }
    elseif ($crosses % 2 == 0) {
      // Number of crosses is even => outside.
      $contained = FALSE;
    }
    else {
      // Number of crosses is odd => inside.
      $contained = TRUE;
    }

    return $contained;
  }

}
