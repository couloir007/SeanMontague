<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg\Kernel;

use Drupal\node\Entity\NodeType;
use Drupal\schemadotorg\Controller\SchemaDotOrgAutocompleteController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the Schema.org autocomplete controller.
 *
 * @coversClass \Drupal\schemadotorg\Controller\SchemaDotOrgAutocompleteController
 * @group schemadotorg
 */
class SchemaDotOrgAutocompleteControllerKernelTest extends SchemaDotOrgKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
  ];

  /**
   * The Schema.org autocomplete controller.
   */
  protected SchemaDotOrgAutocompleteController $controller;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installSchemaDotOrg();
    $this->installEntitySchema('node');
    NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ])->save();
    $this->controller = SchemaDotOrgAutocompleteController::create($this->container);
  }

  /**
   * Test the Schema.org autocomplete controller.
   */
  public function testAutocompleteController(): void {
    // Check searching for 'Thing' within Schema.org types returns 3 results.
    $result = $this->controller->autocomplete(new Request(['q' => 'Thing']), 'types');
    $this->assertEquals('[{"value":"ClothingStore","label":"ClothingStore"},{"value":"MensClothingStore","label":"MensClothingStore"},{"value":"Thing","label":"Thing"}]', $result->getContent());

    // Check searching for 'MensClothingStore' within Schema.org types returns 3 results.
    $result = $this->controller->autocomplete(new Request(['q' => 'MensClothingStore']), 'types');
    $this->assertEquals('[{"value":"MensClothingStore","label":"MensClothingStore"}]', $result->getContent());

    // Check searching for 'Thing' within Schema.org properties returns 3 results.
    $result = $this->controller->autocomplete(new Request(['q' => 'Thing']), 'properties');
    $this->assertEquals('[]', $result->getContent());

    // Check searching for 'Male' within Schema.org types returns Gender
    // enumeration values.
    $result = $this->controller->autocomplete(new Request(['q' => 'Male']), 'types');
    $this->assertEquals('[{"value":"Female","label":"Female"},{"value":"Male","label":"Male"}]', $result->getContent());

    // Check searching for 'Male' within Schema.org Thing does NOT
    // return Gender enumeration values.
    $result = $this->controller->autocomplete(new Request(['q' => 'Male']), 'Thing');
    $this->assertEquals('[]', $result->getContent());

    // Check searching Schema.org types can include entity bundle matches.
    $result = $this->controller->autocomplete(new Request(['q' => 'article']), 'Thing', 'node');
    $labels = json_decode($result->getContent(), TRUE);
    $this->assertEquals(['value' => 'article', 'label' => 'article'], reset($labels));
  }

}
