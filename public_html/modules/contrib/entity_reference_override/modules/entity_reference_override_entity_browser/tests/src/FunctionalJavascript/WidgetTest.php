<?php

namespace Drupal\Tests\entity_reference_override_entity_browser\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;

/**
 * Tests that the widget works.
 *
 * @group entity_reference_override_entity_browser
 */
class WidgetTest extends JavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['eroeb_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $admin_user = $this->drupalCreateUser([
      'access user profiles',
      'administer nodes',
      'administer entity browsers',
      'create test content',
      'edit any test content',
      'access user_selection entity browser pages'
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests EntityReferenceOverrideEntityBrowser with unlimited cardinality.
   *
   * @see \Drupal\entity_reference_override_entity_browser\Plugin\Field\FieldWidget\EntityReferenceOverrideEntityBrowser
   */
  public function testWidgetMultiValueField() {

    $this->createUser([], 'Aardvark');
    $this->createUser([], 'Badger');
    $this->createUser([], 'Cheetah');

    $this->drupalGet('node/add/test');
    $this->click('details#edit-field-user-reference-multi');

    $this->click('input[name=field_user_reference_multi_entity_browser_entity_browser]');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Get the iframe.
    $this->getSession()->switchToIFrame('entity_browser_iframe_user_selection');
    $page = $this->getSession()->getPage();
    // Select the 3 users.
    $page->checkField('entity_browser_select[user:3]');
    $page->checkField('entity_browser_select[user:4]');
    $page->checkField('entity_browser_select[user:5]');
    $this->click('input#edit-submit');
    $this->getSession()->switchToIFrame();
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->getSession()->getPage()->fillField('title[0][value]', 'A test node');
    $this->click('input[value="Save and publish"]');
    $this->assertSession()->pageTextContains('Aardvark');
    $this->assertSession()->pageTextContains('Badger');
    $this->assertSession()->pageTextContains('Cheetah');

    // Edit the node and set overrides.
    $this->drupalGet('node/1/edit');
    $this->click('details#edit-field-user-reference-multi');

    $page = $this->getSession()->getPage();
    $page->fillField('field_user_reference_multi[current][items][0][override]', 'X-Ray Tetra');
    $page->fillField('field_user_reference_multi[current][items][1][override]', 'Yak');
    $page->fillField('field_user_reference_multi[current][items][2][override]', 'Zebra');
    $this->click('input[value="Save and keep published"]');
    $this->assertSession()->pageTextNotContains('Aardvark');
    $this->assertSession()->pageTextNotContains('Badger');
    $this->assertSession()->pageTextNotContains('Cheetah');
    $this->assertSession()->pageTextContains('X-Ray Tetra');
    $this->assertSession()->pageTextContains('Yak');
    $this->assertSession()->pageTextContains('Zebra');

    // Edit an remove the middle reference.
    $this->drupalGet('node/1/edit');
    $this->click('details#edit-field-user-reference-multi');
    // Remove Badger / Yak user entity.
    $this->click('input#edit-field-user-reference-multi-current-items-1-remove-button');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->assertSession()->fieldValueEquals('field_user_reference_multi[current][items][0][override]', 'X-Ray Tetra');
    // Currently this is broken :(
    $this->assertSession()->fieldValueEquals('field_user_reference_multi[current][items][1][override]', 'Zebra');
  }

  /**
   * Tests EntityReferenceOverrideEntityBrowser with single cardinality.
   *
   * @see \Drupal\entity_reference_override_entity_browser\Plugin\Field\FieldWidget\EntityReferenceOverrideEntityBrowser
   */
  public function testWidgetSingleField() {

    $this->createUser([], 'Aardvark');

    $this->drupalGet('node/add/test');
    $this->click('details#edit-field-user-reference-single');

    $this->click('input[name=field_user_reference_single_entity_browser_entity_browser]');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Get the iframe.
    $this->getSession()->switchToIFrame('entity_browser_iframe_user_selection');
    $page = $this->getSession()->getPage();
    // Select a users.
    $page->checkField('entity_browser_select[user:3]');
    $this->click('input#edit-submit');
    $this->getSession()->switchToIFrame();
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->getSession()->getPage()->fillField('title[0][value]', 'A test node');
    $this->click('input[value="Save and publish"]');
    $this->assertSession()->pageTextContains('Aardvark');

    // Edit the node and set overrides.
    $this->drupalGet('node/1/edit');
    $this->click('details#edit-field-user-reference-single');

    $page = $this->getSession()->getPage();
    $page->fillField('field_user_reference_single[current][items][0][override]', 'Zebra');
    $this->click('input[value="Save and keep published"]');
    $this->assertSession()->pageTextNotContains('Aardvark');
    $this->assertSession()->pageTextContains('Zebra');

    // Edit an remove the middle reference.
    $this->drupalGet('node/1/edit');
    $this->click('details#edit-field-user-reference-single');
    // Remove Aardvark / Zebra user entity.
    $this->click('input#edit-field-user-reference-single-current-items-0-remove-button');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Add it back again. The details element will have a random ID due to AJAX
    // replacement after delete.
    $this->click('div#edit-field-user-reference-single-wrapper details');
    $this->click('input[name=field_user_reference_single_entity_browser_entity_browser]');
    $this->assertSession()->assertWaitOnAjaxRequest();
    // Get the iframe.
    $this->getSession()->switchToIFrame('entity_browser_iframe_user_selection');
    $page = $this->getSession()->getPage();
    // Select a user.
    $page->checkField('entity_browser_select[user:3]');
    $this->click('input#edit-submit');
    $this->getSession()->switchToIFrame();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // The override field shouldn't have any data since this is a new reference.
    // But currently this is broken.
    $this->assertSession()->fieldValueEquals('field_user_reference_single[current][items][0][override]', '');

  }

}
