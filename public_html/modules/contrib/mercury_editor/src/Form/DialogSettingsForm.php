<?php

namespace Drupal\mercury_editor\Form;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Mercury Editor Edit Tray settings form.
 */
class DialogSettingsForm extends ConfigFormBase {

  /**
   * The YAML parser service.
   *
   * @var \Drupal\Component\Serialization\Yaml
   */
  protected $yamlParser;

  /**
   * Default values for dialog settings.
   */
  const DEFAULT_TRAY_WIDTH = 400;
  const DEFAULT_TRAY_HEIGHT = 400;
  const DEFAULT_ROLLOVER_PADDING_BLOCK = 10;
  const DEFAULT_ROLLOVER_PADDING_INLINE = 0;

  /**
   * SettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   *   The typed config service.
   * @param \Drupal\Core\Serialization\Yaml $yaml_parser
   *   The YAML parser service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typedConfigManager,
    Yaml $yaml_parser,
  ) {
    parent::__construct(
      $config_factory,
      $typedConfigManager
    );
    $this->yamlParser = $yaml_parser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('serialization.yaml')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mercury_editor_dialog_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'mercury_editor.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('mercury_editor.settings');
    $settings_obj = $config->get('dialog_settings');

    $form['dialog_settings'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Modal dialog settings'),
      '#description' => $this->t('Enter the settings for the modal dialog (YAML format).'),
      '#default_value' => $this->yamlParser->encode($settings_obj),
    ];

    $form['dialog_tray_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Dialog tray width'),
      '#required' => TRUE,
      '#min' => 0,
      '#description' => $this->t('Enter the width of the dialog tray (pixels).'),
      '#default_value' => $config->get('dialog_tray_width') ?? self::DEFAULT_TRAY_WIDTH,
    ];

    if (class_exists('Drupal\codemirror_editor\Element\CodeMirror')) {
      $form['dialog_settings']['#type'] = 'codemirror';
      $form['dialog_settings']['#codemirror'] = [
        'mode' => 'yaml',
        'lineNumbers' => TRUE,
        'lineWrapping' => TRUE,
        'indentUnit' => 2,
        'indentWithTabs' => FALSE,
        'matchBrackets' => TRUE,
        'autoCloseBrackets' => TRUE,
        'autoCloseTags' => TRUE,
        'styleActiveLine' => TRUE,
        'continueComments' => TRUE,
        'toolbar' => FALSE,
        'extraKeys' => [
          'Ctrl-Space' => 'autocomplete',
        ],
      ];
    }

    $form['rollover_padding_block'] = [
      '#type' => 'number',
      '#title' => $this->t('Rollover block padding'),
      '#required' => TRUE,
      '#min' => 0,
      '#description' => $this->t('Enter block padding (pixels) when rolling over elements. This is used to prevent vertically-adjacent Mercury Editor buttons overlapping.'),
      '#default_value' => $config->get('rollover_padding_block') ?? self::DEFAULT_ROLLOVER_PADDING_BLOCK,
    ];

    $form['rollover_padding_inline'] = [
      '#type' => 'number',
      '#title' => $this->t('Rollover inline padding'),
      '#required' => TRUE,
      '#min' => 0,
      '#description' => $this->t('Enter inline padding (pixels) when rolling over elements. This is used to prevent horizontally-adjacent Mercury Editor buttons overlapping, but will likely cause content reflowing. Most sites can set this to 0.'),
      '#default_value' => $config->get('rollover_padding_inline') ?? self::DEFAULT_ROLLOVER_PADDING_INLINE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('mercury_editor.settings');
    $settings_yml_string = $form_state->getValue('dialog_settings');
    $config->set('dialog_settings', $this->yamlParser->decode($settings_yml_string));
    $config->set('dialog_tray_width', $form_state->getValue('dialog_tray_width'));
    $config->set('rollover_padding_block', $form_state->getValue('rollover_padding_block'));
    $config->set('rollover_padding_inline', $form_state->getValue('rollover_padding_inline'));
    $config->save();
    // Confirmation on form submission.
    $this->messenger()->addMessage($this->t('Mercury Editor dialog settings have been saved.'));
  }

}
