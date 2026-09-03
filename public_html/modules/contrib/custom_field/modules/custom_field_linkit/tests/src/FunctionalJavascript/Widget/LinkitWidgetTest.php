<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field_linkit\FunctionalJavascript\Widget;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\custom_field\Plugin\CustomField\FieldType\LinkType;
use Drupal\field\Entity\FieldConfig;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\custom_field\Traits\CustomFieldTestTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * FunctionalJavascript tests for the linkit widget.
 *
 * Covers autocomplete selection, entity meta attributes, and title autofill.
 *
 * @group custom_field
 * @group custom_field_linkit
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[Group('custom_field_linkit')]
#[RunTestsInSeparateProcesses]
class LinkitWidgetTest extends WebDriverTestBase {

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
      'title' => 'Unique Linkit Widget Target',
    ]);

    $link_settings = [
      'check_empty' => FALSE,
      'required' => FALSE,
      'description' => '',
      'link_type' => LinkType::LINK_GENERIC,
      'field_prefix' => 'default',
      'field_prefix_custom' => '',
      'title' => DRUPAL_OPTIONAL,
      'enabled_attributes' => [
        'id' => FALSE,
        'name' => FALSE,
        'target' => FALSE,
        'rel' => FALSE,
        'class' => FALSE,
        'accesskey' => FALSE,
      ],
      'widget_default_open' => LinkType::WIDGET_OPEN_EXPAND_IF_VALUES_SET,
    ];

    $this->createCustomField(
      'field_test',
      [
        'linkit_test' => [
          'name' => 'linkit_test',
          'type' => 'link',
        ],
        'linkit_autofill' => [
          'name' => 'linkit_autofill',
          'type' => 'link',
        ],
      ],
      [
        'linkit_test' => ['label' => 'Linkit test'] + $link_settings,
        'linkit_autofill' => ['label' => 'Linkit autofill'] + $link_settings,
      ],
    );

    $this->setFormDisplay('field_test', [
      'linkit_test' => [
        'type' => 'linkit',
        'weight' => 0,
        'label' => 'Linkit test',
        'placeholder_url' => '',
        'placeholder_title' => '',
        'maxlength' => 255,
        'maxlength_js' => FALSE,
        'linkit_profile' => 'default',
        'linkit_auto_link_text' => FALSE,
      ],
      'linkit_autofill' => [
        'type' => 'linkit',
        'weight' => 1,
        'label' => 'Linkit autofill',
        'placeholder_url' => '',
        'placeholder_title' => '',
        'maxlength' => 255,
        'maxlength_js' => FALSE,
        'linkit_profile' => 'default',
        'linkit_auto_link_text' => TRUE,
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
   * Selects the first Linkit autocomplete suggestion for a field.
   *
   * Uses Linkit's result markup (.linkit-result-line) and keyDown trigger,
   * matching Linkit's own FunctionalJavascript tests.
   *
   * @param string $uri_name
   *   The URI field name.
   * @param string $search
   *   Search string to type.
   */
  protected function selectLinkitSuggestion(string $uri_name, string $search): void {
    $assert = $this->assertSession();
    $session = $this->getSession();
    $page = $session->getPage();

    $field = $assert->fieldExists($uri_name);
    $field->setValue($search);
    $session->getDriver()->keyDown($field->getXpath(), ' ');

    $session->wait(5000, "jQuery('.linkit-result-line.ui-menu-item').length > 0");
    $results = $page->findAll('css', '.linkit-result-line.ui-menu-item');
    $this->assertNotEmpty($results, 'Expected Linkit autocomplete results.');

    $results[0]->click();
    $session->wait(1000);

    $uri_value = $page->findField($uri_name)->getValue();
    $this->assertNotEmpty($uri_value);
    $this->assertStringNotContainsString(
      $search,
      $uri_value,
      'URI field should be replaced with the selected path/URL, not the search text.'
    );
  }

  /**
   * Tests autocomplete selection stores entity URI and linkit meta attributes.
   */
  public function testAutocompleteSelectStoresEntityMeta(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $page = $this->getSession()->getPage();
    $page->fillField('title[0][value]', 'JS linkit meta node');

    $this->selectLinkitSuggestion(
      'field_test[0][linkit_test][uri]',
      'Unique Linkit Widget'
    );

    // Meta fields should be populated client-side after a real selection.
    $entity_type = $page->find('css', 'input[name="field_test[0][linkit_test][attributes][data-entity-type]"]');
    $entity_uuid = $page->find('css', 'input[name="field_test[0][linkit_test][attributes][data-entity-uuid]"]');
    $this->assertNotNull($entity_type);
    $this->assertNotNull($entity_uuid);
    $this->assertEquals('node', $entity_type->getValue());
    $this->assertEquals($this->referenceNode->uuid(), $entity_uuid->getValue());

    $page->fillField('field_test[0][linkit_test][title]', 'Manual title');
    $page->pressButton('Save');
    $assert->waitForText('has been created');

    $node = $this->loadNodeByTitle('JS linkit meta node');
    $this->assertLinkitNodeUri(
      $node->get('field_test')->linkit_test,
      $this->referenceNode->id()
    );
    $this->assertEquals(
      'Manual title',
      $node->get('field_test')->linkit_test__title
    );

    $options = $node->get('field_test')->linkit_test__options;
    $this->assertEquals('node', $options['data-entity-type'] ?? '');
    $this->assertEquals(
      $this->referenceNode->uuid(),
      $options['data-entity-uuid'] ?? ''
    );
  }

  /**
   * Tests linkit_auto_link_text fills the title from the selected entity label.
   */
  public function testTitleAutofillFromSuggestion(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $page = $this->getSession()->getPage();
    $page->fillField('title[0][value]', 'JS linkit autofill node');

    $title_name = 'field_test[0][linkit_autofill][title]';
    $assert->elementAttributeExists(
      'css',
      'input[name="' . $title_name . '"]',
      'data-linkit-widget-title-autofill-enabled'
    );

    $this->selectLinkitSuggestion(
      'field_test[0][linkit_autofill][uri]',
      'Unique Linkit Widget'
    );

    // Autofill should copy the entity label into the title field.
    $assert->fieldValueEquals($title_name, 'Unique Linkit Widget Target');

    $page->pressButton('Save');
    $assert->waitForText('has been created');

    $node = $this->loadNodeByTitle('JS linkit autofill node');
    $this->assertLinkitNodeUri(
      $node->get('field_test')->linkit_autofill,
      $this->referenceNode->id()
    );
    $this->assertEquals(
      'Unique Linkit Widget Target',
      $node->get('field_test')->linkit_autofill__title
    );
  }

  /**
   * Asserts a stored Linkit URI for a node, allowing subdirectory deploys.
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
