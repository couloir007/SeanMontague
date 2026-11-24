<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests synchronization of untranslatable custom subfields.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
class CustomFieldTranslationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_translation',
    'custom_field',
    'field',
    'file',
    'filter',
    'image',
    'language',
    'link',
    'system',
    'taxonomy',
    'text',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('taxonomy_term');
    $this->installConfig(['system', 'field', 'language', 'taxonomy']);
    ConfigurableLanguage::createFromLangcode('fr')->save();
    Vocabulary::create([
      'vid' => 'ticket_type',
      'name' => 'Ticket type',
    ])->save();
    $this->container->get('content_translation.manager')->setEnabled('taxonomy_term', 'ticket_type', TRUE);

    FieldStorageConfig::create([
      'field_name' => 'field_ticket_prices',
      'entity_type' => 'taxonomy_term',
      'type' => 'custom',
      'cardinality' => -1,
      'translatable' => TRUE,
      'settings' => [
        'columns' => [
          'label' => ['name' => 'label', 'type' => 'string', 'length' => 255],
          'price' => ['name' => 'price', 'type' => 'float'],
        ],
      ],
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_ticket_prices',
      'entity_type' => 'taxonomy_term',
      'bundle' => 'ticket_type',
      'translatable' => TRUE,
      'settings' => [
        'field_settings' => [
          'label' => ['translatable' => TRUE],
          'price' => ['translatable' => FALSE],
        ],
      ],
    ])->save();
  }

  /**
   * Tests zero prices are copied when creating and editing a translation.
   */
  public function testUntranslatableZeroPrices(): void {
    $prices = [0, '0', 0.0, '00', 12.5];
    $term = Term::create([
      'vid' => 'ticket_type',
      'name' => 'Tickets',
      'langcode' => 'en',
      'field_ticket_prices' => array_map(
        static fn ($price) => ['label' => 'Original', 'price' => $price],
        $prices,
      ),
    ]);
    $term->save();

    $translation = $term->addTranslation('fr', ['name' => 'Billets']);
    $translation->set('field_ticket_prices', array_fill(0, count($prices), ['label' => 'Translated']));
    $this->assertTrue($translation->isTranslatable());
    $this->assertTrue($translation->getFieldDefinition('field_ticket_prices')->isTranslatable());
    $this->assertCount(2, $translation->getTranslationLanguages());
    $this->assertFalse($translation->isDefaultTranslation());
    $this->assertSame(0, $translation->getUntranslated()->field_ticket_prices[0]->price);
    $translation->save();

    $storage = $this->container->get('entity_type.manager')->getStorage('taxonomy_term');
    foreach (['create', 'update'] as $operation) {
      $storage->resetCache([$term->id()]);
      $term = $storage->load($term->id());
      $translation = $term->getTranslation('fr');
      foreach ($prices as $delta => $price) {
        $this->assertSame((float) $price, (float) $term->field_ticket_prices[$delta]->price);
        $this->assertNotNull($translation->field_ticket_prices[$delta]->price, $operation);
        $this->assertSame((float) $price, (float) $translation->field_ticket_prices[$delta]->price, $operation);
        $this->assertSame('Translated', $translation->field_ticket_prices[$delta]->label);
      }
      if ($operation === 'create') {
        $translation->set('field_ticket_prices', array_fill(0, count($prices), ['label' => 'Translated']));
        $translation->save();
      }
    }
  }

}
