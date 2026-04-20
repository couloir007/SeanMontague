<?php

namespace Drupal\map_page\Plugin\views\display_extender;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\views\Plugin\views\display_extender\DisplayExtenderPluginBase;

/**
 * Map Page display extender plugin.
 *
 * @ingroup views_display_extender_plugins
 *
 * @ViewsDisplayExtender(
 *   id = "map_page_display_extender",
 *   title = @Translation("Map Page display extender"),
 *   help = @Translation("Map Page settings for this view."),
 *   no_ui = FALSE
 * )
 */
class MapPageDisplayExtender extends DisplayExtenderPluginBase {

  use StringTranslationTrait;

  /**
   * The first row tokens on the style plugin.
   *
   * @var array
   */
  protected static $firstRowTokens;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['template'] = ['default' => 'none'];

    return $options;
  }

  /**
   * Provide a form to edit options for this plugin.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    switch ($form_state->get('section')) {
      case 'map_page_template':
        $form['#title'] = $this->t('Map Page Template');
        $form['description'] = [
          '#type' => 'markup',
          '#markup' => '<div>' . $this->t('Select the template to use for this view display.') . '</div>',
        ];
        $form['template'] = [
          '#type' => 'select',
          '#title' => t('Template'),
          '#description' => t('Select the template to use for this view display.'),
          '#options' => [
            'legend' => t('With Legend'),
            'no_legend' => t('Without Legend'),
          ],
        ];
        break;
    }
  }

  /**
   * Validate the options form.
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {}

  /**
   * Handle any special handling on the validate form.
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    switch ($form_state->get('section')) {
      case 'map_page_template':
        $this->options['template'] = $values['template'];
        break;
    }
  }

  /**
   * Set up any variables on the view prior to execution.
   */
  public function preExecute() {}

  /**
   * Inject anything into the query that the display_extender handler needs.
   */
  public function query() {}

  /**
   * Provide the default summary for options in the views UI.
   *
   * This output is returned as an array.
   */
  public function optionsSummary(&$categories, &$options) {
    $categories['map_page'] = [
      'title' => $this->t('Map Page Template'),
      'column' => 'second',
    ];

    $map_page_template = empty($this->options['template']) ? NULL : $this->options['template'];

    $options['map_page_template'] = [
      'category' => 'map_page',
      'title' => $this->t('Map Page Template'),
      'value' => $map_page_template,
    ];
  }

  /**
   * Lists defaultable sections and items contained in each section.
   */
  public function defaultableSections(&$sections, $section = NULL) {}
}
