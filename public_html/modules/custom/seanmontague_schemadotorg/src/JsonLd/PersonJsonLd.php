<?php

namespace Drupal\seanmontague_schemadotorg\JsonLd;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Builds Person additions for person nodes.
 *
 * When a person node is rendered as JSON-LD (e.g. as the homepage WebPage's
 * mainEntity, referenced via schema_main_entity), Blueprints emits a minimal
 * reference stub (type + name + url). This enriches it with the mapped
 * properties that carry the actual content:
 *   - knowsAbout  ← schema_knows_about (the skills list)
 *   - description ← body (the bio paragraphs), plain-text
 *
 * Mirrors the ArticleJsonLd / TouristTripJsonLd static-alter pattern.
 */
class PersonJsonLd {

  public static function alter(array &$data, EntityInterface $entity, BubbleableMetadata $bubbleable_metadata): void {
    if (!$entity instanceof ContentEntityInterface) {
      return;
    }

    $knows_about = static::buildKnowsAbout($entity);
    if ($knows_about) {
      $data['knowsAbout'] = $knows_about;
    }

    // Only set description if Blueprints hasn't already (don't clobber).
    if (empty($data['description'])) {
      $description = static::buildDescription($entity);
      if ($description !== NULL) {
        $data['description'] = $description;
      }
    }
  }

  /**
   * Builds the knowsAbout array from the multi-value schema_knows_about field.
   *
   * @return string[]
   *   List of skill/subject strings; empty if the field is absent or empty.
   */
  protected static function buildKnowsAbout(ContentEntityInterface $entity): array {
    if (!$entity->hasField('schema_knows_about') || $entity->get('schema_knows_about')->isEmpty()) {
      return [];
    }
    $values = [];
    foreach ($entity->get('schema_knows_about') as $item) {
      $value = trim((string) $item->value);
      if ($value !== '') {
        $values[] = $value;
      }
    }
    return $values;
  }

  /**
   * Builds a plain-text description from the body field (bio paragraphs).
   *
   * @return string|null
   *   Plain-text bio, or NULL if body is absent/empty.
   */
  protected static function buildDescription(ContentEntityInterface $entity): ?string {
    if (!$entity->hasField('body') || $entity->get('body')->isEmpty()) {
      return NULL;
    }
    $raw = (string) $entity->get('body')->value;
    if ($raw === '') {
      return NULL;
    }
    // Insert a space at block-level boundaries so paragraphs don't run together
    // when tags are stripped (</p><p> would otherwise join "...well.The...").
    $raw = preg_replace('#</(p|div|h[1-6]|li|br\s*/?)>#i', ' ', $raw);
    $raw = preg_replace('#<br\s*/?>#i', ' ', $raw);
    // Strip remaining tags + normalize whitespace for the plain-text description.
    $text = trim(preg_replace('/\s+/', ' ', strip_tags($raw)));
    return $text !== '' ? $text : NULL;
  }
}
