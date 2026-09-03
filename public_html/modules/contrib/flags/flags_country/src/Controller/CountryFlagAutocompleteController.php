<?php

namespace Drupal\flags_country\Controller;

use Drupal\Core\Render\RendererInterface;
use Drupal\country\Controller\CountryAutocompleteController;
use Drupal\country\CountryFieldManager;
use Drupal\field\Entity\FieldConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns autocomplete responses for countries.
 */
class CountryFlagAutocompleteController extends CountryAutocompleteController {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * CountryFlagAutocompleteController constructor.
   *
   * @param \Drupal\country\CountryFieldManager $country_field_manager
   *   The country field manager service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(
    CountryFieldManager $country_field_manager,
    RendererInterface $renderer,
  ) {
    $this->renderer = $renderer;
    parent::__construct($country_field_manager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('country.field.manager'),
      $container->get('renderer'),
    );
  }

  /**
   * Returns response for the country name autocompletion.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object containing the search string.
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The bundle name.
   * @param string $field_name
   *   The field name.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions for countries.
   */
  public function autocomplete(
    Request $request,
    $entity_type,
    $bundle,
    $field_name,
  ) {
    $matches = [];
    $string = $request->query->get('q');
    if ($string) {
      $field_definition = FieldConfig::loadByName($entity_type, $bundle, $field_name);
      $countries = $this->countryFieldManager->getSelectableCountries($field_definition);
      foreach ($countries as $iso2 => $country) {
        if (strpos(mb_strtolower($country), mb_strtolower($string)) !== FALSE) {
          $label = [
            'country' => ['#markup' => $country],
            'flag' => [
              '#theme' => 'flags',
              '#code' => strtolower($iso2),
              '#source' => 'country',
            ],
          ];

          $matches[] = ['value' => $country, 'label' => $this->renderer->render($label)];
        }
      }
    }
    return new JsonResponse($matches);
  }

}
