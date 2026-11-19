<?php

namespace Drupal\trail_mapper_planner\Controller;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\trail_mapper_planner\Service\PlannerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Standalone planner page and its data endpoints.
 */
class PlannerController extends ControllerBase {

  const NETWORK_MAX_AGE = 86400;

  public function __construct(
    protected PlannerService $planner,
    protected CacheBackendInterface $cache,
    protected ExtensionPathResolver $pathResolver,
    protected LoggerInterface $logger,
  ) {}

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('trail_mapper_planner.planner'),
      $container->get('cache.default'),
      $container->get('extension.path.resolver'),
      $container->get('logger.channel.trail_mapper_planner'),
    );
  }

  /**
   * The standalone page: a complete HTML document, no Drupal theme.
   */
  public function page(Request $request, string $area): Response {
    if (!$this->planner->isAvailable()) {
      return $this->unavailable();
    }
    $settings = $this->config('trail_mapper_planner.settings')->get('areas')[$area];
    $base = $request->getBasePath() . '/' . $settings['path'] . '/map';
    $assets = $request->getBasePath() . '/' . $this->pathResolver->getPath('module', 'trail_mapper_planner') . '/assets';

    $config = json_encode([
      'urls' => [
        'network' => $base . '/network.json',
        'profile' => $base . '/profile',
        'gpx' => $base . '/gpx',
      ],
      'center' => $settings['center'],
      'zoom' => $settings['zoom'],
    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES);

    $html = strtr(self::TEMPLATE, [
      '{{title}}' => htmlspecialchars($settings['label']),
      '{{config}}' => $config,
      '{{assets}}' => htmlspecialchars($assets),
    ]);

    $response = new Response($html);
    $response->headers->set('Content-Type', 'text/html; charset=utf-8');
    return $response;
  }

  /**
   * Trail network GeoJSON, cached in Drupal and invalidated by /refresh.
   */
  public function network(string $area): Response {
    if (!$this->planner->isAvailable()) {
      return $this->unavailable();
    }
    $tags = ['trail_mapper_planner:' . $area];
    $cid = 'trail_mapper_planner:network:' . $area;

    if ($cached = $this->cache->get($cid)) {
      $json = $cached->data;
    }
    else {
      try {
        $json = $this->planner->network($area);
      }
      catch (\InvalidArgumentException) {
        return new Response('Bad request', 400);
      }
      catch (\Throwable $e) {
        return $this->failed($e);
      }
      $this->cache->set($cid, $json, time() + self::NETWORK_MAX_AGE, $tags);
    }

    $response = new CacheableResponse($json, 200, ['Content-Type' => 'application/json']);
    $response->addCacheableDependency((new CacheableMetadata())
      ->setCacheTags($tags)
      ->setCacheMaxAge(self::NETWORK_MAX_AGE));
    return $response;
  }

  /**
   * Elevation profile SVG fragment.
   */
  public function profile(Request $request, string $area): Response {
    return $this->output($request, $area, 'profile', 'image/svg+xml');
  }

  /**
   * GPX download.
   */
  public function gpx(Request $request, string $area): Response {
    $response = $this->output($request, $area, 'gpx', 'text/xml;charset=UTF-8');
    if ($response->getStatusCode() === 200) {
      $response->headers->set('Content-Disposition', 'attachment; filename="mtb_ride.gpx"');
    }
    return $response;
  }

  /**
   * Invalidates the cached network (replaces visiting the old index.php).
   */
  public function refresh(Request $request, string $area): RedirectResponse {
    Cache::invalidateTags(['trail_mapper_planner:' . $area]);
    $path = $this->config('trail_mapper_planner.settings')->get('areas')[$area]['path'];
    return new RedirectResponse($request->getBasePath() . '/' . $path . '/map');
  }

  /**
   * Shared body of the profile and gpx endpoints.
   */
  protected function output(Request $request, string $area, string $method, string $type): Response {
    if (!$this->planner->isAvailable()) {
      return $this->unavailable();
    }
    $ids = $request->query->all();
    $args = [];
    if ($method === 'profile') {
      // debug=1 is not a segment id.
      $args[] = ($ids['debug'] ?? '') === '1';
      unset($ids['debug']);
    }
    try {
      $body = $this->planner->$method($area, $ids, ...$args);
    }
    catch (\InvalidArgumentException) {
      return new Response('Bad request', 400);
    }
    catch (\Throwable $e) {
      return $this->failed($e);
    }
    $response = new Response($body, 200, ['Content-Type' => $type]);
    $response->setPrivate();
    return $response;
  }

  protected function unavailable(): Response {
    return new Response('The trail planner is temporarily unavailable.', 503, ['Content-Type' => 'text/plain']);
  }

  /**
   * Logs the failure class only; PDO messages can carry connection details.
   */
  protected function failed(\Throwable $e): Response {
    $this->logger->error('Planner request failed: @class', ['@class' => get_class($e)]);
    return $this->unavailable();
  }

  /**
   * Page markup, from leaflet/index.html.
   */
  const TEMPLATE = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <title>{{title}}</title>
    <meta name="viewport" content="initial-scale=1.0">
    <meta charset="utf-8">
    <script type="application/json" id="planner-config">{{config}}</script>
    <script
            src="https://code.jquery.com/jquery-3.4.1.min.js"
            integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo="
            crossorigin="anonymous"></script>

    <!-- Load Leaflet from CDN -->
    <script type="text/javascript" src="https://unpkg.com/leaflet@1.5.1/dist/leaflet.js"
        integrity="sha512-GffPMF3RvMeYyc1LWMHtK8EbPv0iNZ8/oTtHPx9/cc2ILxQ+u905qIwdpULaqDkyBKgOaB57QTMg7ztg8Jm2Og=="
        crossorigin=""></script>

    <!-- Load Esri Leaflet from CDN -->
    <script type="text/javascript" src="https://unpkg.com/esri-leaflet@2.3.1/dist/esri-leaflet.js"
        integrity="sha512-Np+ry4Dro5siJ1HZ0hTwn2jsmu/hMNrYw1EIK9EjsEVbDge4AaQhjeTGRg2ispHg7ZgDMVrSDjNrzH/kAO9Law=="
        crossorigin=""></script>

    <script type="text/javascript" src="{{assets}}/js/leaflet.ajax.min.js"></script>
    <script type="text/javascript" src="{{assets}}/js/leafletembed.js"></script>

    <link rel="stylesheet" type="text/css" href="{{assets}}/css/leaflet.css"/>
    <link rel="stylesheet" type="text/css" href="{{assets}}/css/map.css"/>
</head>
<body>
    <div id="list-container" class="table-up">
        <div id="top-stuff">
            <div id="basemaps-wrapper" class="leaflet-bar">
                <select id="basemaps">
                    <option value="Topographic">Topographic</option>
                    <option value="Streets">Streets</option>
                    <option value="NationalGeographic">National Geographic</option>
                    <option value="Oceans">Oceans</option>
                    <option value="Gray">Gray</option>
                    <option value="DarkGray">Dark Gray</option>
                    <option value="Imagery">Imagery</option>
                    <option value="ImageryClarity">Imagery (Clarity)</option>
                    <option value="ImageryFirefly">Imagery (Firefly)</option>
                    <option value="ShadedRelief">Shaded Relief</option>
                    <option value="Physical">Physical</option>
                </select>
            </div>
        </div>
        <div id="totalDist" style="padding: 10px 0">0 Miles</div>
        <ul id="list" tabindex="0"></ul>
    </div>

    <div class="profile-wrapper"><a href="#" id="close">X</a><div class="profile-inner"></div></div>

    <div id="map"></div>
    <div id="getProfile">Get Profile</div>
    <div id="clearSelection">Clear Selection</div>
    <div id="exportGPX">Export to GPX</div>

    <div id="cloneListWrapper">
        <li tabindex="-1">
            <div class="thumb" style="background-image: url('{{assets}}/images/profile.png');"></div>
            <div class="info">
                <div class="name"></div>
                <div class="length"></div>
            </div>
        </li>
    </div>

    <script type="text/javascript" src="{{assets}}/js/site.js"></script>
</body>
</html>
HTML;

}
