<?php

namespace Drupal\Tests\embedded_content\FunctionalJavascript;

use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\ckeditor5\Traits\CKEditor5TestTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\user\RoleInterface;

/**
 * Defines tests for the ckeditor5 button and javascript functionality.
 *
 * @group embedded_content
 */
class EmbeddedContentTest extends WebDriverTestBase
{

    use CKEditor5TestTrait;

    use NodeCreationTrait;

    /**
     * {@inheritdoc}
     */
    protected static $modules = [
    'node',
    'ckeditor5',
    'embedded_content',
    ];

    /**
     * {@inheritdoc}
     */
    protected $defaultTheme = 'stark';

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->drupalCreateContentType(['type' => 'page']);
        FilterFormat::create(
            [
            'format' => 'test',
            'name' => 'Ckeditor 5 with embedded content',
            'roles' => [RoleInterface::AUTHENTICATED_ID],
            'filters' => [
              'filter_html' => [
                'id' => 'filter_html',
                'status' => true,
                'weight' => -10,
                'settings' => [
                  'allowed_html' => '<br> <p> <embedded-content data-plugin-config data-plugin-id data-button-id>',
                  'filter_html_help' => true,
                  'filter_html_nofollow' => false,
                ],
              ],
              'embedded_content' => [
                'id' => 'embedded_content',
                'provider' => 'embedded_content',
                'status' => true,
                'weight' => 100,
              ],
            ],
            ]
        )->save();
        Editor::create(
            [
            'format' => 'test',
            'editor' => 'ckeditor5',
            'settings' => [
              'toolbar' => [
                'items' => ['embeddedContent__default', 'sourceEditing'],
              ],
            ],
            ]
        )->save();

        $this->drupalLogin(
            $this->drupalCreateUser(
                [
                'create page content',
                'edit own page content',
                'access content',
                'use default embedded content button',
                'use text format test',
                ]
            )
        );

    }

    /**
     * Tests if CKEditor 5 tooltips can be interacted with in dialogs.
     */
    public function testCkeditor5EmbeddedContent()
    {

        $page = $this->getSession()->getPage();
        $assert_session = $this->assertSession();

        // Add a node with text rendered via the Plain Text format.
        $this->drupalGet('node/add');

        $this->waitForEditor();
        // Ensure the editor is loaded.
        $this->click('.ck-content');

        $this->assertEditorButtonEnabled('Default');
        $this->click('.ck-button_embedded-content__default');
        $assert_session->waitForText('No embedded content plugins were found. Enable the examples module to see some examples or revise if the filter conditions in the button configration are met.');
        $this->container->get('module_installer')
            ->install(['embedded_content_test'], true);

        // Add a node with text rendered via the Plain Text format.
        $this->drupalGet('node/add');

        $this->waitForEditor();
        // Ensure the editor is loaded.
        $this->click('.ck-content');

        $this->assertEditorButtonEnabled('Default');
        $this->click('.ck-button_embedded-content__default');
        $assert_session->waitForElement('css', '.embedded-content-dialog-form');
        $page->selectFieldOption('config[plugin_id]', 'Shape');

        $assert_session->waitForElement('css', '[data-drupal-selector="edit-config-plugin-config-shape"]');

        $page->selectFieldOption('config[plugin_config][shape]', 'rectangle');

        $this->click('.ui-dialog-buttonset button');
        $node = $assert_session->waitForElement('css', '.embedded-content-preview > div');
        $this->assertEquals('<svg width="400" height="110"><rect width="300" height="100" style="fill:rgb(0,0,255);stroke-width:3;stroke:rgb(0,0,0)"></rect></svg>', $node->getHtml());

        // Test if it is possible to edit a selected embedded content.
        $this->click('figure.ck-widget');

        $this->click('.ck-button_embedded-content__edit');

        $element = $assert_session->waitForElement('css', '[data-drupal-selector="edit-config-plugin-config-shape"]');

        $this->assertEquals('rectangle', $element->getValue());
        $page->selectFieldOption('config[plugin_id]', 'Color');
        $assert_session->waitForElement('css', '[data-drupal-selector="edit-config-plugin-config-color"]');
        $page->selectFieldOption('config[plugin_config][color]', 'red');
        $this->click('.ui-dialog-buttonset button');
        $assert_session->waitForElement('css', '.embedded-content-preview [style="background:green;width:20px;height:20px;display:block;border-radius: 10px"]');

        // Test if it is possible to edit a selected embedded content.
        $this->click('figure.ck-widget');
        $this->click('.ck-button_embedded-content__edit');
        $assert_session->waitForElement('css', '[data-drupal-selector="edit-config-plugin-config-color"]');

    }

}
