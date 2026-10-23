<?php

namespace Drupal\search_api_opensolr;

/**
 * Methods for running the opensolr AutoConfig in a batch.
 *
 * @see \Drupal\search_api_opensolr\AutoConfig
 */
class AutoConfigBatch {

  /**
   * Processes the autoconfigure batch and persists the autoconfig object.
   *
   * @param \Drupal\search_api_opensolr\AutoConfig $autoConfig
   *   The Autoconfig object to persist.
   * @param int $step
   *   The autoconfigure step to take.
   * @param array $context
   *   The batch context.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\search_api\SearchApiException|\Drupal\Core\Entity\EntityMalformedException
   */
  public static function process(AutoConfig $autoConfig, int $step, array &$context) {
    if (!isset($context['sandbox']['auto_config'])) {
      $context['sandbox']['auto_config'] = $autoConfig;
    }
    /** @var \Drupal\search_api_opensolr\AutoConfig $autoConfig */
    $autoConfig = $context['sandbox']['auto_config'];
    $autoConfig->doAutoCreate($step, $context);
  }

  /**
   * Finish autoconfigure batch callback.
   *
   * @param bool $success
   *   Indicate that the batch API tasks were all completed successfully.
   * @param array $results
   *   An array of all the results that were updated in update_do_one().
   * @param array $operations
   *   A list of the operations that had not been completed by the batch API.
   */
  public static function finish(bool $success, array $results, array $operations) {
    $messenger = \Drupal::messenger();
    if ($success) {
      if (!empty($results['errors'])) {
        $logger = \Drupal::logger('search_api_opensolr');
        foreach ($results['errors'] as $error) {
          $messenger->addError($error);
          $logger->error($error);
        }
        $messenger->addWarning(t('The autoconfigure process was executed with errors.'));
      }
      else {
        $messenger->addStatus(t('The autoconfigure process was successful.'));
        $messages = $results['success'] ?? [];
        foreach ($messages as $message) {
          $messenger->addStatus($message);
        }
      }
    }
    else {
      // An error occurred.
      // $operations contains the operations that remained unprocessed.
      $error_operation = reset($operations);
      $message = t('An error occurred while processing %error_operation with arguments: @arguments', [
        '%error_operation' => $error_operation[0],
        '@arguments' => print_r($error_operation[1], TRUE),
      ]);
      $messenger->addError($message);
    }
  }

}
