<?php

/* phpcs:disable SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint */
/* phpcs:disable SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint */

declare(strict_types=1);

namespace Drupal\schemadotorg\Element;

use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Form\ConfigTarget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ToConfig;
use Drupal\Core\Link;
use Drupal\Core\Render\Attribute\FormElement;
use Drupal\Core\Render\Element\Textarea;
use Drupal\Core\Url;

/**
 * Provides a form element for Schema.org Blueprints settings.
 */
#[FormElement("schemadotorg_settings")]
class SchemaDotOrgSettings extends Textarea {

  /**
   * Settings modes mapped to CodeMirror modes.
   */
  protected static array $modes = [
    'yaml' => 'yaml',
    'json' => 'application/ld+json',
  ];

  /**
   * Settings modes mapped to CodeMirror libraries.
   */
  protected static array $libraries = [
    'yaml' => 'schemadotorg/codemirror.yaml',
    'json' => 'schemadotorg/codemirror.javascript',
  ];

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#process' => [
        [$class, 'processSchemaDotOrgSettings'],
        [$class, 'processAjaxForm'],
        [$class, 'processGroup'],
      ],
      '#description' => '',
      '#description_link' => '',
      '#token_link' => FALSE,
      '#token_types' => [],
      '#example' => '',
      '#attributes' => ['wrap' => 'off'],
      '#mode' => 'yaml',
      '#raw' => FALSE,
      '#config_name' => '',
      '#config_key' => '',
    ] + parent::getInfo();
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    // Elements using #config_target don't need to use the ::valueCallback.
    if (!empty($element['#config_target'])) {
      return NULL;
    }

    if ($input === FALSE) {
      $config_name = $element['#config_name'];
      $config_key = $element['#config_key'];
      $element['#default_value'] = \Drupal::config($config_name)->get($config_key)
        ?: $element['#default_value']
        ?? NULL;
    }
    else {
      $mode = $element['#mode'];
      $raw = $element['#raw'];
      if (!$raw && is_string($input)) {
        try {
          static::validate($mode, $input);
          return ($mode === 'json')
            ? static::jsonDecodeToConfig($input)
            : static::yamlDecodeToConfig($input);
        }
        catch (InvalidDataTypeException $exception) {
          // Do nothing and allow validation to catch the exception.
        }
      }
    }
  }

  /**
   * Processes a 'schemadotorg_settings' element.
   */
  public static function processSchemaDotOrgSettings(array &$element, FormStateInterface $form_state, array &$complete_form): array {
    $mode = $element['#mode'];
    $raw = $element['#raw'];

    // Handle elements that are not using #config_target via a simple
    // configuration form.
    if (empty($element['#config_target'])) {
      if (!$raw) {
        $value_properties = ['#default_value', '#value'];
        foreach ($value_properties as $value_property) {
          if (isset($element[$value_property]) && is_array($element[$value_property])) {
            $element[$value_property] = ($mode === 'json')
              ? static::jsonEncodeFromConfig($element[$value_property])
              : static::yamlEncodeFromConfig($element[$value_property]);
          }
        }
      }
      $element['#element_validate'][] = [static::class, 'validateElement'];
    }

    // Append token tree link to the description.
    if ($element['#token_link']
      && \Drupal::moduleHandler()->moduleExists('token')) {
      // Build the token tree link.
      $build = [
        '#theme' => 'token_tree_link',
        '#token_types' => $element['#token_types'],
      ];

      // If token types are empty, set the token types to support mapping types.
      if (empty($build['#token_types'])) {
        $mapping_types = \Drupal::entityTypeManager()
          ->getStorage('schemadotorg_mapping_type')
          ->loadMultiple();
        if ($mapping_types) {
          $mapping_type_ids = array_keys($mapping_types);
          $token_types = array_combine($mapping_type_ids, $mapping_type_ids);
          if (isset($token_types['taxonomy_term'])) {
            $token_types['term'] = 'term';
          }
          $build['#token_types'] = $token_types;
        }
      }

      // Render and append the token tree link to the description.
      /** @var \Drupal\Core\Render\RendererInterface $renderer */
      $renderer = \Drupal::service('renderer');
      $element['#description'] = $element['#description'] ?? '';
      $element['#description'] .= '<br/>';
      $element['#description'] .= $renderer->render($build);
    }

    // Append Schema.org browse types or properties link to the description.
    $link_table = $element['#description_link'];
    if (in_array($link_table, ['types', 'properties'])
      && \Drupal::moduleHandler()->moduleExists('schemadotorg_report')) {
      $link_text = ($link_table === 'types')
        ? t('Browse Schema.org types.')
        : t('Browse Schema.org properties.');
      $link_url = Url::fromRoute("schemadotorg_report.$link_table");
      $element['#description'] .= (!empty($element['#description'])) ? ' ' : '';
      $element['#description'] .= '<span class="schemadotorg-settings-browse">' . Link::fromTextAndUrl($link_text, $link_url)->toString() . '</span>';
      $element['#attached']['library'][] = 'schemadotorg/schemadotorg.dialog';
    }

    // Append an example to the description.
    if ($element['#example']) {
      // Make sure the example if valid.
      static::validate($mode, $element['#example']);

      $id = $element['#id'];
      $element['#description'] = [
        'content' => [
          '#markup' => $element['#description'],
        ],
        'example' => [
          '#type' => 'inline_template',
          '#template' => '<div class="schemadotorg-settings-example">
  <div class="schemadotorg-settings-example--link"><a role="button" href="#{{ id }}-example">{{ "Example"|t }}</a></div>
  <div class="schemadotorg-settings-example--content" id="{{ id }}-example">
    <pre data-schemadotorg-codemirror-mode="{{ mode }}">{{ example }}</pre>
  </div>
</div>',
          '#context' => [
            'mode' => static::$modes[$mode],
            'id' => $id,
            'example' => $element['#example'],
          ],
        ],
      ];
    }

    // Set CodeMirror class and mode attributes and attach the library.
    $element['#attributes']['class'][] = 'schemadotorg-codemirror';
    $element['#attributes']['data-mode'] = static::$modes[$mode];
    $element['#attached']['library'][] = static::$libraries[$mode];

    // Attach the library.
    $element['#attached']['library'][] = 'schemadotorg/schemadotorg.settings.element';

    return $element;
  }

  /* ************************************************************************ */
  // Config target methods.
  /* ************************************************************************ */

  /**
   * Sets the config target for the element.
   *
   * @param array $element
   *   The element for which config target is being set.
   * @param string $config_name
   *   The name of the config.
   * @param string $config_key
   *   The key of the config.
   */
  public static function setConfigTarget(array &$element, string $config_name, string $config_key): void {
    $raw = $element['#raw'] ?? FALSE;
    $mode = $element['#mode'] ?? 'yaml';

    if ($raw) {
      $element['#config_target'] = "$config_name:$config_key";
    }
    else {
      $element['#config_target'] = new ConfigTarget(
        $config_name,
        $config_key,
        [static::class, $mode . 'EncodeFromConfig'],
        [static::class, $mode . 'DecodeToConfig'],
      );
    }

    $element['#element_validate'][] = [static::class, 'validateElement'];
  }

  /**
   * Encode a value as YAML.
   *
   * @param array $value
   *   A value.
   *
   * @return string
   *   The value encoded as YAML.
   */
  public static function yamlEncodeFromConfig(array $value): string {
    $yaml = $value ? Yaml::encode($value) : '';
    // Remove return after array delimiter.
    $yaml = preg_replace('#((?:\n|^)[ ]*-)\n[ ]+(\w|[\'"])#', '\1 \2', $yaml);
    return $yaml;
  }

  /**
   * Decode YAML string.
   *
   * @param string $value
   *   A YAML string.
   *
   * @return array|\Drupal\Core\Form\ToConfig
   *   Decoded YAML string.
   */
  public static function yamlDecodeToConfig(string $value): array|ToConfig {
    try {
      return $value ? Yaml::decode($value) : [];
    }
    catch (\Exception $exception) {
      return ToConfig::NoOp;
    }
  }

  /**
   * Encode a value as JSON.
   *
   * @param array $value
   *   A value.
   *
   * @return string
   *   The value encoded as JSON.
   */
  public static function jsonEncodeFromConfig(array $value): string {
    return $value ? Json::encode($value) : '';
  }

  /**
   * Decode JSON string.
   *
   * @param string $value
   *   A JSON string.
   *
   * @return array|\Drupal\Core\Form\ToConfig
   *   Decoded JSON string.
   */
  public static function jsonDecodeToConfig(string $value): array|ToConfig {
    try {
      return $value ? Json::decode($value) : [];
    }
    catch (\Exception $exception) {
      return ToConfig::NoOp;
    }
  }

  /* ************************************************************************ */
  // Validation methods.
  /* ************************************************************************ */

  /**
   * Validates the element and sets error if validation fails.
   *
   * @param array $element
   *   The element to be validated.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   * @param array $complete_form
   *   The complete form array.
   */
  public static function validateElement(array &$element, FormStateInterface $form_state, array &$complete_form): void {
    $mode = $element['#mode'];

    try {
      static::validate($mode, $element['#value']);
    }
    catch (\Exception $exception) {
      $t_args = [
        '@name' => $element['#title'],
        '@mode' => strtoupper($mode),
        '%error' => $exception->getMessage(),
      ];
      $form_state->setError($element, t('@name field is not valid @mode. %error', $t_args));
      return;
    }

    $raw = $element['#raw'];
    if ($raw) {
      return;
    }

    // Decode the value so that it can be validated.
    $settings = ($element['#mode'] === 'json')
      ? Json::decode($element['#value']) ?? []
      : Yaml::decode($element['#value']) ?? [];

    if (empty($element['#config_target'])) {
      $form_state->setValueForElement($element, $settings);
      $config_name = $element['#config_name'];
      $config_key = $element['#config_key'];
    }
    else {
      /** @var \Drupal\Core\Form\ConfigTarget $config_target */
      $config_target = $element['#config_target'];
      $config_name = $config_target->configName;
      $config_key = implode('.', (array) $config_target->propertyPaths);
    }

    /** @var \Drupal\schemadotorg\SchemaDotOrgConfigManagerInterface $schema_config_manager */
    $schema_config_manager = \Drupal::service('schemadotorg.config_manager');
    $t_args = ['@name' => $element['#title']];
    try {
      $errors = $schema_config_manager->checkConfigValue($config_name, $config_key, $settings);
      if (is_array($errors)) {
        // Prefix the error with the exact config key triggering the error.
        [, $error_config_key] = explode(':', array_key_first($errors));
        $t_args['%error'] = $error_config_key . ' - ' . reset($errors);
        $form_state->setError($element, t('@name field is invalid.<br/>%error', $t_args));
      }
    }
    catch (\Exception $exception) {
      $t_args['%error'] = $exception->getMessage();
      $form_state->setError($element, t('@name field is invalid.<br/>%error', $t_args));
    }
  }

  /**
   * Validate YAML or JSON.
   *
   * @param string $mode
   *   The data's mode (YAML or JSON).
   * @param string|null $value
   *   The raw data YAML or JSON string to be decoded.
   *
   * @throws \Exception
   *   Throw an exception when the raw data YAML or JSON string
   *   can't be decoded.
   */
  public static function validate(string $mode, ?string $value): void {
    if ($value === '') {
      return;
    }

    switch ($mode) {
      case 'yaml':
        Yaml::decode($value);
        return;

      case 'json':
        // Replace all tokens with 'null' to allow the JSON to be validated.
        $value = preg_replace('#\[[a-z][^]]+\]#', 'null', $value);
        @json_decode($value);
        if (json_last_error() !== JSON_ERROR_NONE) {
          throw new \Exception(json_last_error_msg());
        }
        return;

      default;
        throw new \Exception('Unknown "' . $mode . '" settings mode.');
    }
  }

}
