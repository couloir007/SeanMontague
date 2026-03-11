<?php

namespace Drupal\content_model_documentation\Report;

/**
 * A report that shows all block_content types and their counts.
 */
class BlockContentCount extends ReportBase implements ReportInterface, ReportTableInterface, ReportDiagramInterface {

  const ENTITY_TYPE = 'block_content_type';

  const ENTITY_BUNDLE = 'block_content';

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
    return 'Block Content counts';
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
    $vars = ['@content_type' => self::ENTITY_TYPE];
    if ($this->entityTypeExists(self::ENTITY_TYPE)) {
      return $this->t("This is a snapshot of this site's @content_type types.", $vars);
    }
    return $this->t("The @content_type entity type does not exist on this site.", $vars);
  }

  /**
   * {@inheritdoc}
   */
  public function getCaption(): string {
    return $this->t('List of block_content types and the number of each in use.');
  }

  /**
   * {@inheritdoc}
   */
  public function getHeaderRow(): array {
    $header = [
      $this->t('Label'),
      $this->t('Id'),
      $this->t('Total'),
      $this->t('Published'),
      $this->t('Unpublished'),
    ];
    if ($this->config->get('block')) {
      array_push($header, $this->t('Documentation'));
    }
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
    if ($this->config->get('block')) {
      $rows = $this->addDocumentationColumn('block', '', $rows);
    }
    return $rows;
  }

  /**
   * {@inheritdoc}
   */
  public function getCsvBodyRows(): array {
    // Will need to convert links to urls.
    $rows = $this->getData();
    if ($this->config->get('block')) {
      $rows = $this->addDocumentationColumn('block', '', $rows, TRUE);
    }
    return $rows;
  }

  /**
   * Gets the data for the table.
   *
   * @return array
   *   The data of the rows, but not including documentation.
   */
  protected function getData(): array {
    $label_content_types = $this->getContentTypeLabels();
    $bundles_counted = $this->getBundleTotalCount();
    $published_buldle_count = $this->getBundlePublishedCount();
    $total = 0;
    $total_published = 0;
    $total_not_published = 0;
    $resultTable = [];
    foreach ($label_content_types as $bundle => $content_type_label) {
      $resultTable[$bundle] = [
        'label' => $content_type_label,
        'id' => $bundle,
        'total' => $bundles_counted[$bundle],
        'publish' => $published_buldle_count[$bundle],
        'no_publish' => $bundles_counted[$bundle] - $published_buldle_count[$bundle],
      ];
      $total += $bundles_counted[$bundle];
      $total_published += $published_buldle_count[$bundle];
      $total_not_published += ($bundles_counted[$bundle] - $published_buldle_count[$bundle]);
    }
    $content_type_count = count($resultTable);
    $footer = [];
    $footer['label'] = (string) $this->t('TOTAL');
    $footer['id'] = "{$content_type_count} {$this->t('Block types')}";
    $footer['total'] = "{$total} {$this->t('blocks')}";
    $footer['publish'] = "{$total_published} {$this->t('published blocks')}";
    $footer['no-publish'] = "{$total_not_published} {$this->t('un-published blocks')}";
    $this->footer = [$footer];
    return $resultTable;
  }

  /**
   * Gets an array of block content labels.
   *
   * @return array
   *   An array of node content labels.
   */
  protected function getContentTypeLabels() {
    $label_content_types = [];
    $types = ($this->entityTypeExists(self::ENTITY_TYPE)) ? $this->entityTypeManager->getStorage(self::ENTITY_TYPE)->loadMultiple() : [];
    foreach ($types as $key => $type) {
      $label = $type->label();
      $machine_name = $type->id();
      if (empty($label_content_types[$machine_name])) {
        $label_content_types[$machine_name] = $label;
      }
    }
    natcasesort($label_content_types);

    return $label_content_types;
  }

  /**
   * Gets the count of total blocks.
   *
   * @return array
   *   The array of bundles with a total count.
   */
  protected function getBundleTotalCount() {
    $block_type_counts = [];
    if ($this->entityTypeExists(self::ENTITY_TYPE) && $this->entityTypeExists(self::ENTITY_BUNDLE)) {
      $block_types = $this->entityTypeManager->getStorage(self::ENTITY_TYPE)->loadMultiple();
      foreach ($block_types as $block_type) {
        $type_id = $block_type->id();
        // Query to count the number of blocks of this type.
        $query = $this->entityTypeManager->getStorage(self::ENTITY_BUNDLE)->getQuery();
        $query->condition('type', $type_id)->accessCheck(FALSE);
        $count = $query->count()->execute();

        // Store the count in the array.
        $block_type_counts[$type_id] = $count;
      }
    }
    return $block_type_counts;
  }

  /**
   * Gets the count of total blocks.
   *
   * @return array
   *   The array of bundles with a total count.
   */
  protected function getBundlePublishedCount() {
    $block_type_counts = [];
    if ($this->entityTypeExists(self::ENTITY_TYPE) && $this->entityTypeExists(self::ENTITY_BUNDLE)) {
      $block_types = $this->entityTypeManager->getStorage(self::ENTITY_TYPE)->loadMultiple();
      foreach ($block_types as $block_type) {
        $type_id = $block_type->id();
        // Query to count the number of blocks of this type.
        $query = $this->entityTypeManager->getStorage(self::ENTITY_BUNDLE)->getQuery();
        $query->condition('type', $type_id)->condition('status', '1', '=')->accessCheck(FALSE);
        $count = $query->count()->execute();

        // Store the count in the array.
        $block_type_counts[$type_id] = $count;
      }
    }
    return $block_type_counts;
  }

  /**
   * Builds the Mermaid string for the diagram.
   *
   * @return string
   *   The string that is the Mermaid Diagram.
   */
  protected function getDiagram(): string {
    $label_content_types = $this->getContentTypeLabels();
    if (empty($label_content_types)) {
      // There is nothing to diagram, bail out.
      return '';
    }
    // Sorting is largely irrelevant because mermaid will sort from high to low.
    // The reason for the sort is in the case of screen readers it reads raw.
    asort($label_content_types, SORT_NATURAL);
    $bundles = $this->getBundleTotalCount();
    $bundle_count = count($bundles);
    $vars = ['@total_count' => $bundle_count];

    $title = $this->t('There are @total_count block_content types (bundles).', $vars);
    $mermaid = "pie showData title $title" . PHP_EOL;
    foreach ($label_content_types as $machine_name => $label_content_type) {
      $count = $bundles[$machine_name] ?? 0;
      $mermaid .= "  \"$label_content_type\": {$count}" . PHP_EOL;
    }

    return $mermaid;
  }

  /**
   * {@inheritdoc}
   */
  public function getDiagramList(): array {
    $diagrams = [
      'Block Content Types' => [
        'diagram' => $this->getDiagram(),
        'caption' => $this->t('All block_content bundle counts.'),
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
