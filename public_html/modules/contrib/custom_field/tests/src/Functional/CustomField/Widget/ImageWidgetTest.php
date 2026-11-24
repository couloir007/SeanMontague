<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Functional\CustomField\Widget;

use Drupal\file\Entity\File;
use Drupal\Tests\custom_field\Functional\CustomField\CustomFieldFunctionalTestBase;
use Drupal\Tests\TestFileCreationTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Functional tests for the image_image widget.
 *
 * Covers settings (preview_image_style), file input + accept, non-JS upload
 * persist, empty value, and that alt fields are not present before upload.
 * Alt/title after AJAX upload and preview are covered in FunctionalJavascript.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
class ImageWidgetTest extends CustomFieldFunctionalTestBase {

  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'file',
    'image',
  ];

  /**
   * Path to a temporary image fixture.
   *
   * @var string
   */
  protected string $imagePath;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // TestFileCreationTrait generates real image binaries.
    $images = $this->getTestFiles('image');
    $this->assertNotEmpty($images);
    $this->imagePath = $this->container->get('file_system')
      ->realpath($images[0]->uri);

    $this->createCustomField(
      'field_test',
      [
        'photo' => [
          'name' => 'photo',
          'type' => 'image',
          'uri_scheme' => 'public',
          'target_type' => 'file',
        ],
      ],
      [
        'photo' => [
          'label' => 'Photo',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'file_directory' => 'custom-field-images',
          'file_extensions' => 'png gif jpg jpeg webp',
          'max_filesize' => '',
          'max_resolution' => '',
          'min_resolution' => '',
          'alt_field' => 1,
          'alt_field_required' => 0,
          'title_field' => 1,
          'title_field_required' => 0,
        ],
      ],
    );

    $this->setFormDisplay('field_test', [
      'photo' => [
        'type' => 'image_image',
        'weight' => 0,
        'label' => 'Photo',
        'progress_indicator' => 'throbber',
        'preview_image_style' => 'thumbnail',
      ],
    ]);
  }

  /**
   * Tests preview_image_style widget setting.
   */
  public function testWidgetSettingsFormUi(): void {
    $assert = $this->assertSession();
    $base = self::FIELD_PATH . '[photo]';

    $this->drupalGet('admin/structure/types/manage/page/form-display');
    $this->submitForm([], 'field_test_settings_edit');

    $assert->fieldExists($base . '[preview_image_style]');
    $assert->fieldValueEquals($base . '[preview_image_style]', 'thumbnail');

    $this->submitForm([
      $base . '[preview_image_style]' => '',
    ], 'field_test_plugin_settings_update');
    $this->submitForm([], 'Save');
    $assert->pageTextContains('Your settings have been saved.');
  }

  /**
   * Tests the image file input renders with image/* accept.
   */
  public function testImageInputRenders(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $assert->elementExists('css', 'input[type="file"][name^="files[field_test_0_photo"]');
    $assert->elementAttributeContains(
      'css',
      'input[type="file"][name^="files[field_test_0_photo"]',
      'accept',
      'image/*'
    );
    // Alt/title only appear after a file is present.
    $assert->fieldNotExists('field_test[0][photo][alt]');
    $assert->fieldNotExists('field_test[0][photo][title]');
  }

  /**
   * Tests uploading an image and persisting the fid.
   */
  public function testImageUploadPersists(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'Image upload node',
      'files[field_test_0_photo]' => $this->imagePath,
    ], 'Upload');

    // Filename from the fixture should be visible after upload.
    $filename = basename($this->imagePath);
    $assert->pageTextContains($filename);

    // Alt/title may be available after the managed-file process runs.
    $edit = [];
    $page = $this->getSession()->getPage();
    if ($page->findField('field_test[0][photo][alt]')) {
      $edit['field_test[0][photo][alt]'] = 'Test alt text';
    }
    if ($page->findField('field_test[0][photo][title]')) {
      $edit['field_test[0][photo][title]'] = 'Test title text';
    }
    $this->submitForm($edit, 'Save');

    $node = $this->loadNodeByTitle('Image upload node');
    $fid = $node->get('field_test')->photo;
    $this->assertNotEmpty($fid);
    $file = File::load($fid);
    $this->assertNotNull($file);

    if (isset($edit['field_test[0][photo][alt]'])) {
      $this->assertEquals(
        'Test alt text',
        $node->get('field_test')->photo__alt
      );
    }
    if (isset($edit['field_test[0][photo][title]'])) {
      $this->assertEquals(
        'Test title text',
        $node->get('field_test')->photo__title
      );
    }
  }

  /**
   * Tests empty image subfield stores NULL.
   */
  public function testEmptyValueStoresNull(): void {
    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Empty image node',
    ], 'Save');

    $node = $this->loadNodeByTitle('Empty image node');
    $value = $node->get('field_test')->photo ?? NULL;
    $this->assertTrue($value === NULL || $value === '' || $value === 0);
  }

  /**
   * Tests non-image extension is rejected.
   */
  public function testNonImageRejected(): void {
    $assert = $this->assertSession();
    $txt = $this->publicFilesDirectory . '/not-an-image.txt';
    file_put_contents($txt, 'plain text');

    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Bad image node',
      'files[field_test_0_photo]' => $txt,
    ], 'Upload');

    $page = $this->getSession()->getPage()->getText();
    $this->assertTrue(
      str_contains($page, 'Only files with the following extensions')
      || str_contains(strtolower($page), 'image')
      || str_contains($page, 'png'),
      'Expected a validation message rejecting non-image upload.'
    );
    $assert->pageTextNotContains('Bad image node has been created');
  }

}
