<?php

declare(strict_types=1);

namespace Drupal\entity_embed_test\Hook;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Theme\ThemeManagerInterface;

/**
 * Hook implementations for entity_embed_test.
 */
class EntityEmbedTestHooks {

  public function __construct(
    protected StateInterface $state,
    protected ThemeManagerInterface $themeManager,
  ) {}

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme($existing, $type, $theme, $path): array {
    $items['entity_embed_twig_test'] = [
      'template' => 'entity_embed_twig_test',
      'variables' => [
        'entity_type' => '',
        'id' => '',
        'display_plugin' => 'default',
        'display_settings' => [],
      ],
    ];
    return $items;
  }

  /**
   * Implements hook_entity_embed_display_plugins_alter().
   */
  #[Hook('entity_embed_display_plugins_alter')]
  public function entityEmbedDisplayPluginsAlter(&$info): void {
    // Allow tests to enable or disable this hook.
    if (!$this->state->get('entity_embed_test_entity_embed_display_plugins_alter', FALSE)) {
      return;
    }

    // Prefix each plugin name with 'testing_hook:'.
    $new_info = [];
    foreach ($info as $key => $value) {
      $new_key = "testing_hook:" . $key;
      $new_info[$new_key] = $info[$key];
      unset($info[$key]);
    }
    $info = $new_info;
  }

  /**
   * Implements hook_entity_embed_context_alter().
   */
  #[Hook('entity_embed_context_alter')]
  public function entityEmbedContextAlter(array &$context, EntityInterface $entity): void {
    // Allow tests to enable or disable this hook.
    if (!$this->state->get('entity_embed_test_entity_embed_context_alter', FALSE)) {
      return;
    }

    // Force to use 'Label' plugin.
    $context['data-entity-embed-display'] = 'entity_reference:entity_reference_label';
    $context['data-entity-embed-display-settings'] = ['link' => 1];

    // Set title of the entity.
    $entity->setTitle("Title set by hook_entity_embed_context_alter");
  }

  /**
   * Implements hook_entity_embed_alter().
   */
  #[Hook('entity_embed_alter')]
  public function entityEmbedAlter(array &$build, EntityInterface $entity, array $context): void {
    // Allow tests to enable or disable this hook.
    if (!$this->state->get('entity_embed_test_entity_embed_alter', FALSE)) {
      return;
    }

    // Adding classes is as simple as appending to an array.
    $build['#attributes']['class'][] = 'test-class-added-in-alter-hook';

    // Set title of the 'node' entity.
    $entity->setTitle("Title set by hook_entity_embed_alter");
  }

  /**
   * Implements hook_entity_access().
   */
  #[Hook('entity_access')]
  public function entityAccess(EntityInterface $entity, $operation, AccountInterface $account): ?AccessResultInterface {
    if ($entity->label() == 'Embed Test Node') {
      return AccessResult::neutral()->addCacheTags(['foo:' . $entity->id()]);
    }
    return NULL;
  }

  /**
   * Implements hook_entity_view_alter().
   */
  #[Hook('entity_view_alter')]
  public function entityViewAlter(&$build, EntityInterface $entity, EntityViewDisplayInterface $display): void {
    $build['#attributes']['data-entity-embed-test-uuid'] = $entity->uuid();
    $build['#attributes']['data-entity-embed-test-view-mode'] = $build['#view_mode'];
    // @see \Drupal\Tests\entity_embed\FunctionalJavascript\MediaImageTest::testPreviewUsesDefaultTheme()
    $build['#attributes']['data-entity-embed-test-active-theme'] = $this->themeManager->getActiveTheme()->getName();
  }

}
