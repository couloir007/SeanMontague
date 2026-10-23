<?php

namespace Drupal\search_api_opensolr_security\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an interface defining the opensolr admin security form.
 *
 * @package Drupal\search_api_opensolr_security\Form
 */
interface SecurityAdminFormInterface {

  /**
   * Form submission handler for adding HTTP auth to opensolr core.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function addHttpAuth(array &$form, FormStateInterface $form_state);

  /**
   * Form submission handler for adding ip restriction to opensolr core.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function addIpRestriction(array &$form, FormStateInterface $form_state);

  /**
   * Form submission handler for removing ip restrictions from opensolr core.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function removeIpRestrictions(array &$form, FormStateInterface $form_state);

}
