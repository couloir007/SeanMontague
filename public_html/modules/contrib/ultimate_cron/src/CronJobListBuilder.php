<?php

namespace Drupal\ultimate_cron;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines a class to build a listing of cron jobs.
 *
 * @see \Drupal\ultimate_cron\Entity\CronJob
 */
class CronJobListBuilder extends DraggableListBuilder {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ultimate_cron_job_list';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [];
    $header['label'] = ['data' => $this->t('Title')];
    $header['module'] = ['data' => $this->t('Module')];
    $header['scheduled'] = ['data' => $this->t('Scheduled')];
    $header['started'] = ['data' => $this->t('Last Run')];
    $header['duration'] = ['data' => $this->t('Duration')];
    $header['status'] = ['data' => $this->t('Status')];
    return $header + parent::buildHeader();
  }
  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\ultimate_cron\CronJobInterface $entity */
    $icon = \Drupal::service('extension.list.module')->getPath('ultimate_cron') . '/icons/hourglass.png';
    $behind_icon = [
      '#prefix' => ' ',
      '#theme' => 'image',
      '#uri' => \Drupal::service('file_url_generator')->generateAbsoluteString($icon),
      '#title' => $this->t('Job is behind schedule!'),
    ];

    $log_entry = $entity->loadLatestLogEntry();
    $row['label'] = $entity->label();
    $row['module']['#markup'] = $entity->getModuleName();
    $row['module']['#wrapper_attributes']['title'] = $entity->getModuleDescription();
    $row['scheduled']['label']['#markup'] = $entity->getPlugin('scheduler')->formatLabel($entity);
    if ($entity->isScheduled()) {
      $row['scheduled']['behind'] = $behind_icon;
    }
    // If the start time is 0, the jobs have never been run.
    $row['started']['#markup'] = $log_entry->start_time ? \Drupal::service('date.formatter')->format((int) $log_entry->start_time, "short") : $this->t('Never');

    // Display duration
    $progress = $entity->isLocked() ? $entity->formatProgress() : '';
    $row['duration'] = [
      '#markup' => '<span class="duration-time" data-src="' . $log_entry->getDuration() . '">' . $log_entry->formatDuration() . '</span> <span class="duration-progress">' . $progress . '</span>',
      '#wrapper_attributes' => ['title' => $log_entry->formatEndTime()],
     ];

    if (!$entity->isValid()) {
      $row['status']['#markup'] = $this->t('Missing');
    }
    elseif (!$entity->status()) {
      $row['status']['#markup'] = $this->t('Disabled');
    }
    else {
      // Get the status from the launcher when running, otherwise use the last
      // log entry.
      if ($entity->isLocked() && $log_entry->lid == $entity->isLocked()) {
        list($status, $title) = $entity->getPlugin('launcher')->formatRunning($entity);
      }
      elseif ($log_entry->start_time && !$log_entry->end_time) {
        list($status, $title) = $entity->getPlugin('launcher')->formatUnfinished($entity);
      }
      else {
        list($status, $title) = $log_entry->formatSeverity();
        $title = $log_entry->message ? $log_entry->message : $title;
      }

      $row['status'] = $status;
      $row['status']['#wrapper_attributes']['title'] = $title;
    }

    $row += parent::buildRow($entity);
    $row['weight']['#delta'] = 50;
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity, ?CacheableMetadata $cacheability = NULL) {
    $operations = parent::getDefaultOperations($entity, $cacheability);
    if ($entity->status() && $entity->isValid()) {
      if (!$entity->isLocked()) {
        $operations += [
          'run' => [
            'title' => $this->t('Run'),
            'weight' => 9,
            'url' => $entity->toUrl('run'),
          ],
        ];
      }
      else {
        $operations += [
          'unlock' => [
            'title' => $this->t('Unlock'),
            'weight' => 9,
            'url' => $entity->toUrl('unlock'),
          ],
        ];
      }
    }

    $operations += [
      'logs' => [
        'title' => $this->t('Logs'),
        'weight' => 10,
        'url' => $entity->toUrl('logs'),
      ],
    ];

    // Invalid jobs can not be enabled nor disabled.
    if (!$entity->isValid()) {
      unset($operations['disable']);
      unset($operations['enable']);
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entity_ids = $this->getEntityIds();
    $entities = $this->storage->loadMultiple($entity_ids);

    // Sort the entities using the entity class's sort() method.
    // See \Drupal\Core\Config\Entity\ConfigEntityBase::sort().
    uasort($entities, [$this->entityType->getClass(), 'sort']);
    return $entities;
  }

}
