<?php

namespace Drupal\mercury_editor_templates\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\layout_paragraphs\LayoutParagraphsLayout;
use Drupal\layout_paragraphs\LayoutParagraphsComponent;
use Drupal\mercury_editor_templates\Entity\MeTemplate;

/**
 * Controller for inserting a template.
 */
class SaveAsTemplate extends ControllerBase {

  /**
   * A Mercury Editor layout object.
   *
   * @var \Drupal\layout_paragraphs\LayoutParagraphsLayout
   */
  protected $layoutParagraphsLayout;

  /**
   * Save a paragraph entity as a template.
   *
   * @param \Drupal\layout_paragraphs\LayoutParagraphsLayout $layout_paragraphs_layout
   *   The paragraph entity to save.
   * @param string $uuid
   *   The uuid of the paragraph entity.
   *
   * @return array
   *   A render array.
   */
  public function templateForm(LayoutParagraphsLayout $layout_paragraphs_layout, string $uuid) {
    $this->layoutParagraphsLayout = $layout_paragraphs_layout;
    $duplicate_component = $this->layoutParagraphsLayout->duplicateComponent($uuid);
    $uuids = $this->getComponentUuids($duplicate_component);
    $field_content = [];
    foreach ($uuids as $uuid) {
      $entity = $this->layoutParagraphsLayout->getComponentByUuid($uuid)->getEntity();
      $field_content[] = [
        'entity' => $entity,
      ];
    }
    // The first paragraph should be the layout paragraphs container, with
    // its parent uuid and region removed.
    $layout_settings = $field_content[0]['entity']->getAllBehaviorSettings()['layout_paragraphs'];
    $layout_settings['parent_uuid'] = NULL;
    $layout_settings['region'] = NULL;
    $field_content[0]['entity']->setBehaviorSettings('layout_paragraphs', $layout_settings);

    $me_template = MeTemplate::create([
      'content' => $field_content,
      'status' => TRUE,
    ]);
    $edit_form = \Drupal::entityTypeManager()->getFormObject('me_template', 'dialog')->setEntity($me_template);
    return \Drupal::formBuilder()->getForm($edit_form, $this->layoutParagraphsLayout);
  }

  /**
   * Returns a list of all component uuids and descendent uuids in a layout.
   *
   * @param \Drupal\layout_paragraphs\LayoutParagraphsComponent $component
   *   The component to get uuids for.
   * @param array $uuids
   *   The uuids array.
   *
   * @return array
   *   An array of uuids.
   */
  protected function getComponentUuids(LayoutParagraphsComponent $component, array $uuids = []) {
    $uuids[] = $component->getEntity()->uuid();
    if ($component->isLayout()) {
      $section = $this->layoutParagraphsLayout->getLayoutSection($component->getEntity());
      foreach ($section->getComponents() as $component) {
        $uuids += $this->getComponentUuids($component, $uuids);
      }
    }
    return $uuids;
  }

}

