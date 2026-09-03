<?php

namespace Drupal\geolocation_geometry\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Download helper.
 */
class GeometryFormatFileDownloadController extends ControllerBase {

  /**
   * Download handler.
   *
   * @param string $format
   *   Format.
   * @param string $entity_type
   *   Entity type ID.
   * @param int $entity_id
   *   Entity ID.
   * @param string $field_name
   *   Field name.
   * @param int|null $delta
   *   Delta.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Response.
   */
  public function download(string $format, string $entity_type, int $entity_id, string $field_name, ?int $delta = NULL): Response {

    /** @var \Drupal\Core\Entity\ContentEntityInterface|null $entity */
    $entity = $this->entityTypeManager()->getStorage($entity_type)->load($entity_id);

    if (!$entity) {
      return new Response("Not found.", 404);
    }

    // Perform the access check.
    $access = \Drupal::entityTypeManager()
      ->getAccessControlHandler($entity_type)
      ->access($entity, 'view');

    // Return the appropriate access result.
    if (!$access) {
      return new Response('Not allowed.', 500);
    }

    if (!$entity->hasField($field_name)) {
      return new Response('Field does not exist.', 500);
    }

    $field = $entity->get($field_name);

    if (!is_int($delta)) {
      return new Response('Joining geometries not supported yet.', 500);
    }

    switch ($format) {
      case 'geojson':
        return new JsonResponse($field->get($delta)->getValue()['geojson']);

      case 'gpx':
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        $response->setContent("");
        return $response;

      default:
        return new Response('Unknown format requested', 500);
    }
  }

}
