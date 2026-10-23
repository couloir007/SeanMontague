<?php

namespace Drupal\search_api_opensolr;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Helper class for manipulating opensolr error codes.
 */
class OpenSolrErrors {

  /**
   * Transforms the given opensolr error code.
   *
   * @param string $errorCode
   *   The opensolr error code.
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup
   *   Returns the human-readable translation of the given opensolr error code.
   */
  public static function getError(string $errorCode): string|TranslatableMarkup {
    return match (TRUE) {
      $errorCode == 'ERROR_AUTHENTICATION_FAILED' => t('Authentication failed'),
      $errorCode == 'ERROR_CANNOT_ADD_MORE_THAN_1_CORES' => t('Cannot add more than 1 core. Consider <a href="https://opensolr.com/pricing" target="_blank">upgrading</a> your opensolr account.'),
      default => $errorCode,
    };
  }

}
