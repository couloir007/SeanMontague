<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field_linkit\FunctionalJavascript\Widget;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\custom_field\Plugin\CustomField\FieldType\LinkTypeInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\custom_field\Traits\CustomFieldTestTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * FunctionalJavascript tests for linkit_url autocomplete selection.
 *
 * @group custom_field
 * @group custom_field_linkit
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[Group('custom_field_linkit')]
#[RunTestsInSeparateProcesses]
class LinkitUrlWidgetTest extends WebDriverTestBase {

  use CustomFieldTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'field_ui',
    'node',
    'linkit',
    'custom_field',
    'custom_field_linkit',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A node available as a Linkit suggestion.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $referenceNode;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Page']);
    $body = FieldConfig::loadByName('node', 'page', 'body');
    $body?->delete();

    $admin = $this->drupalCreateUser([
      'administer content types',
      'administer node fields',
      'administer node form display',
      'access content',
      'create page content',
      'edit any page content',
      'link to any page',
    ]);
    $this->drupalLogin($admin);

    $this->referenceNode = $this->drupalCreateNode([
      'type' => 'page',
      'title' => 'Unique Linkit Target Alpha',
    ]);

    $this->createCustomField(
      'field_test',
      [
        'linkit_test' => [
          'name' => 'linkit_test',
          'type' => 'uri',
        ],
      ],
      [
        'linkit_test' => [
          'label' => 'Linkit test',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'link_type' => LinkTypeInterface::LINK_GENERIC,
          'field_prefix' => 'default',
          'field_prefix_custom' => '',
        ],
      ],
    );

    $this->setFormDisplay('field_test', [
      'linkit_test' => [
        'type' => 'linkit_url',
        'weight' => 0,
        'label' => 'Linkit test',
        'placeholder' => '',
        'linkit_profile' => 'default',
      ],
    ]);
  }

  /**
   * Sets form display for a custom field, including per-subfield widgets.
   *
   * @param string $field_name
   *   Field machine name.
   * @param array $subfield_widgets
   *   Keyed by subfield name with widget settings.
   * @param string $widget_type
   *   Overall widget plugin id.
   * @param array $widget_settings
   *   Overall widget settings.
   * @param string $bundle
   *   Content type bundle.
   * @param string $mode
   *   Form mode.
   */
  protected function setFormDisplay(
    string $field_name,
    array $subfield_widgets,
    string $widget_type = 'custom_stacked',
    array $widget_settings = [],
    string $bundle = 'page',
    string $mode = 'default',
  ): void {
    $form_display = EntityFormDisplay::load("node.{$bundle}.{$mode}");
    if (!$form_display) {
      $form_display = EntityFormDisplay::create([
        'targetEntityType' => 'node',
        'bundle' => $bundle,
        'mode' => $mode,
        'status' => TRUE,
      ]);
    }

    $settings = $widget_settings + [
      'wrapper' => 'details',
      'open' => TRUE,
      'fields' => $subfield_widgets,
    ];

    $form_display->setComponent($field_name, [
      'type' => $widget_type,
      'weight' => 10,
      'region' => 'content',
      'settings' => $settings,
    ])->save();
  }

  /**
   * Tests autocomplete suggestion selection stores an entity URI.
   */
  public function testAutocompleteSelectAndSave(): void {
    $assert = $this->assertSession();
    $session = $this->getSession();
    $this->drupalGet('node/add/page');

    $page = $session->getPage();
    $page->fillField('title[0][value]', 'JS linkit url node');

    $uri_name = 'field_test[0][linkit_test][uri]';
    $field = $assert->fieldExists($uri_name);

    // Linkit listens for key events; setValue + keyDown matches its own tests.
    $field->setValue('Unique Linkit Target');
    $session->getDriver()->keyDown($field->getXpath(), ' ');

    $session->wait(5000, "jQuery('.linkit-result-line.ui-menu-item').length > 0");
    $results = $page->findAll('css', '.linkit-result-line.ui-menu-item');
    $this->assertNotEmpty($results, 'Expected Linkit autocomplete results.');
    $assert->pageTextContains('Unique Linkit Target Alpha');

    $results[0]->click();
    $session->wait(1000);

    $uri_value = $page->findField($uri_name)->getValue();
    $this->assertNotEmpty($uri_value);
    $this->assertStringNotContainsString(
      'Unique Linkit Target',
      $uri_value,
      'URI field should be replaced with the selected path/URL, not the search text.'
    );

    $page->pressButton('Save');
    $assert->waitForText('has been created');

    $node = $this->loadNodeByTitle('JS linkit url node');
    $this->assertLinkitNodeUri(
      $node->get('field_test')->linkit_test,
      $this->referenceNode->id()
    );
  }

  /**
   * Asserts a stored Linkit URI for a node, allowing subdirectory deploys.
   *
   * Linkit may store entity:node/{id} at the docroot, or
   * internal:{base}/node/{id} under a subdirectory (e.g. /web on GitLab CI).
   *
   * @param string $actual
   *   The stored URI value.
   * @param int|string $nid
   *   The referenced node ID.
   */
  protected function assertLinkitNodeUri(string $actual, int|string $nid): void {
    $base_path = rtrim((string) parse_url($this->baseUrl, PHP_URL_PATH), '/');
    $allowed = [
      'entity:node/' . $nid,
      $base_path === ''
        ? 'internal:/node/' . $nid
        : 'internal:' . $base_path . '/node/' . $nid,
    ];
    $this->assertContains(
      $actual,
      $allowed,
      sprintf('Unexpected Linkit URI "%s"; allowed: %s', $actual, implode(', ', $allowed))
    );
  }

}
