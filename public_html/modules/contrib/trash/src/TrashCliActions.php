<?php

declare(strict_types=1);

namespace Drupal\trash;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\views\Entity\View;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Shared logic for the Trash CLI commands (Drush and the core `dr` CLI).
 */
class TrashCliActions {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected TrashManagerInterface $trashManager,
    protected TrashViewBuilder $trashViewBuilder,
  ) {}

  /**
   * Gets the IDs of all trash-enabled entity types.
   */
  public function getEnabledEntityTypes(): array {
    return $this->trashManager->getEnabledEntityTypes();
  }

  /**
   * Checks whether the given ID is a trash-enabled entity type.
   */
  public function isTrashEnabledType(?string $entity_type_id): bool {
    return $entity_type_id !== NULL && in_array($entity_type_id, $this->getEnabledEntityTypes(), TRUE);
  }

  /**
   * Builds the error message for an invalid entity type ID argument.
   */
  public function invalidEntityTypeMessage(?string $entity_type_id): string {
    $enabled = $this->getEnabledEntityTypes();
    return (string) $this->t('@entity_type_id is not a Trash-enabled entity type. Enabled entity types: @entity_type_ids', [
      '@entity_type_id' => $entity_type_id ?? '(none given)',
      '@entity_type_ids' => $enabled ? implode(', ', $enabled) : '(none)',
    ]);
  }

  /**
   * Builds the confirmation question for a restore or purge operation.
   */
  public function confirmationQuestion(string $operation, ?string $entity_type_id, ?array $entity_ids, bool $all): string {
    if ($all) {
      return (string) $this->t('Are you sure you want to @operation all data for all entity types?', [
        '@operation' => $operation,
      ]);
    }
    if (!$entity_ids) {
      return (string) $this->t('Are you sure you want to @operation all data for the @entity_type_id entity type?', [
        '@operation' => $operation,
        '@entity_type_id' => $entity_type_id,
      ]);
    }
    return (string) $this->t('Are you sure you want to @operation @entity_type_id @entity_ids?', [
      '@operation' => $operation,
      '@entity_type_id' => $entity_type_id,
      '@entity_ids' => implode(', ', $entity_ids),
    ]);
  }

  /**
   * Returns the labels of the given entity types, keyed by entity type ID.
   */
  public function getEntityTypeLabels(array $entity_type_ids): array {
    $labels = [];
    foreach ($entity_type_ids as $entity_type_id) {
      $labels[$entity_type_id] = (string) $this->entityTypeManager->getDefinition($entity_type_id)->getLabel();
    }
    return $labels;
  }

  /**
   * Returns the trash-enabled entity types that a View can be exported for.
   *
   * Entity types without Views integration get the fallback table on the Trash
   * overview page, so there is no View to export for them.
   *
   * @see \Drupal\trash\Controller\TrashController::listing()
   */
  public function getExportableEntityTypes(): array {
    return array_values(array_filter(
      $this->getEnabledEntityTypes(),
      fn (string $entity_type_id): bool => (bool) $this->entityTypeManager->getDefinition($entity_type_id, FALSE)?->hasHandlerClass('views_data'),
    ));
  }

  /**
   * Returns the message shown when an entity type has no View to export.
   */
  public function notExportableMessage(string $entity_type_id): string {
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id, FALSE);
    return (string) $this->t('The @entity_type entity type has no Views integration, so its Trash overview page uses the fallback table and there is no view to export.', [
      '@entity_type' => $entity_type ? $entity_type->getLabel() : $entity_type_id,
    ]);
  }

  /**
   * Returns the message shown when no entity type has a View to export.
   */
  public function noExportableEntityTypesMessage(): string {
    return (string) $this->t('None of the Trash-enabled entity types have Views integration, so there are no Trash overview page views to export.');
  }

  /**
   * Builds the confirmation question for the export-views operation.
   */
  public function exportConfirmationQuestion(bool $all, ?string $entity_type_id): string {
    if ($all) {
      return (string) $this->t('Are you sure you want to export the Trash overview page view for all Trash entity types?');
    }
    return (string) $this->t('Are you sure you want to export the Trash overview page views for the @entity_type entity type?', [
      '@entity_type' => $this->entityTypeManager->getDefinition($entity_type_id)->getLabel(),
    ]);
  }

  /**
   * Exports the Trash overview page views for the given entity types.
   */
  public function exportViews(SymfonyStyle $io, array $entity_type_ids, bool $overwrite): void {
    foreach ($entity_type_ids as $entity_type_id) {
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
      // Saving a View built on a table without Views data throws while its
      // dependencies are calculated, so there is nothing to export here.
      if (!$entity_type->hasHandlerClass('views_data')) {
        $io->comment($this->notExportableMessage($entity_type_id));
        continue;
      }

      $trash_view = View::load('trash_' . $entity_type_id);
      if (!$trash_view || $overwrite) {
        $view_executable = $this->trashViewBuilder->buildView($entity_type, TRUE);
        if ($overwrite && $trash_view) {
          $trash_view->delete();
        }
        $view_executable->save();
        $io->info((string) $this->t('Exported the @entity_type Trash overview page view.', [
          '@entity_type' => $entity_type->getLabel(),
        ]));
      }
      else {
        $io->comment((string) $this->t('Skipped export of the Trash overview page view for @entity_type because it already exists.', [
          '@entity_type' => $entity_type->getLabel(),
        ]));
      }
    }
  }

  /**
   * Returns the message shown when no entity types are trash-enabled.
   */
  public function noEntityTypesEnabledMessage(): string {
    return (string) $this->t('No entity types enabled.');
  }

  /**
   * Returns the prompt for selecting the entity type to restore or purge.
   */
  public function selectEntityTypeQuestion(string $operation): string {
    return (string) $this->t('Select the entity type you want to @operation.', [
      '@operation' => $operation,
    ]);
  }

  /**
   * Returns the prompt for selecting a valid entity type to export.
   */
  public function selectExportEntityTypeQuestion(): string {
    return (string) $this->t('Please specify a valid entity type');
  }

  /**
   * Returns the success message for a completed restore or purge operation.
   */
  public function resultMessage(string $operation, int $count): string {
    assert(in_array($operation, ['restore', 'purge'], TRUE));
    if ($operation === 'restore') {
      return $count > 0
        ? (string) $this->t('Restored @count trashed entities.', ['@count' => $count])
        : (string) $this->t('No trashed entities to restore.');
    }
    return $count > 0
      ? (string) $this->t('Purged @count trashed entities.', ['@count' => $count])
      : (string) $this->t('No trashed entities to purge.');
  }

  /**
   * Performs a restore or purge operation on the given arguments.
   *
   * @param \Symfony\Component\Console\Style\SymfonyStyle $io
   *   The I/O style used for progress reporting.
   * @param string $operation
   *   The operation to perform, either 'restore' or 'purge'.
   * @param array $entity_type_ids
   *   An array of entity type IDs.
   * @param array|null $entity_ids
   *   An array of entity IDs.
   *
   * @return int
   *   Returns the number of entities on which the operation was performed.
   */
  public function performOperation(SymfonyStyle $io, string $operation, array $entity_type_ids, ?array $entity_ids = NULL): int {
    assert(in_array($operation, ['restore', 'purge'], TRUE));

    $count = 0;
    foreach ($entity_type_ids as $entity_type_id) {
      $storage = $this->entityTypeManager->getStorage($entity_type_id);
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->addMetaData('trash', 'inactive')
        ->exists('deleted');

      if (count($entity_type_ids) === 1 && $entity_ids) {
        $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
        $query->condition($entity_type->getKey('id'), $entity_ids, 'IN');
      }

      $ids = $query->execute();
      if ($ids === []) {
        continue;
      }

      $io->progressStart(count($ids));
      $chunkSize = Settings::get('entity_update_batch_size', 50);

      foreach (array_chunk($ids, $chunkSize) as $chunk) {
        $this->trashManager->executeInTrashContext('ignore', function () use (&$count, $io, $storage, $chunk, $operation) {
          $entities = $storage->loadMultiple($chunk);
          if ($operation === 'restore') {
            $storage->restoreFromTrash($entities);
          }
          elseif ($operation === 'purge') {
            $storage->delete($entities);
          }
          $count += count($entities);
          $io->progressAdvance(count($chunk));
        });
      }

      $io->progressFinish();
    }

    return $count;
  }

}
