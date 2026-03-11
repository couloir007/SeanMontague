<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_editorial;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The Schema.org editorial manager.
 */
class SchemaDotOrgEditorialManager implements SchemaDotOrgEditorialManagerInterface {

  /**
   * Constructs a SchemaDotOrgEditorialManager object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(
    protected RequestStack $requestStack,
    protected MessengerInterface $messenger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function nodePresave(NodeInterface $node): void {
    // Never generate the editorial paragraph.
    if (!empty($node->devel_generate) && $node->hasField('field_editorial')) {
      $node->get('field_editorial')->setValue([]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function nodePrepareForm(NodeInterface $node, string $operation, FormStateInterface $form_state): void {
    // Only display message when the node edit for is loaded via a GET request.
    if ($this->requestStack->getCurrentRequest()->getMethod() !== 'GET') {
      return;
    }

    // See if the node has am editorial paragraphs.
    if (!$node->hasField('field_editorial')
      || empty($node->field_editorial->entity)) {
      return;
    }

    /** @var \Drupal\paragraphs\ParagraphInterface|null $paragraph */
    $paragraph = $node->field_editorial->entity;

    // See if the paragraphs has an editorial message.
    if (!$paragraph->hasField('field_editorial_message')
      || empty($paragraph->field_editorial_message->value)) {
      return;
    }

    // Display the editorial message as a warning.
    // @phpstan-ignore-next-line argument.type
    $this->messenger->addWarning([
      '#type' => 'processed_text',
      '#text' => $paragraph->field_editorial_message->value,
      // @phpstan-ignore-next-line property.notFound
      '#format' => $paragraph->field_editorial_message->format,
      '#langcode' => $paragraph->language()->getId(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function nodeViewAlter(array &$build, NodeInterface $node, EntityViewDisplayInterface $display): void {
    $field_name = 'field_editorial';
    // Check that the 'Editorial' paragraph is being rendered.
    if (empty($build[$field_name])) {
      return;
    }

    // Check that the 'Editorial' paragraph has field values,
    // if not hide the editorial paragraph entity reference field view display.
    /** @var \Drupal\paragraphs\ParagraphInterface|null $paragraph */
    $paragraph = $node->get($field_name)->entity;
    if (!$paragraph) {
      return;
    }

    $values = $paragraph->toArray();
    $values = array_filter(
      $values,
      fn($key) => str_starts_with($key, 'field_'),
      ARRAY_FILTER_USE_KEY
    );
    $values = array_filter($values);
    if (empty($values)) {
      $build[$field_name]['#access'] = FALSE;
    }
  }

}
