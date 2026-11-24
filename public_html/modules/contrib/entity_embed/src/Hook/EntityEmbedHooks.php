<?php

declare(strict_types=1);

namespace Drupal\entity_embed\Hook;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for entity_embed.
 */
class EntityEmbedHooks {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ModuleHandlerInterface $moduleHandler,
    protected ModuleExtensionList $moduleExtensionList,
  ) {}

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match): ?string {
    switch ($route_name) {
      case 'help.page.entity_embed':
        $output = '';
        $output .= '<h3>' . $this->t('About') . '</h3>';
        $output .= '<p>' . $this->t('The Entity Embed module allows entities to be embedded in formatted text.') . '</p>';
        $output .= '<h3>' . $this->t('Uses') . '</h3>';
        $output .= '<dl>';
        $output .= '<dt>' . $this->t('Embedding media') . '</dt>';
        $output .= '<dd>' . $this->t('This module, and the text filter that it provides along with the CKEditor integration, is especially suited to allow content authors to embed media in their textual content: images, video, and so on.') . '</dd>';
        $output .= '<dt>' . $this->t('Embedding arbitrary content') . '</dt>';
        $output .= '<dd>' . $this->t('As mentioned above, this module is especially helpful for embedding media in textual content, but it is not necessarily restricted to that; it allows <em>any</em> entity to be embedded. On an e-commerce site, you may want to embed products, on a company blog you may want to embed past projects, and so on.') . '</dd>';
        $output .= '</dl>';
        return $output;
    }
    return NULL;
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(): array {
    return [
      'entity_embed_container' => [
        'render element' => 'element',
        'initial preprocess' => static::class . ':preprocessEntityEmbedContainer',
      ],
    ];
  }

  /**
   * Prepares variables for entity embed container templates.
   *
   * Default template: entity-embed-container.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - element: An associative array containing the properties of the element.
   *     Properties used: #attributes, #children.
   */
  public function preprocessEntityEmbedContainer(array &$variables): void {
    $variables['element'] += ['#attributes' => []];
    $variables['attributes'] = $variables['element']['#attributes'];
    $variables['children'] = $variables['element']['#children'];

    // Ensure the display settings get converted to a JSON string again.
    if (isset($variables['attributes']['data-entity-embed-display-settings']) && is_array($variables['attributes']['data-entity-embed-display-settings'])) {
      $variables['attributes']['data-entity-embed-display-settings'] = Json::encode($variables['attributes']['data-entity-embed-display-settings']);
    }
  }

  /**
   * Implements hook_entity_embed_display_plugins_alter() for file.
   */
  #[Hook('entity_embed_display_plugins_alter', module: 'file')]
  public function fileEntityEmbedDisplayPluginsAlter(array &$plugins): void {
    // The RSS enclosure field formatter is not usable for Entity Embed.
    unset($plugins['file:file_rss_enclosure']);
  }

  /**
   * Implements hook_entity_embed_display_plugins_alter() for taxonomy.
   */
  #[Hook('entity_embed_display_plugins_alter', module: 'taxonomy')]
  public function taxonomyEntityEmbedDisplayPluginsAlter(array &$plugins): void {
    // The RSS category field formatter is not usable for Entity Embed.
    unset($plugins['entity_reference:entity_reference_rss_category']);
  }

  /**
   * Implements hook_entity_embed_display_plugins_for_context_alter().
   *
   * The 'Rendered entity' formatter can not be used for files unless the
   * file_entity module is available.
   *
   * @see https://www.drupal.org/node/2468387
   *
   * @todo Remove when https://www.drupal.org/node/2567919 is fixed in core.
   */
  #[Hook('entity_embed_display_plugins_for_context_alter')]
  public function entityEmbedDisplayPluginsForContextAlter(array &$definitions, array $context): void {
    if ($context['entity_type'] === 'file' && !$this->moduleHandler->moduleExists('file_entity')) {
      unset($definitions['entity_reference:entity_reference_entity_view']);
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter() for the filter format forms.
   */
  #[Hook('form_filter_format_edit_form_alter')]
  #[Hook('form_filter_format_add_form_alter')]
  public function formFilterFormatFormAlter(array &$form, FormStateInterface $form_state, $form_id): void {
    // Add an additional validate callback so we can ensure the order of
    // filters is correct.
    $form['#validate'][] = [static::class, 'validateFilterFormatForm'];
  }

  /**
   * Validate callback to ensure filter order and allowed_html are compatible.
   */
  public static function validateFilterFormatForm(array $form, FormStateInterface $form_state): void {
    // This validate handler doesn't apply when using the 'Configure' button.
    if ($form_state->getTriggeringElement()['#name'] === 'editor_configure') {
      return;
    }

    $allowed_html_path = [
      'filters',
      'filter_html',
      'settings',
      'allowed_html',
    ];

    $button_group_path = [
      'editor',
      'settings',
      'toolbar',
      'button_groups',
    ];

    $filter_html_settings_path = [
      'filters',
      'filter_html',
      'settings',
    ];

    $filter_html_enabled = $form_state->getValue([
      'filters',
      'filter_html',
      'status',
    ]);

    $entity_embed_enabled = $form_state->getValue([
      'filters',
      'entity_embed',
      'status',
    ]);

    if ($entity_embed_enabled && $filter_html_enabled && $form_state->getValue($allowed_html_path)) {
      if ($button_groups = $form_state->getValue($button_group_path)) {
        $buttons = [];
        $button_groups = json_decode($button_groups, TRUE);
        if (!empty($button_groups[0])) {
          foreach ($button_groups[0] as $button_group) {
            foreach ($button_group['items'] as $item) {
              $buttons[] = $item;
            }
          }
        }

        /** @var \Drupal\filter\Entity\FilterFormat $filter_format */
        $filter_format = $form_state->getFormObject()->getEntity();

        $filter_html = $filter_format->filters()->get('filter_html');
        $filter_html->setConfiguration(['settings' => $form_state->getValue($filter_html_settings_path)]);
        $restrictions = $filter_html->getHTMLRestrictions();
        $allowed = $restrictions['allowed'];

        $embeds = \Drupal::entityTypeManager()
          ->getStorage('embed_button')
          ->loadMultiple($buttons);

        /** @var \Drupal\embed\Entity\EmbedButton $embed */
        foreach ($embeds as $embed) {
          if ($embed->getTypeId() !== 'entity') {
            continue;
          }
          // Require `<drupal-entity>` HTML tag if filter_html is enabled.
          if (!isset($allowed['drupal-entity'])) {
            $form_state->setError($form['filters']['settings']['filter_html']['allowed_html'], t('The %embed button requires <code>&lt;drupal-entity&gt;</code> among the allowed HTML tags.', [
              '%embed' => $embed->label(),
            ]));
            break;
          }
          else {
            $required_attributes = [
              'data-entity-type',
              'data-entity-uuid',
              'data-entity-embed-display',
              'data-entity-embed-display-settings',
              'data-align',
              'data-caption',
              'data-embed-button',
              'alt',
              'title',
            ];

            // If there are no attributes, the allowed item is set to FALSE,
            // otherwise, it is set to an array.
            if ($allowed['drupal-entity'] === FALSE) {
              $missing_attributes = $required_attributes;
            }
            else {
              // If any of the attributes on the drupal-entity tag use
              // wildcards, iterate through and replace them with matching
              // required attributes.
              $wildcards = preg_grep('/\*/', array_keys($allowed['drupal-entity']));
              if (!empty($wildcards)) {
                foreach ($wildcards as $wildcard) {
                  $pattern = str_replace('*', '(.*)', $wildcard);
                  $matches = preg_grep('/' . $pattern . '/', $required_attributes);
                  foreach ($matches as $match) {
                    $allowed['drupal-entity'][$match] = 1;
                  }
                  unset($allowed['drupal-entity'][$wildcard]);
                }
              }
              $missing_attributes = array_diff($required_attributes, array_keys($allowed['drupal-entity']));
            }

            if ($missing_attributes) {
              $form_state->setError($form['filters']['settings']['filter_html']['allowed_html'], t('The <code>&lt;drupal-entity&gt;</code> tag in the allowed HTML tags is missing the following attributes: <code>%list</code>.', [
                '%list' => implode(', ', $missing_attributes),
              ]));
              break;
            }
          }
        }
      }
    }

    $filters = $form_state->getValue('filters');

    $get_filter_label = function ($filter_plugin_id) use ($form) {
      return (string) $form['filters']['order'][$filter_plugin_id]['filter']['#markup'];
    };

    // The "entity_embed" filter must run after "filter_align",
    // "filter_caption", and "filter_html_image_secure".
    if ($entity_embed_enabled) {
      $precedents = [
        'filter_align',
        'filter_caption',
        'filter_html_image_secure',
      ];

      $error_filters = [];
      foreach ($precedents as $filter_name) {

        // A filter that should run before entity embed filter.
        $precedent = $filters[$filter_name];

        if (empty($precedent['status']) || !isset($precedent['weight'])) {
          continue;
        }

        if ($precedent['weight'] >= $filters['entity_embed']['weight']) {
          $error_filters[$filter_name] = $get_filter_label($filter_name);
        }
      }

      if (!empty($error_filters)) {
        $singular = 'The %entity-embed-filter-label filter needs to be placed after the %filter filter.';
        $plural = 'The %entity-embed-filter-label filter needs to be placed after the following filters: %filters.';
        $error_message = \Drupal::translation()->formatPlural(count($error_filters), $singular, $plural, [
          '%entity-embed-filter-label' => $get_filter_label('entity_embed'),
          '%filter' => reset($error_filters),
          '%filters' => implode(', ', $error_filters),
        ]);

        $form_state->setErrorByName('filters][entity_embed][status', $error_message);
      }
    }
  }

  /**
   * Implements hook_field_widget_single_element_form_alter().
   */
  #[Hook('field_widget_single_element_form_alter')]
  public function fieldWidgetSingleElementFormAlter(&$element, FormStateInterface $form_state, $context): void {
    // Add a `data-entity_embed-host-entity-langcode` attribute so that
    // entity_embed's JavaScript can pass the host entity's language to
    // EntityEmbedDialog, allowing it to present entities in the same language.
    if (isset($element['#type']) && $element['#type'] === 'text_format') {
      /** @var \Drupal\Core\Field\FieldItemListInterface $items */
      $items = $context['items'];
      $element['#attributes']['data-entity_embed-host-entity-langcode'] = $items->getEntity()->language()->getId();
    }
  }

  /**
   * Implements hook_library_info_alter().
   */
  #[Hook('library_info_alter')]
  public function libraryInfoAlter(&$libraries, $extension): void {
    // Create the drupalSettings dynamically based on embed buttons for the
    // admin library.
    if ($extension === 'entity_embed' && isset($libraries['admin.entity_embed'])) {
      $entity_embed_buttons = $this->entityTypeManager
        ->getStorage('embed_button')
        // @see \Drupal\entity_embed\Plugin\EmbedType\Entity
        ->loadByProperties(['type_id' => 'entity']);
      foreach ($entity_embed_buttons as $entity_embed_button) {
        $libraries['admin.entity_embed']['drupalSettings']['embedButtons'][$entity_embed_button->id()] = [
          'id' => $entity_embed_button->id(),
          'icon' => $entity_embed_button->getIconUrl(),
        ];
      }
      $libraries['admin.entity_embed']['drupalSettings']['modulePath'] = $this->moduleExtensionList->getPath('entity_embed');
    }
  }

}
