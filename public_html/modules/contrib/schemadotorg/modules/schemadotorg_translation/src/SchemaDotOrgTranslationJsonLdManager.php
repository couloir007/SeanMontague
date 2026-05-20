<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_translation;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\schemadotorg\SchemaDotOrgMappingInterface;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;
use Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdManagerInterface;

/**
 * The Schema.org translation JSON-LD manager.
 */
class SchemaDotOrgTranslationJsonLdManager implements SchemaDotOrgTranslationJsonLdManagerInterface {

  /**
   * Constructs a SchemaDotOrgTranslationJsonLdManager object.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager
   *   The Schema.org schema type manager.
   * @param \Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdManagerInterface|null $schemaJsonLdManager
   *   The Schema.org JSON-LD manager.
   */
  public function __construct(
    protected LanguageManagerInterface $languageManager,
    protected SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager,
    protected SchemaDotOrgJsonLdManagerInterface|null $schemaJsonLdManager = NULL,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function schemaTypeEntityAlter(array &$data, EntityInterface $entity, ?SchemaDotOrgMappingInterface $mapping, ?BubbleableMetadata $bubbleable_metadata): void {

    // Make sure the entity has a mapping.
    if (!$mapping) {
      return;
    }

    // Make sure we are dealing with a content entity with translations.
    if (!$entity instanceof ContentEntityInterface
      || empty($entity->getTranslationLanguages(FALSE))
      || !$entity->hasLinkTemplate('canonical')) {
      return;
    }

    // Make the entity's Schema can include an @url.
    if (!$this->schemaJsonLdManager->hasSchemaUrl($mapping)) {
      return;
    }

    // Check that Schema.org mapping type is a CreativeWork which
    // supports translations.
    // @see https://schema.org/workTranslation
    // @see https://schema.org/translationOfWork
    $schema_type = $mapping->getSchemaType();
    if (!$this->schemaTypeManager->isSubTypeOf($schema_type, 'CreativeWork')) {
      return;
    }

    // Get current language translation for the entity.
    // phpcs:ignore @phpstan-ignore-next-line
    $current_langcode = $this->languageManager->getCurrentLanguage()->getId();

    // Make sure the entity has a translation.
    if (!$entity->hasTranslation($current_langcode)) {
      return;
    }

    $entity = $entity->getTranslation($current_langcode);
    if ($entity->isDefaultTranslation()) {
      // Default translation list all translations
      // using https://schema.org/workTranslation.
      $data['workTranslation'] = [];
      $translation_languages = $entity->getTranslationLanguages(FALSE);
      foreach ($translation_languages as $translation_language) {
        $translation = $entity->getTranslation($translation_language->getId());
        $data['workTranslation'][] = [
          '@type' => $data['@type'],
          '@id' => $translation->toUrl()->setAbsolute()->toString(),
        ];
      }
    }
    else {
      // Translation reference default
      // using https://schema.org/translationOfWork.
      // Get the default language.
      // Currently, Drupal does not provide an easy way to get this information.
      // @see \Drupal\Core\Entity\ContentEntityBase::$defaultLangcode
      $default_languages = array_diff_key(
        $entity->getTranslationLanguages(),
        $entity->getTranslationLanguages(FALSE)
      );
      $default_language = reset($default_languages);
      $default_translation = $entity->getTranslation($default_language->getId());
      $data['translationOfWork'] = ['@id' => $default_translation->toUrl()->setAbsolute()->toString()];
    }
  }

}
