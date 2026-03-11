<?php

declare(strict_types=1);

namespace Drupal\paragraphs_usage\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Link;
use Drupal\field_ui\FieldUI;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\paragraphs_usage\Service\ParagraphsUsageService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Paragraph usage routes.
 */
class ParagraphsUsageController extends ControllerBase {

  /**
   * Entity Field Manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * Paragraphs Usage Service.
   *
   * @var \Drupal\paragraphs_usage\Service\ParagraphsUsageService
   */
  protected $paragraphsUsageService;

  /**
   * ParagraphsUsageController constructor.
   *
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   Entity Field Manager service.
   * @param \Drupal\paragraphs_usage\Service\ParagraphsUsageService $paragraphs_usage_service
   *   Paragraphs Usage service.
   */
  public function __construct(EntityFieldManager $entity_field_manager, ParagraphsUsageService $paragraphs_usage_service) {
    $this->entityFieldManager = $entity_field_manager;
    $this->paragraphsUsageService = $paragraphs_usage_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_field_manager = $container->get('entity_field.manager');
    $paragraphs_usage_service = $container->get('paragraphs_usage.paragraphs_usage_service');
    return new static($entity_field_manager, $paragraphs_usage_service);
  }

  /**
   * Get Paragraphs usage.
   *
   * @param \Drupal\paragraphs\Entity\ParagraphsType $paragraphs_type
   *   Paragraphs type.
   *
   * @return array
   *   return render array.
   */
  public function getUsage(ParagraphsType $paragraphs_type): array {
    $rows = [];
    $header = [
      'label' => $this->t('Bundle'),
      'machine_name' => $this->t('Machine name'),
      'type' => $this->t('Type'),
      'field_name' => $this->t('Field name'),
    ];
    $this->paragraphsUsageService->setParagraphType($paragraphs_type);
    $used_paragraphs = $this->paragraphsUsageService->getUsedParagraphs();

    foreach ($used_paragraphs as $used_paragraph) {
      $entity_type_used = $used_paragraph['bundle_entity_type'] ?? $used_paragraph['entity_type'];
      $url = FieldUI::getOverviewRouteInfo($used_paragraph['entity_type'], $used_paragraph['bundle']);
      $type_link = Link::fromTextAndUrl($used_paragraph['entity_type_label'], $url);
      $rows[] = [
        $type_link,
        $used_paragraph['bundle'],
        $entity_type_used,
        $used_paragraph['field']['label'] . ' (' . $used_paragraph['field']['name'] . ')',
      ];
    }
    if (empty($rows)) {
      return [
        '#type' => 'item',
        '#markup' => $this->t('This paragraph is not used in any content type.'),
      ];
    }
    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];
  }

}
