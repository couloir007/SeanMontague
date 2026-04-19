<?php

namespace Drupal\leaflet_full_page\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;
use Drupal\Core\Render\RenderContext;

/**
 * Controller for dumping semiquincentennial map items.
 */
class MapItemsController extends ControllerBase {

    /**
     * Dumps all semiquincentennial geo entities as JSON.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   The JSON response.
     */
    public function dump() {
        $storage = $this->entityTypeManager()->getStorage('geo_entity');
        $label_key = $storage->getEntityType()->getKey('label'); // e.g. 'title' or 'name'

        $query = $storage->getQuery()
            ->condition('bundle', 'semiquincentennial')
            ->accessCheck(FALSE)
            ->sort($label_key, 'ASC');
        $ids = $query->execute();

        $entities = $storage->loadMultiple($ids);
        $data = [];

        $renderer = \Drupal::service('renderer');
        $render_context = new RenderContext();

        foreach ($entities as $entity) {
            $item = [
                'contentID' => $entity->id(),
                'locationID' => $entity->id(),
                'stateID' => $entity->get('field_state_fips')->value, // Added for JS compatibility
                'title' => $entity->label(),
            ];

            // Handle field_sub_title
            if ($entity->hasField('field_sub_title') && !$entity->get('field_sub_title')->isEmpty()) {
                $raw_sub_title = (string) $entity->get('field_sub_title')->value;

                // Allow only a minimal, safe subset of HTML tags.
                // Adjust allowed tags as needed (keep it tight).
                $allowed_tags = ['em', 'strong', 'b', 'i', 'br', 'span', 'sup', 'sub'];

                $item['field_sub_title'] = Xss::filter($raw_sub_title, $allowed_tags);
            }

            // Handle media fields (listing image)
            if ($entity->hasField('field_listing_image_media') && !$entity->get('field_listing_image_media')->isEmpty()) {
                $item['field_listing_image_media'] = $this->renderReferencedEntities($entity, 'field_listing_image_media', '3_2_large');
                $item['field_listing_image_media_1'] = $this->renderReferencedEntities($entity, 'field_listing_image_media', 'square');
            }

            // Handle field_body with media embeds
            if ($entity->hasField('field_body') && !$entity->get('field_body')->isEmpty()) {
                $body_item = $entity->get('field_body')->first();
                $build = [
                    '#type' => 'processed_text',
                    '#text' => $body_item->value ?? '',
                    '#format' => $body_item->format ?? 'full_html',
                ];

                $item['field_body'] = (string) $renderer->executeInRenderContext($render_context, function () use (&$build, $renderer) {
                    return $renderer->renderRoot($build);
                });

            } else {
                $item['field_body'] = '';
            }

            // Handle field_edan_object (this needs EDAN connector service)
            if ($entity->hasField('field_edan_object') && !$entity->get('field_edan_object')->isEmpty()) {
                $edan_url = $entity->get('field_edan_object')->value;
                $item['field_edan_object'] = $this->processEdanObject($edan_url);
            }

            // Handle field_object_content (entity reference) - render REFERENCED entity, not field wrapper.
            if ($entity->hasField('field_object_content') && !$entity->get('field_object_content')->isEmpty()) {
                $item['field_object_content'] = $this->renderReferencedEntities($entity, 'field_object_content', 'default');
            } else {
                $item['field_object_content'] = '';
            }

            $data[] = $item;
        }

        $map_pdf_url = '/sites/default/files/media/file/260128-find-your-place-museum-map.pdf';
        $media = Media::load(9625);
        if ($media) {
            $source_field = $media->getSource()->getConfiguration()['source_field'] ?? NULL;
            if ($source_field && $media->hasField($source_field) && !$media->get($source_field)->isEmpty()) {
                $fid = $media->get($source_field)->target_id;
                $file = File::load($fid);
                if ($file) {
                    $map_pdf_url = \Drupal::service('file_url_generator')->generateString($file->getFileUri());
                }
            }
        }

        $data[] = ['contentID' => 'findMap', 'pdf' => '<a class="icon-link " href="' . $map_pdf_url . '" title="Find Your Place Museum Map and Object List (PDF)" aria-label="Find Your Place Museum Map and Object List (PDF)">
        <span class="icon-circle icon-circle--download-sm"></span>Find Your Place Museum Map and Object List (PDF)
        </a>'];

        // Also persist the JSON to public files.
        $file_path = 'public://find_your_place/interactive-us-map_mapitems.json';
        $directory = dirname($file_path);

        try {
            /** @var \Drupal\Core\File\FileSystemInterface $file_system */
            $file_system = \Drupal::service('file_system');

            // Ensure directory exists.
            $file_system->prepareDirectory(
                $directory,
                FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS
            );

            // Encode and save.
            $json = Json::encode($data);
            $file_system->saveData($json, $file_path, FileSystemInterface::EXISTS_REPLACE);

            \Drupal::logger('leaflet_full_page')->info('Saved map items JSON to @path', [
                '@path' => $file_path,
            ]);
        }
        catch (\Throwable $e) {
            \Drupal::logger('leaflet_full_page')->error('Failed to save map items JSON: @message', [
                '@message' => $e->getMessage(),
            ]);
        }

        return new JsonResponse($data);
    }

    /**
     * Renders entities referenced by an entity reference field using the referenced
     * entity view builder (entity templates), not the parent field formatter.
     *
     * @param string|null $only_entity_type_id
     *   If provided, only renders references of this entity type (e.g. 'media').
     */
    protected function renderReferencedEntities($entity, $field_name, $view_mode, $only_entity_type_id = null): string {
        $field = $entity->get($field_name);

        $grouped = [];
        foreach ($field->referencedEntities() as $ref) {
            if (!$ref) {
                continue;
            }
            $type_id = $ref->getEntityTypeId();
            if ($only_entity_type_id !== null && $type_id !== $only_entity_type_id) {
                continue;
            }
            $grouped[$type_id][] = $ref;
        }

        if (!$grouped) {
            return '';
        }

        $renderer = \Drupal::service('renderer');
        $output = '';

        foreach ($grouped as $type_id => $refs) {
            $view_builder = $this->entityTypeManager()->getViewBuilder($type_id);
            $build = $view_builder->viewMultiple($refs, $view_mode);
            $output .= (string) $renderer->renderPlain($build);
        }

        return $output;
    }

    /**
     * Renders a media field with a specific view mode.
     */
    protected function renderMediaField($entity, $field_name, $view_mode) {
        $field = $entity->get($field_name);
        $build = $field->view(['view_mode' => $view_mode]);
        return (string) \Drupal::service('renderer')->renderPlain($build);
    }

    /**
     * Renders an entity reference field.
     */
    protected function renderEntityField($entity, $field_name, $view_mode) {
        $field = $entity->get($field_name);
        $build = $field->view(['view_mode' => $view_mode]);
        return (string) \Drupal::service('renderer')->renderPlain($build);
    }

    /**
     * Processes EDAN object field value.
     */
    protected function processEdanObject($edan_url) {
        if (empty($edan_url)) {
            return [];
        }

        $edanConnector = \Drupal::service('edan_connector.simple');
        try {
            $object = $edanConnector->getObjectByUrl($edan_url);

            if (isset($object['data']['content']['descriptiveNonRepeating']['online_media']['media'])) {
                $media = $object['data']['content']['descriptiveNonRepeating']['online_media']['media'];
                $imagesToSerialize = [];

                foreach ($media as $image) {
                    if (str_contains($image['content'], 'https://collections.nmnh.si.edu/media/?irn=')) {
                        $imagesToSerialize[] = [
                            'content' => 'https://ids.si.edu/ids/deliveryService?id=' . $image['content'],
                            'thumb' => 'https://ids.si.edu/ids/deliveryService?id=' . $image['content'] . '&thumb=yes&max_w=100',
                            'idsId' => $image['idsId'],
                        ];
                    } else {
                        $imagesToSerialize[] = [
                            'content' => $image['content'],
                            'thumb' => $image['content'] . '/100',
                            'idsId' => $image['idsId'],
                        ];
                    }
                }
                return $imagesToSerialize;
            }
        } catch (\Exception $e) {
            \Drupal::logger('leaflet_full_page')->error('Error processing EDAN object in dump controller: @message', ['@message' => $e->getMessage()]);
        }

        return [];
    }
}
