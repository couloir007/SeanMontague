<?php

namespace Drupal\global_volcanism\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\global_volcanism\Services\VolcanoesService;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\file\Entity\File;

class VolcanoImportForm extends FormBase {

  protected $volcanoesService;

  public function __construct(VolcanoesService $volcanoesService) {
    $this->volcanoesService = $volcanoesService;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('global_volcanism.VolcanoesService')
    );
  }

  public function getFormId() {
    return 'global_volcanism_volcano_import_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['csv_file'] = [
      '#type' => 'file',
      '#title' => $this->t('Upload tab-delimited file'),
      '#description' => $this->t('Upload the tab-delimited volcano data file.'),
      '#upload_location' => 'temporary://',
      // This is for managed files if you want to save or reuse
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import'),
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $validators = ['file_validate_extensions' => ['txt tsv csv']];
    $file = file_save_upload('csv_file', $validators, 'temporary://', 0);

    if (!$file) {
      $form_state->setErrorByName('csv_file', $this->t('Please upload a valid tab-delimited text file.'));
    } else {
      $form_state->setValue('csv_file', $file);
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\file\Entity\File $file */
    $file = $form_state->getValue('csv_file');

    if ($file) {
      try {
        $filepath = $file->getFileUri();
        // Copy file to local filesystem path for reading.
        $realpath = \Drupal::service('file_system')->realpath($filepath);

        $volcanoes = $this->volcanoesService->importVolcanoDataFromFile($realpath);

        $this->messenger()
          ->addMessage($this->t('Successfully imported @count volcano records.', ['@count' => $volcanoes]));
      }
      catch (\Exception $e) {
        $this->messenger()
          ->addError($this->t('Import failed: @message', ['@message' => $e->getMessage()]));
      }
    }
  }

}
