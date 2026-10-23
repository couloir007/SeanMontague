<?php

namespace Drupal\geolocation_gpx\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\geolocation_gpx\GeolocationGpxImporter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Creates a GPX entity from an uploaded file.
 *
 * The upload is only used as import source. It is read straight from PHP's
 * own request scratch space, so no managed file is ever created and nothing
 * has to be cleaned up afterwards - once the entity is built, the file is
 * gone.
 */
class GeolocationGpxUploadForm extends FormBase {

  use GeolocationGpxOverviewUrlTrait;

  /**
   * Constructor.
   */
  public function __construct(
    protected GeolocationGpxImporter $importer,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('geolocation_gpx.importer'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'geolocation_gpx_upload_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['gpx_file'] = [
      '#type' => 'file',
      '#title' => $this->t('GPX file'),
      '#description' => $this->t('Allowed file types: <i>gpx, xml</i>. The file is parsed into a GPX data set and discarded afterwards, it is not stored.'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Upload'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $upload = $this->getRequest()->files->get('files', [])['gpx_file'] ?? NULL;

    if (!$upload?->isValid()) {
      $form_state->setErrorByName('gpx_file', $this->t('The file could not be uploaded.'));

      return;
    }

    if (!in_array(strtolower(pathinfo($upload->getClientOriginalName(), PATHINFO_EXTENSION)), ['gpx', 'xml'])) {
      $form_state->setErrorByName('gpx_file', $this->t('Only <i>gpx</i> and <i>xml</i> files can be imported.'));

      return;
    }

    try {
      $data = $this->importer->parse($upload->getRealPath());
    }
    catch (\Exception $e) {
      $form_state->setErrorByName('gpx_file', $this->t('Could not import GPX file: @message', ['@message' => $e->getMessage()]));

      return;
    }

    $form_state->set('gpx_data', $data);
    $form_state->set('gpx_file_name', $upload->getClientOriginalName());
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $gpx = $this->importer->import(
      $form_state->get('gpx_data'),
      $form_state->get('gpx_file_name'),
    );

    $this->messenger()->addStatus($this->t('Created GPX %label.', [
      '%label' => $gpx->label(),
    ]));

    $form_state->setRedirectUrl($this->overviewUrl());
  }

}
