<?php

namespace Drupal\leaflet_full_page\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\leaflet_full_page\Service\LeafletEdanObjectService;

/**
 * Controller to test EDAN object fetching via leaflet_full_page.
 */
class EdanTestController extends ControllerBase {

  /**
   * The Leaflet EDAN object service.
   *
   * @var \Drupal\leaflet_full_page\Service\LeafletEdanObjectService
   */
  protected $edanService;

  public function __construct(LeafletEdanObjectService $edanService) {
    $this->edanService = $edanService;
  }

  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('leaflet_full_page.edan_object_service')
    );
  }

  /**
   * Test endpoint to fetch an EDAN Object and return a render array with iframe.
   *
   * Usage:
   *  - /admin/config/development/leaflet-full-page/edan-test/edanmdm:nmnhanthro_12345
   *  - /admin/config/development/leaflet-full-page/edan-test (with ?url=url:edanmdm:...)
   *
   * Parameter precedence:
   *  - If route/path {edan_id} is provided, use it.
   *  - Else, if query edan_id is provided, use it.
   *  - Else, if "url" is provided, uses getObjectByUrl(url).
   *  - Else, falls back to a sample id for demonstration.
   *
   * @param string|null $edan_id
   *   Optional edanmdm ID path parameter.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return array
   *   Drupal render array.
   */
  public function test(?string $edan_id, Request $request) {
    try {
      // Prefer explicit path parameter when provided.
      $path_edan_id = $request->attributes->get('edan_id');
      $query_edan_id = $request->query->get('edan_id');
      $edan_url = $request->query->get('url');

      if (!empty($edan_url)) {
        $result = $this->edanService->getObjectByUrl($edan_url);
      }
      else {
        $effective_id = $path_edan_id ?: ($edan_id ?: ($query_edan_id ?: 'edanmdm:nmnhanthro_12345'));
        $result = $this->edanService->getObjectById($effective_id);
      }

      // Attempt to extract first media URL for iframe display.
      $media = $result['data']['content']['descriptiveNonRepeating']['online_media']['media'] ?? [];
      if (!empty($media) && !empty($media[0]['content'])) {
        $first = $media[0]['content'];
        return [
          '#type' => 'container',
          '#attributes' => ['style' => 'height: 100vh;'],
          'iframe' => [
            '#markup' => '<iframe class="" id="Images" title="Images viewer for" src="' . htmlspecialchars($first, ENT_QUOTES) . '&container.fullpage&inline=true" width="100%" height="100%" scrolling="no"></iframe>',
          ],
        ];
      }

      // Fallback: show success and basic info if no media found.
      return [
        '#markup' => '<div>No media found on the EDAN record.</div>',
      ];
    }
    catch (\Throwable $e) {
      return [
        '#markup' => '<div>Error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES) . '</div>',
      ];
    }
  }

}
