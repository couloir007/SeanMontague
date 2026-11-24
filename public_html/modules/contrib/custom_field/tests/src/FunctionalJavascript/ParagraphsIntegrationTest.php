<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\FunctionalJavascript;

use Drupal\field\Entity\FieldConfig;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\custom_field\Traits\CustomFieldTestTrait;
use Drupal\Tests\custom_field\Traits\ParagraphsIntegrationTestTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Custom field subfields inside paragraphs, through the real AJAX flows.
 *
 * Covers what the non-JavaScript counterpart cannot: the viewfield display
 * options arriving by AJAX after a view is chosen, the Paragraphs "Add" button
 * rebuilding a form that already holds values, and the image upload. Every
 * subfield type whose data type writes extra properties is filled in, so those
 * extra AJAX rebuilds are exercised against all of them at once.
 *
 * Each test method reinstalls Drupal, so the scenarios are grouped into two.
 *
 * @group custom_field
 * @group custom_field_viewfield
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[Group('custom_field_viewfield')]
#[RunTestsInSeparateProcesses]
class ParagraphsIntegrationTest extends WebDriverTestBase {

  use CustomFieldTestTrait;
  use ParagraphsIntegrationTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'field_ui',
    'node',
    'file',
    'image',
    'media',
    'media_library',
    'views',
    'entity_reference_revisions',
    'paragraphs',
    'custom_field',
    'custom_field_media',
    'custom_field_viewfield',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Media ids selected per paragraph delta.
   *
   * @var array<int, string>
   */
  protected array $selectedMedia = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Page']);
    FieldConfig::loadByName('node', 'page', 'body')?->delete();

    $this->drupalLogin($this->drupalCreateUser([
      'administer content types',
      'administer node fields',
      'administer node form display',
      'access content',
      'create page content',
      'edit any page content',
    ]));

