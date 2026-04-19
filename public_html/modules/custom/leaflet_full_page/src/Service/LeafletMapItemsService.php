<?php

namespace Drupal\leaflet_full_page\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\views\Views;
use Drupal\Core\Render\RendererInterface;
use Drupal\leaflet_full_page\Service\LeafletEdanObjectService;

/**
 * Service for handling leaflet full page map items data.
 */
class LeafletMapItemsService {

    /**
     * The entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * The renderer service.
     *
     * @var \Drupal\Core\Render\RendererInterface
     */
    protected RendererInterface $renderer;

    /**
     * The EDAN object service.
     *
     * @var \Drupal\leaflet_full_page\Service\LeafletEdanObjectService
     */
    protected \Drupal\leaflet_full_page\Service\LeafletEdanObjectService $edanObjectService;

    /**
     * Constructs a LeafletMapItemsService object.
     *
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
     *   The entity type manager.
     * @param \Drupal\Core\Render\RendererInterface $renderer
     *   The renderer service.
     * @param \Drupal\leaflet_full_page\Service\LeafletEdanObjectService $edan_object_service
     *   The EDAN object service.
     */
    public function __construct(EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer, LeafletEdanObjectService $edan_object_service) {
        $this->entityTypeManager = $entity_type_manager;
        $this->renderer = $renderer;
        $this->edanObjectService = $edan_object_service;
    }

    /**
     * Gets map items data for a specific display.
     *
     * @param string $current_display
     *   The current display being processed.
     *
     * @return array
     *   An array of map items data.
     */
    public function getMapItemsData($current_display) {

        // Load the view and execute the display
        $view = Views::getView('leaflet_full_page');
        if (!$view) {
            return [];
        }

        $view->setDisplay('semiquincentennial_export');
        $view->execute();

        // Get the raw results instead of rendering
        $results = $view->result;
        $map_items = [];

        foreach ($results as $row) {
            if (isset($row->_entity)) {
                $entity = $row->_entity;

                // Build item data using the view's field handlers
                $item_data = [];

                // Add basic entity information
                $item_data['contentID'] = $entity->id();
                $item_data['locationID'] = $entity->id();
                foreach ($view->field as $field_name => $field_handler) {
                    $value = $field_handler->getValue($row);
                    switch ($field_name) {
                        case 'label':
                            $item_data['title'] = $value; // For template compatibility
                            break;
                        case 'field_state_fips':
                            $item_data['stateID'] = $value; // For template compatibility
                            break;
                        case 'field_listing_image_media':
                            $item_data[$field_name] = $this->renderMediaField($entity, 'field_listing_image_media', '3_2_large');
                            break;
                        case 'field_listing_image_media_1':
                            $item_data[$field_name] = $this->renderMediaField($entity, 'field_listing_image_media', 'square');
                            break;
                        case 'field_edan_object':
                            $item_data[$field_name] = $this->processEdanObject($value);
                            break;
//                        case 'field_object_content':
////                            $item_data[$field_name] = $this->renderMediaField($entity, 'field_listing_image_media', 'square');
//                            break;
                        default:
                            // Default handling for all other fields
                            $item_data[$field_name] = $value;
                            break;
                    }
                }

                $map_items[] = $item_data;
            }
        }

        return $map_items;
    }

    /**
     * Processes EDAN object field value.
     *
     * @param string $edan_value
     *   The EDAN object field value.
     *
     * @return array
     *   Processed EDAN object data.
     */
    protected function processEdanObject($edan_value) {
        if (empty($edan_value)) {
            return [];
        }

        // Extract string value from Markup object if it's a Markup object
        if ($edan_value instanceof \Drupal\Core\Render\Markup) {
            $edan_value_string = (string) $edan_value;
        } else {
            $edan_value_string = $edan_value;
        }

        try {
            $object = $this->edanObjectService->getObjectByUrl($edan_value_string);

            if (!isset($object['data']['content']['descriptiveNonRepeating']['online_media']['media'])) {
                return [];
            }

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
        } catch (\Exception $e) {
            // Log the error and return empty array
            \Drupal::logger('leaflet_full_page')->error('Error processing EDAN object: @message', ['@message' => $e->getMessage()]);
            return [];
        }
    }

    protected function renderMediaField($entity, $field_name, $view_mode = 'default') {
        if (!$entity->hasField($field_name) || $entity->get($field_name)->isEmpty()) {
            return '';
        }

        $media_field = $entity->get($field_name);
        $media_entity = $media_field->entity;

        if (!$media_entity) {
            return '';
        }

        // Render the media entity using the specified view mode
        $view_builder = $this->entityTypeManager->getViewBuilder('media');
        $build = $view_builder->view($media_entity, $view_mode);

        return $this->renderer->render($build);
    }

    /**
     * Gets processed map items with custom field rendering.
     *
     * @param string $current_display
     *   The current display being processed.
     * @param array $field_names
     *   Array of field names to render.
     * @param string $view_mode
     *   The view mode to use for field rendering.
     *
     * @return array
     *   An array of processed map items.
     */
    public function getProcessedMapItems($current_display, array $field_names = [], $view_mode = 'default') {
        $mapItemsDisplay = $current_display;

        $view = Views::getView('leaflet_full_page');
        if (!$view) {
            return [];
        }

        $view->setDisplay($mapItemsDisplay);
        $view->execute();

        $map_items = [];

        foreach ($view->result as $row) {
            if (isset($row->_entity)) {
                $entity = $row->_entity;

                $item = [
                    'id' => $entity->id(),
                    'title' => $entity->label(),
                    'type' => $entity->getEntityTypeId(),
                ];

                // Render specific fields if requested
                foreach ($field_names as $field_name) {
                    $item['fields'][$field_name] = $this->renderEntityField($entity, $field_name, $view_mode);
                }

                $map_items[] = $item;
            }
        }

        return $map_items;
    }

    /**
     * Renders a field from an entity.
     *
     * @param \Drupal\Core\Entity\EntityInterface $entity
     *   The entity containing the field.
     * @param string $field_name
     *   The field name to render.
     * @param string $view_mode
     *   The view mode to use for rendering.
     *
     * @return string
     *   The rendered field output.
     */
    protected function renderEntityField($entity, $field_name, $view_mode = 'default') {
        if (!$entity->hasField($field_name) || $entity->get($field_name)->isEmpty()) {
            return '';
        }

        $field = $entity->get($field_name);
        $build = $field->view($view_mode);
        return $this->renderer->render($build);
    }

}