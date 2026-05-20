<?php

namespace Drupal\content_model_documentation\Report;

/**
 * A report that shows field counts for all the entities.
 */
class FieldsCount extends ReportBase implements ReportInterface, ReportTableInterface, ReportDiagramInterface {

  /**
   * The footer row.
   *
   * @var array
   */
  protected $footer = [];

  /**
   * {@inheritdoc}
   */
  public static function getReportTitle(): string {
    return 'Field counts';
  }

  /**
   * {@inheritdoc}
   */
  public function getReportType(): string {
    return 'table';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {

    return $this->t("This is a snapshot of this field counts across all entity bundles.");
  }

  /**
   * {@inheritdoc}
   */
  public function getCaption(): string {
    return $this->t('List of field counts across all the entity bundles.');
  }

  /**
   * {@inheritdoc}
   */
  public function getHeaderRow(): array {
    $header = [
      $this->t('Id'),
      $this->t('Entity Id'),
      $this->t('Field Count'),
    ];
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function getFooterRow(): array {
    return $this->footer;
  }

  /**
   * {@inheritdoc}
   */
  public function getTableBodyRows(): array {
    $rows = $this->getData();
    return $rows;
  }

  /**
   * {@inheritdoc}
   */
  public function getCsvBodyRows(): array {
    // Will need to convert links to urls.
    $rows = $this->getData();
    return $rows;
  }

  /**
   * Gets the data for the table.
   *
   * @return array
   *   The data of the rows, but not including documentation.
   */
  protected function getData(): array {
    $entity_bundle_fields_count = $this->reportManager->getFieldsCountBundleWise();
    $total = 0;

    $resultTable = [];
    foreach ($entity_bundle_fields_count as $entity => $bundle) {
      foreach ($bundle as $data) {
        $resultTable[$data["id"]] = [
          'id' => $data["id"],
          'entity_id' => $entity,
          'field_count' => $data["field_count"],
        ];
        $total += $data["field_count"];
      }
    }
    $footer = [];
    $footer['id'] = (string) $this->t('TOTAL');
    $footer['entity_id'] = '';
    $footer['field_count'] = "{$total} {$this->t('Field instances')}";
    $this->footer = [$footer];
    return $resultTable;
  }

  /**
   * Builds the Mermaid string for the diagram.
   *
   * @return string
   *   The string that is the Mermaid Diagram.
   */
  protected function getDiagram(): string {
    $entity_bundle_fields_count = $this->reportManager->getFieldsCountBundleWise();
    if (empty($entity_bundle_fields_count)) {
      // There is nothing to diagram, bail out.
      return '';
    }
    $mermaid = "";
    $total = 0;
    foreach ($entity_bundle_fields_count as $entity => $bundle) {
      foreach ($bundle as $data) {
        $count = $data['field_count'] ?? 0;
        $label = "{$entity}:{$data['id']}";
        $mermaid .= "  \"$label\": {$count}" . PHP_EOL;
        $total += $data['field_count'];
      }
    }
    $vars = [
      '@total_count' => $total,
      '@entity_count' => count($entity_bundle_fields_count),
    ];
    $title = $this->t('There are @total_count field instances across @entity_count bundles.', $vars);
    $mermaid = "pie showData title $title" . PHP_EOL . $mermaid;

    return $mermaid;
  }

  /**
   * {@inheritdoc}
   */
  public function getDiagramList(): array {
    $diagrams = [
      'Fields on entity bundles' => [
        'diagram' => $this->getDiagram(),
        'caption' => $this->t('All field counts.'),
        'key' => '',
      ],
    ];
    return $diagrams;
  }

  /**
   * Gets a render array for something to display above the table.
   *
   * @return array
   *   A drupal render array for the diagram.
   */
  protected function getPreReport(): array {
    return $this->buildDiagramPage(' ');
  }

}
