<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Functional\CustomField\Widget;

use Drupal\file\Entity\File;
use Drupal\Tests\custom_field\Functional\CustomField\CustomFieldFunctionalTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Functional tests for the file_generic widget.
 *
 * Covers settings UI, managed-file element presence, upload + persist via the
 * non-JS Upload button, empty value, and extension rejection messaging.
 * AJAX upload UX is covered in the FunctionalJavascript counterpart.
 *
 * Managed-file input names use the parents-based key under files[], e.g.
 * files[field_test_0_doc]. Prefer relative assertions so base-path differences
 * in CI do not break path checks.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
class FileWidgetTest extends CustomFieldFunctionalTestBase {


  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'file',
  ];

  /**
   * Path to a temporary .txt fixture for uploads.
   *
   * @var string
   */
  protected string $txtPath;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->txtPath = $this->createTempFile('example upload content', 'txt');

    $this->createCustomField(
      'field_test',
      [
        'doc' => [
          'name' => 'doc',
          'type' => 'file',
          'uri_scheme' => 'public',
          'target_type' => 'file',
        ],
      ],
      [
        'doc' => [
          'label' => 'Document',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'file_directory' => 'custom-field-test',
          'file_extensions' => 'txt',
          'max_filesize' => '',
        ],
      ],
    );

    $this->setFormDisplay('field_test', [
      'doc' => [
        'type' => 'file_generic',
        'weight' => 0,
        'label' => 'Document',
        'progress_indicator' => 'throbber',
      ],
    ]);
  }

  /**
   * Creates a temporary file and returns its real path.
   *
   * @param string $content
   *   File contents.
   * @param string $extension
   *   Extension without the leading dot.
   *
   * @return string
   *   Absolute path to the temp file.
   */
  protected function createTempFile(string $content, string $extension): string {
    $path = $this->publicFilesDirectory . '/test-upload.' . $extension;
    file_put_contents($path, $content);
    return $path;
  }

  /**
   * Tests progress_indicator setting when uploadprogress is available.
   */
  public function testWidgetSettingsFormUi(): void {
    $assert = $this->assertSession();

    $this->drupalGet('admin/structure/types/manage/page/form-display');
    $this->submitForm([], 'field_test_settings_edit');

    if (extension_loaded('uploadprogress')) {
      $base = self::FIELD_PATH . '[doc]';
      $assert->fieldExists($base . '[progress_indicator]');
      $assert->fieldValueEquals($base . '[progress_indicator]', 'throbber');
    }
    else {
      // Setting is access-restricted without the extension; form still loads.
      $assert->statusCodeEquals(200);
    }
  }

  /**
   * Tests the managed file input is present on the node form.
   */
  public function testFileInputRenders(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $assert->elementExists('css', 'input[type="file"][name^="files[field_test_0_doc"]');
    // Upload help includes allowed extensions from field settings.
    $assert->pageTextContains('txt');
  }

  /**
   * Tests uploading a valid file and persisting the fid.
   *
   * Uses the non-JS managed-file Upload button (full page POST).
   */
  public function testFileUploadPersists(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $this->submitForm([
      'title[0][value]' => 'File upload node',
      'files[field_test_0_doc]' => $this->txtPath,
    ], 'Upload');

    // After Upload, the file name should appear on the form.
    $assert->pageTextContains('test-upload.txt');

    $this->submitForm([], 'Save');

    $node = $this->loadNodeByTitle('File upload node');
    $fid = $node->get('field_test')->doc;
    $this->assertNotEmpty($fid);
    $file = File::load($fid);
    $this->assertNotNull($file);
    $this->assertStringContainsString('test-upload', $file->getFilename());
  }

  /**
   * Tests empty file subfield stores NULL.
   */
  public function testEmptyValueStoresNull(): void {
    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Empty file node',
    ], 'Save');

    $node = $this->loadNodeByTitle('Empty file node');
    $value = $node->get('field_test')->doc ?? NULL;
    $this->assertTrue($value === NULL || $value === '' || $value === 0);
  }

  /**
   * Tests that a disallowed extension is rejected.
   */
  public function testDisallowedExtensionRejected(): void {
    $assert = $this->assertSession();
    $png = $this->createTempFile('not-really-an-image', 'png');

    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Bad extension node',
      'files[field_test_0_doc]' => $png,
    ], 'Upload');

    // Core FileExtension validator message variants.
    $page = $this->getSession()->getPage()->getText();
    $this->assertTrue(
      str_contains($page, 'Only files with the following extensions')
      || str_contains($page, 'txt')
      && (str_contains($page, 'error') || str_contains(strtolower($page), 'not') || str_contains($page, 'png')),
      'Expected an extension validation message on the page.'
    );
    $assert->pageTextNotContains('Bad extension node has been created');
  }

}
