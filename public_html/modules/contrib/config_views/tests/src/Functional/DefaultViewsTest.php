<?php

namespace Drupal\Tests\config_views\Functional;

use Drupal\block_content\Entity\BlockContentType;
use Drupal\comment\Entity\CommentType;
use Drupal\contact\Entity\ContactForm;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Url;
use Drupal\image\Entity\ImageStyle;
use Drupal\shortcut\Entity\ShortcutSet;
use Drupal\system\Entity\Menu;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\BrowserTestBase;
use Drupal\views\Entity\View;

/**
 * Test access to default views.
 *
 * @group config_views
 */
class DefaultViewsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'comment',
    'contact',
    'filter',
    'taxonomy',
    'views',
    'path',
    'block',
    'block_content',
    'datetime',
    'config_views',
    'shortcut',
    'image',
    'field_ui',
    'menu_ui',
    'views_ui',
    'config_views_test',
  ];

  /**
   * An administrative user to configure the test environment.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Create and log in an administrative user.
    $this->adminUser = $this->drupalCreateUser([
      'administer blocks',
      'access administration pages',
      'administer menu',
      'administer blocks',
      'administer site configuration',
      'administer filters',
      'administer contact forms',
      'administer image styles',
      'administer shortcuts',
      'administer permissions',
      'administer views',
      'administer content types',
      'administer taxonomy',
      'access taxonomy overview',
      'administer comment types',
      'administer display modes',
    ]);
    $this->drupalLogin($this->adminUser);
    $disable_views = [
      'contact_forms',
      'form_modes',
      'view_modes',
      'user_roles',
      'text_formats',
      'views_list',
    ];

    // Enabled the disabled views.
    foreach ($disable_views as $view_id) {
      $view = View::load($view_id);
      $view->enable()->save();
    }
    $this->container->get('router.builder')->rebuild();

    $this->setUpEntities();
  }

  /**
   * Tests default views.
   */
  public function testViews() {
    // Comment types view.
    $this->drupalGet(Url::fromRoute('view.comment_types.page_1'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Default comment');

    // Contact form types view.
    $this->drupalGet(Url::fromRoute('view.contact_forms.page_1'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Default contact form');

    $this->drupalGet(Url::fromRoute('view.content_types.page_1'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Article');

    $this->drupalGet(Url::fromRoute('view.custom_block_types.page_1'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Default block');

    $this->drupalGet(Url::fromRoute('view.date_formats.page_1'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Default date format');
    $this->assertSession()->pageTextContains('D, j M Y - H:i');

    View::load('form_modes')->enable()->save();
    $this->drupalGet(Url::fromRoute('view.form_modes.page_1'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Register');

    $this->drupalGet(Url::fromRoute('view.image_styles.page_1'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Default image style');

    $this->drupalGet(Url::fromRoute('view.menus.page_1'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Main navigation');

    $this->drupalGet(Url::fromRoute('view.shortcuts.page_1'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Default shortcut set');

    $this->drupalGet(Url::fromRoute('view.taxonomy.page_1'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Tags');

    $this->drupalGet(Url::fromRoute('view.text_formats.page_1'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Plain text');

    $this->drupalGet(Url::fromRoute('view.user_roles.page_1'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Anonymous user');
    $this->assertSession()->pageTextContains('Authenticated user');

    $this->drupalGet(Url::fromRoute('view.view_modes.page_1'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Full content');
    $this->assertSession()->pageTextContains('Full comment');
    $this->assertSession()->pageTextContains('Taxonomy term page');

    $this->drupalGet(Url::fromRoute('view.views_list.page_1'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Comments');
    $this->assertSession()->pageTextContains('Recent comments');
  }

  /**
   * Tests the boolean filter.
   */
  public function testBooleanFilter() {
    // The footer contains the disabled Views. Double check that boolean is
    // working correctly. The versions of these Views provided by this module
    // are with 'status' of 'false' in their configuration.
    View::load('views_list')->enable()->save();
    drupal_flush_all_caches();
    $this->drupalGet(Url::fromRoute('view.views_list.page_1'));
    $this->assertSession()->elementTextNotContains('css', 'footer table', 'Text formats and editors');
    $this->assertSession()->elementTextContains('css', 'footer table', 'Glossary');

    View::load('text_formats')->disable()->save();
    drupal_flush_all_caches();
    $this->drupalGet(Url::fromRoute('view.views_list.page_1'));
    $this->assertSession()->elementTextContains('css', 'footer table', 'Text formats and editors');
    $this->assertSession()->elementTextContains('css', 'footer table', 'Glossary');
  }

  /**
   * Tests the pager.
   */
  public function testPager() {
    $this->drupalGet(Url::fromRoute('view.date_formats_pager.page_1'));

    // Check for pager on the first page and that we have the default date
    // format on that page.
    $this->assertSession()->elementsCount('css', '.pager__item', 5);
    $this->assertSession()->pageTextContains('Default date format');

    // Switch to page two and expect to see different date formats.
    $this->drupalGet(Url::fromRoute('view.date_formats_pager.page_1', [], [
      'query' => [
        'page' => 1,
      ],
    ]));
    $this->assertSession()->pageTextNotContains('Default date format');
    $this->assertSession()->pageTextContains('HTML Time');
  }

  /**
   * Setup sample entities.
   */
  protected function setupEntities() {
    // Create sample config entities.
    CommentType::create([
      'id' => 'comment',
      'label' => 'Default comment',
      'target_entity_type_id' => 'node',
    ])->save();

    ContactForm::create([
      'id' => 'contact_message',
      'label' => 'Default contact form',
    ])->save();

    BlockContentType::create([
      'id' => 'default_block',
      'label' => 'Default block',
      'description' => "Provides a default block type.",
    ])->save();

    DateFormat::create([
      'id' => 'default_datetime',
      'label' => 'Default date format',
      'pattern' => 'Y-m-d',
    ])->save();

    Vocabulary::create([
      'name' => 'Tags',
      'vid' => 'tags',
    ])->save();

    ImageStyle::create([
      'name' => 'default_image_style',
      'label' => 'Default image style',
    ])->save();

    Menu::create([
      'id' => 'default_menu',
      'label' => 'Default menu',
    ])->save();

    ShortcutSet::create([
      'id' => 'default_shortcut_set',
      'label' => 'Default shortcut set',
    ])->save();
  }

}
