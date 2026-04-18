<?php

namespace Drupal\mermaid_diagram_field\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the field type of MermaidDiagram.
 */
#[FieldType(
  id: "mermaid_diagram",
  label: new TranslatableMarkup('Mermaid diagram'),
  description: new TranslatableMarkup('A field for adding and rendering Mermaid diagrams.'),
  category: 'general',
  default_widget: 'mermaid_diagram_widget',
  default_formatter: 'mermaid_diagram_formatter'
)]
class MermaidDiagramItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        // List the values that the field will save.
        'title' => [
          'type' => 'varchar',
          'length' => '255',
          'not null' => FALSE,
        ],
        'diagram' => [
          'type' => 'text',
          'size' => 'medium',
          'not null' => FALSE,
        ],
        'caption' => [
          'type' => 'text',
          'size' => 'medium',
          'not null' => FALSE,
        ],
        'key' => [
          'type' => 'text',
          'size' => 'small',
          'not null' => FALSE,
        ],
        // Seems wrong to have to use int in place of boolean.
        'show_code' => [
          'type' => 'int',
          'size' => 'tiny',
          'not null' => FALSE,
        ],
        'allow_download' => [
          'type' => 'int',
          'size' => 'tiny',
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];

    $properties['title'] = DataDefinition::create('string')
      ->setLabel(t('Title'))
      ->setRequired(TRUE);
    $properties['caption'] = DataDefinition::create('string')
      ->setLabel(t('Caption'))
      ->setRequired(TRUE);
    $properties['diagram'] = DataDefinition::create('string')
      ->setLabel(t('Mermaid code'))
      ->setRequired(TRUE);
    $properties['key'] = DataDefinition::create('string')
      ->setLabel(t('Key code'))
      ->setRequired(FALSE);
    $properties['show_code'] = DataDefinition::create('string')
      ->setLabel(t('Expose the code'))
      ->setRequired(FALSE);
    $properties['allow_download'] = DataDefinition::create('string')
      ->setLabel(t('Allow download as .mermaid'))
      ->setRequired(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $title = $this->get('title')->getValue();
    $caption = $this->get('caption')->getValue();
    $diagram = $this->get('diagram')->getValue();
    // Whether is has show_code or not should not determine emptiness.
    return (empty($title)) && (empty($caption)) && (empty($diagram));
  }

}
