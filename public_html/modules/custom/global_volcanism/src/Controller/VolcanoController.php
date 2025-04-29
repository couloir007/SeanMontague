<?php

namespace Drupal\global_volcanism\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\global_volcanism\Services\VolcanoesService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class VolcanoController extends ControllerBase {

  protected VolcanoesService $volcanoService;

  public function __construct(VolcanoesService $volcano_service) {
    $this->volcanoService = $volcano_service;
  }

  public static function create(ContainerInterface $container) {
    return new static(
    // The service ID you declared in .services.yml
      $container->get('global_volcanism.volcanoes')
    );
  }

  public function latest() {
    // Calls NoaaApiClient::get() via your child class
    $data = $this->volcanoService->getLatestActivity();

    return [
      '#theme' => 'item_list',
      '#title' => $this->t('Latest Volcano Activity'),
      '#items' => array_map(fn($v) => $v['name'] . ' (' . $v['date'] . ')', $data ?? []),
    ];
  }
}
