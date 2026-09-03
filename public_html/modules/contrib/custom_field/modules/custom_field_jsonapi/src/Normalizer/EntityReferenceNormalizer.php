<?php

namespace Drupal\custom_field_jsonapi\Normalizer;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\TypedData\TypedDataInternalPropertiesHelper;
use Drupal\custom_field\Plugin\DataType\CustomFieldEntityReference;
use Drupal\custom_field\Plugin\DataType\CustomFieldImage;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Drupal\serialization\Normalizer\ComplexDataNormalizer;

/**
 * Converts the entity_reference custom field value to a JSON:API structure.
 */
class EntityReferenceNormalizer extends ComplexDataNormalizer {

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Constructs the EntityReferenceNormalizer object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   */
  public function __construct(EntityRepositoryInterface $entity_repository) {
    $this->entityRepository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []): array {
    $entity = $object->getEntity();
    if ($entity instanceof EntityInterface) {
      // Set the entity in the correct language.
      if ($entity instanceof TranslatableInterface) {
        $entity = $this->entityRepository->getTranslationFromContext($entity);
      }
      $this->addCacheableDependency($context, $entity);

      $attributes = [];
      $typed = $entity->getTypedData();
      $properties = TypedDataInternalPropertiesHelper::getNonInternalProperties($typed);
      // Get the key identifiers.
      $id = $entity->getEntityType()->getKey('id');
      $vid = $entity->getEntityType()->getKey('revision');
      if (method_exists($this->serializer, 'normalize')) {
        foreach ($properties as $name => $property) {
          $attribute = $this->serializer->normalize($property, $format, $context);
          if (is_array($attribute)) {
            if (!empty($attribute) && count($attribute) == 1) {
              // Flatten out single items.
              $attribute = reset($attribute);
              // Flatten out the value.
              if (is_array($attribute) && count($attribute) == 1) {
                $attribute = $attribute['value'] ?? $attribute;
              }
            }
          }
          // Replace property names to be consistent with JSON:API output.
          switch ($name) {
            case $id:
              $name = 'drupal_internal__' . $id;
              break;

            case $vid:
              $name = 'drupal_internal__' . $vid;
              break;

            case 'created':
            case 'changed':
            case 'revision_timestamp':
              $attribute = $attribute['value'] ?? $attribute;
              break;
          }
          $attributes[$name] = $attribute;
        }
        // Standardize the uuid property.
        $attributes = ['id' => $attributes['uuid']] + $attributes;
        unset($attributes['uuid'], $attributes['uid']);

        // Add image metadata.
        if ($object instanceof CustomFieldImage) {
          $attributes['meta'] = [
            'alt' => $object->getAlt(),
            'title' => $object->getTitle(),
            'width' => (int) $object->getWidth(),
            'height' => (int) $object->getHeight(),
          ];
        }

        // Expose image style derivative URLs when available.
        $this->addImageStyleUris($entity, $attributes, $context);
      }

      return $attributes;
    }

    // The parent ComplexDataNormalizer::normalize() return type is `: array`
    // as of Drupal core 11.4, so an empty reference returns an empty array
    // rather than NULL.
    return [];
  }

  /**
   * Adds image style derivative URLs for a referenced image media entity.
   *
   * When the jsonapi_image_styles module is enabled it adds a computed
   * "image_style_uri" base field to file entities, exposing image style
   * derivative URLs. The underlying file of an image media reference is
   * flattened inline here rather than serialized as its own resource, so those
   * URLs would otherwise be lost. This copies them onto the media source field
   * so consumers receive the same data a regular media reference provides.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The referenced entity.
   * @param array $attributes
   *   The normalized attributes, modified by reference.
   * @param array $context
   *   The normalization context.
   */
  protected function addImageStyleUris(EntityInterface $entity, array &$attributes, array $context): void {
    if (!$entity instanceof MediaInterface) {
      return;
    }
    $source_field = $entity->getSource()->getConfiguration()['source_field'] ?? NULL;
    if ($source_field === NULL || !isset($attributes[$source_field]) || !is_array($attributes[$source_field])) {
      return;
    }
    $file = $entity->hasField($source_field) ? $entity->get($source_field)->entity : NULL;
    if (!$file instanceof FileInterface || !$file->hasField('image_style_uri')) {
      return;
    }
    $uris = $file->get('image_style_uri')->first()?->getValue();
    if (!empty($uris)) {
      $attributes[$source_field]['image_style_uri'] = $uris;
      $this->addCacheableDependency($context, $file);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTypes(?string $format): array {
    return [
      CustomFieldEntityReference::class => TRUE,
      CustomFieldImage::class => TRUE,
    ];
  }

}