    $this->setUpParagraphsIntegration(TRUE);
  }

  /**
   * Adds a card paragraph through the Paragraphs AJAX button.
   *
   * @param int $delta
   *   The delta the new paragraph is expected to occupy.
   */
  protected function addCardViaAjax(int $delta): void {
    $this->getSession()->getPage()->pressButton('Add Card');
    $this->assertSession()->assertWaitOnAjaxRequest();
    // The XHR counter alone is not a barrier for the second and later AJAX
    // calls on a page, so wait for the subform this click created.
    $this->assertNotNull(
      $this->assertSession()->waitForField($this->cardElement($delta, 'txt')),
      sprintf('Subform for paragraph %d rendered.', $delta)
    );
  }

  /**
   * Fills every subfield of one paragraph delta, driving the AJAX flows.
   *
   * @param int $delta
   *   The paragraph delta.
   * @param string $view
   *   The view to select.
   * @param string $display
   *   The display to select once the AJAX options arrive.
   */
  protected function fillCard(int $delta, string $view, string $display): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->selectMedia($delta);

    $page->fillField($this->cardElement($delta, 'txt'), 'Text ' . $delta);
    $page->fillField($this->cardElement($delta, 'lnk', '[uri]'), 'https://example.com/' . $delta);
    $page->fillField($this->cardElement($delta, 'lnk', '[title]'), 'Link ' . $delta);

    // The link attributes sit in a details element that starts collapsed.
    $target = $this->cardElement($delta, 'lnk', '[options][attributes][target]');
    $target_field = $page->find('css', '[name="' . $target . '"]');
    $this->assertNotNull($target_field, 'Link target field exists.');
    if (!$target_field->isVisible()) {
      $details = $page->find('xpath', '//*[@name="' . $target . '"]/ancestor::details[1]/summary');
      $this->assertNotNull($details, 'Attributes details summary exists.');
      $details->click();
    }
    $page->selectFieldOption($target, '_blank');

    // Native date, time and datetime-local controls interpret typed text in
    // the browser's locale, so assign their values directly instead.
    $this->setDateValue($this->cardElement($delta, 'dt', '[value][date]'), '2026-08-15');
    $this->setDateValue($this->cardElement($delta, 'dt', '[value][time]'), '09:00:00');
    $page->selectFieldOption($this->cardElement($delta, 'dt', '[timezone]'), 'America/New_York');

    $this->setDateValue($this->cardElement($delta, 'dr', '[value][date]'), '2026-08-15T09:00');
    $this->setDateValue($this->cardElement($delta, 'dr', '[end_value][date]'), '2026-08-16T17:00');

    $this->setDateValue($this->cardElement($delta, 'tr', '[value]'), '09:00:00');
    $this->setDateValue($this->cardElement($delta, 'tr', '[end_value]'), '17:00:00');

    $this->uploadCardImage($delta);
    $page->fillField($this->cardElement($delta, 'img', '[alt]'), 'Alt ' . $delta);
    $page->fillField($this->cardElement($delta, 'img', '[title]'), 'Title ' . $delta);

    // Selecting the view fetches the display options over AJAX.
    $page->selectFieldOption($this->cardElement($delta, 'vf', '[target_id]'), $view);
    $assert->assertWaitOnAjaxRequest();
    $this->assertNotNull(
      $assert->waitForElement('css', 'select[name="' . $this->cardElement($delta, 'vf', '[display_id]') . '"]'),
      sprintf('Display options loaded for paragraph %d.', $delta)
    );
    $page->selectFieldOption($this->cardElement($delta, 'vf', '[display_id]'), $display);

    // Advanced options sit in a details element that #states keeps hidden
    // until a view is chosen, and that starts collapsed.
    $args = $this->cardElement($delta, 'vf', '[view_options][arguments]');
    $args_field = $page->find('css', '[name="' . $args . '"]');
    $this->assertNotNull($args_field, 'Viewfield arguments field exists.');
    if (!$args_field->isVisible()) {
      $summary = $page->find('xpath', '//*[@name="' . $args . '"]/ancestor::details[1]/summary');
      $this->assertNotNull($summary, 'Advanced options summary exists.');
      $summary->click();
    }
    $page->fillField($args, 'a/b');
    $page->fillField($this->cardElement($delta, 'vf', '[view_options][items_to_display]'), '5');
  }

  /**
   * Selects a media item through the media library modal.
   *
   * The modal is its own AJAX dialog rendered inside the paragraph subform,
   * which is the part no other test in the suite covers.
   *
   * @param int $delta
   *   The paragraph delta.
   */
  protected function selectMedia(int $delta): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Each delta selects a different media so a value leaking between
    // paragraphs cannot pass unnoticed.
    $media_id = (string) $this->mediaIds[$delta % count($this->mediaIds)];

    $button = $page->find('css', '[name="' . $this->cardMediaOpenButton($delta) . '"]');
    $this->assertNotNull($button, sprintf('Add media button for paragraph %d exists.', $delta));
    $button->click();
    $assert->assertWaitOnAjaxRequest();
    $modal = $assert->waitForElement('css', '.media-library-widget-modal');
    $this->assertNotNull($modal, 'Media library modal opened.');

    // Core keys each row checkbox by media id, so address the wanted item
    // explicitly rather than taking whichever row happens to come first.
    $checkbox = $modal->find('css', '[name="media_library_select_form[' . $media_id . ']"]');
    $this->assertNotNull($checkbox, sprintf('Media %s is offered in the library.', $media_id));
    $checkbox->check();

    $buttons = $assert->waitForElement('css', '.ui-dialog-buttonset');
    $this->assertNotNull($buttons, 'Media library dialog buttons rendered.');
    $buttons->pressButton('Insert selected');
    $assert->assertWaitOnAjaxRequest();

    // The widget must end up holding the media that was ticked in the library.
    // The expectation comes from the fixture, not from the widget, so a widget
    // that inserted a wrong but consistent id would fail here.
    $target = $this->cardElement($delta, 'media', '[selection][0][target_id]');
    $this->assertNotNull(
      $assert->waitForElement('css', '[name="' . $target . '"]'),
      sprintf('Media selection recorded for paragraph %d.', $delta)
    );
    $assert->hiddenFieldValueEquals($target, $media_id);
    $this->selectedMedia[$delta] = $media_id;
  }

  /**
   * Uploads the fixture image into one paragraph's image subfield.
   *
   * Attaching the file fires a change event that file.js turns into a click on
   * the element's own Upload button. That binding only exists once Drupal's
   * behaviors have run over the subform, and every other AJAX on the page
   * re-renders the widget, so the change can also land on an element that is
   * already on its way out. Neither shows up as a failed request:
   * assertWaitOnAjaxRequest() is satisfied by the media library request that
   * ran a moment earlier, so only the alt field the upload adds proves it
   * happened. Press the Upload button the way file.js does if it does not turn
   * up.
   *
   * @param int $delta
   *   The paragraph delta.
   */
  protected function uploadCardImage(int $delta): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();
    $alt = $this->cardElement($delta, 'img', '[alt]');
    $selector = 'input[type="file"][name="' . $this->cardImageUpload($delta) . '"]';

    $upload = $page->find('css', $selector);
    $this->assertNotNull($upload, sprintf('Image upload field exists for paragraph %d.', $delta));
    $upload->attachFile($this->fixtureImagePath());

    // Saving the file rebuilds the whole node form, which grows with every
    // paragraph already on the page, so allow well beyond the ten second
    // default before concluding that nothing was submitted.
    if ($assert->waitForField($alt, 30000) !== NULL) {
      return;
    }

    // Re-attach first: if the element the file went to has been replaced since,
    // the button would submit an empty upload.
    $upload = $page->find('css', $selector);
    $this->assertNotNull($upload, sprintf('Image upload field still exists for paragraph %d.', $delta));
    $upload->attachFile($this->fixtureImagePath());
    // The button carries the js-hide class, so it cannot be clicked. Core's own
    // auto-upload triggers it with a jQuery mousedown, the event its AJAX
    // submit is bound to.
    $this->getSession()->executeScript(sprintf(
      'jQuery(%s).trigger("mousedown");',
      json_encode('[name="' . $this->cardImageUploadButton($delta) . '"]', JSON_THROW_ON_ERROR)
    ));
    $this->assertNotNull(
      $assert->waitForField($alt, 30000),
      sprintf('Alt field appeared after uploading the image of paragraph %d.', $delta)
    );
  }

  /**
   * Sets a native date, time or datetime-local input's value directly.
   *
   * WebDriver types into these controls character by character and the browser
   * reinterprets the text in its own locale, which silently produces an
   * invalid value and blocks form submission through HTML5 validation.
   *
   * @param string $name
   *   The element name attribute.
   * @param string $value
   *   The value in the element's own format.
   */
  protected function setDateValue(string $name, string $value): void {
    $this->assertNotNull(
      $this->getSession()->getPage()->find('css', '[name="' . $name . '"]'),
      sprintf('Date element %s exists.', $name)
    );
    $script = sprintf(
      'var el = document.querySelector(%s);'
      . 'el.value = %s;'
      . "el.dispatchEvent(new Event('input', {bubbles: true}));"
      . "el.dispatchEvent(new Event('change', {bubbles: true}));",
      json_encode('[name="' . $name . '"]'),
      json_encode($value)
    );
    $this->getSession()->executeScript($script);
    $this->assertSession()->fieldValueEquals($name, $value);
  }

  /**
   * The expected stored values for a filled paragraph delta.
   *
   * @param int $delta
   *   The paragraph delta.
   * @param string $view
   *   The expected view id.
   * @param string $display
   *   The expected display id.
   *
   * @return array<string, mixed>
   *   Expected property values.
   */
  protected function expectedCard(int $delta, string $view, string $display): array {
    return [
      'txt' => 'Text ' . $delta,
      'lnk' => 'https://example.com/' . $delta,
      'lnk__title' => 'Link ' . $delta,
      'dt__timezone' => 'America/New_York',
      'img__alt' => 'Alt ' . $delta,
      'img__title' => 'Title ' . $delta,
      'vf' => $view,
      'vf__display' => $display,
      'vf__arguments' => 'a/b',
      'vf__items' => '5',
      'media' => $this->selectedMedia[$delta],
    ];
  }

  /**
   * One paragraph, filled through AJAX, keeps every extra property.
   */
  public function testAjaxFlowsPersistAllExtraProperties(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet('node/add/page');
    $this->addCardViaAjax(0);
    $page->fillField('title[0][value]', 'Ajax card');
    $this->fillCard(0, 'view_one', 'page_1');

    $this->submitForm([], 'Save');
    $assert->waitForText('Ajax card');
    $assert->pageTextContains('has been created');

    $node = $this->loadNodeByTitle('Ajax card');
    $this->assertCardCount((int) $node->id(), 1);
    $this->assertStoredCard((int) $node->id(), 0, $this->expectedCard(0, 'view_one', 'page_1'));

    $item = $this->reloadCardItem((int) $node->id(), 0);
    $this->assertSame(['target' => '_blank'], $item->lnk__options['attributes'] ?? NULL);
    $this->assertNotEmpty($item->dr__end, 'Date range end stored.');
    $this->assertNotEmpty($item->tr__end, 'Time range end stored.');
    $this->assertNotEmpty($item->img__width, 'Image width stored.');

    // The re-edit form offers the stored values as defaults.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $assert->fieldValueEquals($this->cardElement(0, 'lnk', '[title]'), 'Link 0');
    $assert->fieldValueEquals($this->cardElement(0, 'img', '[alt]'), 'Alt 0');
    $assert->fieldValueEquals($this->cardElement(0, 'vf', '[target_id]'), 'view_one');
    $assert->fieldValueEquals($this->cardElement(0, 'vf', '[display_id]'), 'page_1');
    $assert->hiddenFieldValueEquals(
      $this->cardElement(0, 'media', '[selection][0][target_id]'),
      $this->selectedMedia[0]
    );
  }

  /**
   * Adding a second paragraph must not disturb the first one's values.
   */
  public function testAddingSecondParagraphKeepsFirstValues(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet('node/add/page');
    $this->addCardViaAjax(0);
    $page->fillField('title[0][value]', 'Two ajax cards');
    $this->fillCard(0, 'view_one', 'page_1');

    // Another AJAX rebuild over the already-filled first paragraph.
    $this->addCardViaAjax(1);
    $assert->fieldValueEquals($this->cardElement(0, 'vf', '[display_id]'), 'page_1');
    $assert->fieldValueEquals($this->cardElement(0, 'lnk', '[title]'), 'Link 0');
    $assert->fieldValueEquals($this->cardElement(0, 'img', '[alt]'), 'Alt 0');

    $this->fillCard(1, 'view_two', 'default');

    // Filling the second paragraph must not disturb the first one's media.
    $assert->hiddenFieldValueEquals(
      $this->cardElement(0, 'media', '[selection][0][target_id]'),
      $this->selectedMedia[0]
    );
    $this->assertNotSame(
      $this->selectedMedia[0],
      $this->selectedMedia[1],
      'The two paragraphs reference different media.'
    );

    $this->submitForm([], 'Save');
    $assert->waitForText('Two ajax cards');
    $assert->pageTextContains('has been created');

    $node = $this->loadNodeByTitle('Two ajax cards');
    $this->assertCardCount((int) $node->id(), 2);
    $this->assertStoredCard((int) $node->id(), 0, $this->expectedCard(0, 'view_one', 'page_1'));
    $this->assertStoredCard((int) $node->id(), 1, $this->expectedCard(1, 'view_two', 'default'));
  }

}
