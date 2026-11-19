<?php

/**
 * @file
 * Post update functions for Ultimate Cron.
 */

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\ultimate_cron\CronJobInterface;


/**
 * Convert cron jobs to the new custom hook callback format.
 */
function ultimate_cron_post_update_hook_callbacks() {
  $storage = \Drupal::entityTypeManager()->getStorage('ultimate_cron_job');
  assert($storage instanceof ConfigEntityStorageInterface);
  $jobs = $storage->loadMultipleOverrideFree();

  // Convert cron jobs to the custom hook callback format.
  foreach ($jobs as $job) {
    assert($job instanceof CronJobInterface);
    if ($job->getCallback() === $job->getModule() . '_cron') {
      $job->setCallback($job->getModule() . '#cron');
      $job->save();
    }
  }
}
