<?php

namespace Drupal\Tests\geolocation_geometry\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests drawing a multi polygon with the Terra Draw widget on a Leaflet map.
 *
 * The demo content type gets a multi polygon field, whose widget is driven the
 * way a user drives it: through the map's own Javascript.
 *
 * @group geolocation
 */
#[RunTestsInSeparateProcesses]
class GeolocationGeometryTerraDrawMultiWidgetTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected $profile = 'minimal';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'field',
    'user',
    'geolocation',
    'geolocation_leaflet',
    'geolocation_geometry',
    'geolocation_geometry_demo',
  ];

  /**
   * Multi polygon field added to the demo content type.
   */
  protected const FIELD_NAME = 'field_geometry_multipolygon';

  /**
   * Map feature ID of the widget's Terra Draw connector.
   */
  protected const FEATURE_ID = 'geolocation_geometry_terra_draw_multi_connector';

  /**
   * Map provider the widget draws on.
   *
   * Terra Draw talks to each map through an adapter of its own, so what is
   * drawn has to arrive the same way whichever provider renders the map.
   */
  protected const MAP_PROVIDER_ID = 'leaflet';

  /**
   * Corners of the triangles to draw, as pixel offsets in the map container.
   *
   * @var array<string, array<int, array<string, int>>>
   */
  protected array $triangles = [
    'first' => [
      ['x' => 60, 'y' => 60],
      ['x' => 160, 'y' => 60],
      ['x' => 110, 'y' => 160],
    ],
    'second' => [
      ['x' => 220, 'y' => 60],
      ['x' => 320, 'y' => 60],
      ['x' => 270, 'y' => 160],
    ],
    'third' => [
      ['x' => 380, 'y' => 60],
      ['x' => 480, 'y' => 60],
      ['x' => 430, 'y' => 160],
    ],
  ];

  /**
   * A multi polygon of two parts, as stored on the node beforehand.
   */
  protected string $storedGeometry = '{"type":"MultiPolygon","coordinates":[[[[7,50],[8,50],[8,51],[7,50]]],[[[10,50],[11,50],[11,51],[10,50]]]]}';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    FieldStorageConfig::create([
      'field_name' => static::FIELD_NAME,
      'entity_type' => 'node',
      'type' => 'geolocation_geometry_multipolygon',
      'cardinality' => 1,
    ])->save();

    FieldConfig::create([
      'field_name' => static::FIELD_NAME,
      'entity_type' => 'node',
      'bundle' => 'geolocation_geometry_demo',
      'label' => 'Geolocation Geometry MultiPolygon',
    ])->save();

    // Map provider settings are left to the map provider's own defaults, which
    // the map element applies while rendering - among them the default data
    // layer the geometries are rendered into.
    EntityFormDisplay::load('node.geolocation_geometry_demo.default')
      ->setComponent(static::FIELD_NAME, [
        'type' => 'geolocation_geometry_terra_draw_multi',
        'settings' => [
          'map_provider_id' => static::MAP_PROVIDER_ID,
        ],
      ])
      ->save();

    $this->drupalLogin($this->drupalCreateUser([
      'access content',
      'create geolocation_geometry_demo content',
      'edit any geolocation_geometry_demo content',
    ]));
  }

  /**
   * Tests drawing a multi polygon onto an existing node.
   */
  public function testDrawMultiPolygon(): void {
    $node = $this->drupalCreateNode([
      'type' => 'geolocation_geometry_demo',
      'title' => 'Known content',
    ]);

    $this->drupalGet($node->toUrl('edit-form'));

    // Everything drawn on the map makes up the geometry of the one item, so
    // two triangles have to arrive as the two parts of one multi polygon.
    $this->drawPolygon($this->triangles['first']);
    $this->assertGeometryParts(1);

    $this->drawPolygon($this->triangles['second']);
    $drawn = $this->assertGeometryParts(2);
    $geometry = json_decode($drawn, TRUE);

    foreach ([$this->triangles['first'], $this->triangles['second']] as $part => $corners) {
      $this->assertRing($geometry['coordinates'][$part], count($corners));
    }

    // What was drawn is what got stored.
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains('has been updated');

    // Saving happened in the browser, but it invalidated the node's cache tag,
    // so loading it here yields what was stored.
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->container->get('entity_type.manager')->getStorage('node')->load($node->id());

    $this->assertSame($drawn, $node->get(static::FIELD_NAME)->first()?->get('geojson')->getValue());
  }

  /**
   * Tests drawing on top of a multi polygon the node already holds.
   */
  public function testDrawOnStoredMultiPolygon(): void {
    $node = $this->drupalCreateNode([
      'type' => 'geolocation_geometry_demo',
      'title' => 'Known content',
      static::FIELD_NAME => $this->storedGeometry,
    ]);

    $this->drupalGet($node->toUrl('edit-form'));

    // The stored geometry is rendered server side as a single shape, which
    // Terra Draw takes off the map to render one drawable part per polygon.
    $feature_id = static::FEATURE_ID;
    $this->assertJsCondition("(() => {
      const map = window.Drupal?.geolocation?.maps?.initializedMaps?.values().next().value;
      const feature = map?.features?.get('$feature_id');
      return map?.dataLayers?.get('default')?.shapes.length === 0 && feature?.getParts().length === 2;
    })()", 30000);

    $this->drawPolygon($this->triangles['third']);
    $drawn = $this->assertGeometryParts(3);
    $drawn_geometry = json_decode($drawn, TRUE);

    // The parts taken over come back unchanged, the drawn one is appended.
    $stored_geometry = json_decode($this->storedGeometry, TRUE);
    $this->assertSame($stored_geometry['coordinates'], array_slice($drawn_geometry['coordinates'], 0, 2));
    $this->assertRing($drawn_geometry['coordinates'][2], count($this->triangles['third']));

    // What was drawn on top of the stored parts is what got stored.
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains('has been updated');

    // Saving happened in the browser, but it invalidated the node's cache tag,
    // so loading it here yields what was stored.
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->container->get('entity_type.manager')->getStorage('node')->load($node->id());

    $this->assertSame($drawn, $node->get(static::FIELD_NAME)->first()?->get('geojson')->getValue());
  }

  /**
   * Draw one polygon on the map, the way a user would.
   *
   * @param array<int, array<string, int>> $corners
   *   Corners, as pixel offsets in the map container.
   */
  protected function drawPolygon(array $corners): void {
    // The control is rendered server side but only put on the map once the
    // connector loaded Terra Draw, so waiting for it to become visible is what
    // makes drawing arrive at a map that can take it.
    $button = $this->assertSession()->waitForElementVisible('css', '.terra-draw-control button[data-terra-draw-mode="draw"]', 30000);
    $this->assertNotEmpty($button, 'Drawing mode can be entered.');
    $button->click();

    $positions = json_encode($corners);

    // Terra Draw binds its pointer listeners to one element per adapter, and
    // treats an event as a click only where it targets that element - so the
    // map provider says which one rather than the events being aimed at
    // whatever happens to be under the point.
    $event_element = $this->mapEventElementScript();
    $positions = json_encode($corners);

    $this->getSession()->executeScript(<<<JS
(() => {
  // The form renders this widget's map and no other, so the controls queried
  // above and the element drawn on below are the ones of that map.
  const maps = Drupal.geolocation.maps.initializedMaps;
  if (maps.size !== 1) {
    throw new Error("Expected exactly one map on the form, found " + maps.size + ".");
  }

  const map = maps.values().next().value;
  const target = $event_element;
  const rect = target.getBoundingClientRect();

  const pointer = (type, position, buttons) => {
    target.dispatchEvent(new PointerEvent(type, {
      bubbles: true,
      cancelable: true,
      isPrimary: true,
      pointerId: 1,
      pointerType: 'mouse',
      button: 0,
      buttons: buttons,
      clientX: rect.left + position.x,
      clientY: rect.top + position.y,
    }));
  };

  $positions.forEach((position) => {
    pointer('pointerdown', position, 1);
    pointer('pointerup', position, 0);
  });

  // Finish the polygon, as the Enter key does for a user. Left unfinished,
  // the ring keeps the vertex that follows the cursor while drawing.
  target.dispatchEvent(new KeyboardEvent('keyup', {bubbles: true, key: 'Enter'}));
})();
JS);
  }

  /**
   * Javascript expression for the element Terra Draw listens on.
   *
   * Evaluated with the geolocation map in scope as "map".
   *
   * @return string
   *   Javascript expression.
   */
  protected function mapEventElementScript(): string {
    return 'map.container';
  }

  /**
   * Assert the field input to hold a multi polygon of so many parts.
   *
   * @param int $count
   *   Number of parts.
   *
   * @return string
   *   The GeoJSON the input holds.
   */
  protected function assertGeometryParts(int $count): string {
    $input_name = static::FIELD_NAME . '[0][geojson]';

    $this->assertJsCondition("(() => {
      const input = document.querySelector('[name=\"$input_name\"]');
      if (!input?.value) {
        return false;
      }
      const geometry = JSON.parse(input.value);
      return geometry.type === 'MultiPolygon' && geometry.coordinates.length === $count;
    })()");

    return $this->assertSession()->fieldExists($input_name)->getValue();
  }

  /**
   * Assert a polygon part to be a closed ring of as many corners as clicked.
   *
   * Which position each corner ends up at is Terra Draw's projection, not this
   * module's - it hands over the coordinates of its parts untouched. That the
   * coordinates survive unaltered is asserted on the stored geometry instead.
   *
   * @param array<int, array<int, float[]>> $part
   *   Coordinates of one multi polygon part.
   * @param int $corners
   *   Number of corners clicked for this part.
   */
  protected function assertRing(array $part, int $corners): void {
    $this->assertCount(1, $part, 'A drawn polygon has no holes.');

    $ring = $part[0];
    $this->assertCount($corners + 1, $ring, 'The ring holds one position per corner, plus the closing one.');
    $this->assertSame($ring[0], $ring[$corners], 'The ring is closed.');
  }

}
