<?php

namespace Drupal\entity_reference_tree\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;
use Drupal\entity_reference_tree\Ajax\OpenEntityReferenceTreeModalDialogCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Ajax\AjaxResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * EntityReferenceTreeController class.
 */
class EntityReferenceTreeController extends ControllerBase {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;
  
  /**
   * CSRF Token.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfToken;

  /**
   * The EntityReferenceTreeController constructor.
   *
   * @param \Drupal\Core\Form\FormBuilder $formBuilder
   *   The form builder.
   */
  public function __construct(FormBuilder $formBuilder, CsrfTokenGenerator $csrfToken) {
    $this->formBuilder = $formBuilder;
    $this->csrfToken = $csrfToken;
  }

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The Drupal service container.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('form_builder'),
        $container->get('csrf_token')
        );
  }

  /**
   * Callback for opening the modal form.
   */
  public function openSearchForm(Request $request, string $field_edit_id, string $bundle, string $entity_type, string $theme, int $dots, string $dialog_title) {
    $theme = $this->normalizeTheme($theme);
    $dots = $dots === 1 ? 1 : 0;
    $limit = $this->normalizeLimit($request->query->get('limit'));
    $token = $request->query->get('token');
    $worker = $this->normalizeBooleanFlag($request->query->get('worker'));
    $disable_animation = $this->normalizeBooleanFlag($request->query->get('disable_animation'));
    $force_text = $this->normalizeBooleanFlag($request->query->get('force_text'));
    if (empty($token) || !$this->csrfToken->validate($token, $this->buildModalTokenValue($field_edit_id, $bundle, $entity_type, $theme, $dots, $dialog_title, $limit, $worker, $disable_animation, $force_text))) {
      throw new AccessDeniedHttpException();
    }

    $response = new AjaxResponse();
    // Translate the title.
    $dialog_title = $this->t('@title', ['@title' => $dialog_title]);

    // Get the modal form using the form builder.
    $modal_form = $this->formBuilder->getForm('Drupal\entity_reference_tree\Form\SearchForm', $field_edit_id, $bundle, $entity_type, $theme, $dots, $worker, $disable_animation, $force_text, $limit);

    // Add an AJAX command to open a modal dialog with the form as the content.
    $response->addCommand(new OpenEntityReferenceTreeModalDialogCommand($dialog_title, $modal_form, ['width' => '800']));

    return $response;
  }

  /**
   * Callback for JsTree json data.
   */
  public function treeJson(Request $request, string $entity_type, string $bundles) {
    $token = $request->query->get('token');

    if (empty($token) || !$this->csrfToken->validate($token, $this->buildTreeJsonTokenValue($entity_type, $bundles))) {
      throw new AccessDeniedHttpException();
    }

    // Instance a entity tree builder for this entity type if it exists.
    if (\Drupal::hasService('entity_reference_' . $entity_type . '_tree_builder')) {
      $treeBuilder = \Drupal::service('entity_reference_' . $entity_type . '_tree_builder');
    }
    else {
      // Todo: A basic entity tree builder.
      $treeBuilder = \Drupal::service('entity_reference_entity_tree_builder');
    }

    $bundlesAry = explode(',', $bundles);
    $entityTrees = [];
    $entityNodeAry = [];

    foreach ($bundlesAry as $bundle_id) {
      $tree = $treeBuilder->loadTree($entity_type, $bundle_id);
      if (!empty($tree)) {
        $entityTrees[] = $tree;
      }
    }

    foreach ($entityTrees as $tree) {
      $processed = [];
      foreach ($tree as $entity) {
        // Create tree node for each entity.
        // Store them into an array passed to JS.
        // An array in JavaScript is indexed list.
        // JavaScript's array indices are always sequential
        // and start from 0.
        $treeNode = $treeBuilder->createTreeNode($entity);
        // Dedup, skip nodes that have already been processed.
        if (isset($processed[$treeNode['id']])) {
          continue;
        }
        // Applies a very permissive XSS/HTML filter for node text.
        $treeNode['text'] = Xss::filterAdmin($treeNode['text']);
        $entityNodeAry[] = $treeNode;
        $processed[$treeNode['id']] = TRUE;
      }
    }

    return new JsonResponse($entityNodeAry);
  }

  /**
   * Build the CSRF token value for the modal endpoint.
   */
  private function buildModalTokenValue(
    string $field_edit_id,
    string $bundle,
    string $entity_type,
    string $theme,
    int $dots,
    string $dialog_title,
    int $limit,
    int $worker,
    int $disable_animation,
    int $force_text
  ): string {
    return implode(':', [
      $field_edit_id,
      $bundle,
      $entity_type,
      $theme,
      $dots,
      $dialog_title,
      $limit,
      $worker,
      $disable_animation,
      $force_text,
    ]);
  }

  /**
   * Build the CSRF token value for the tree json endpoint.
   */
  private function buildTreeJsonTokenValue(string $entity_type, string $bundles): string {
    return implode(':', [$entity_type, $bundles]);
  }

  /**
   * Normalize checkbox/radio values to 0/1.
   */
  private function normalizeBooleanFlag($value): int {
    if (is_bool($value)) {
      return (int) $value;
    }

    if (is_int($value)) {
      return $value === 1 ? 1 : 0;
    }

    if (is_string($value)) {
      $normalized = strtolower($value);
      if (in_array($normalized, ['1', 'true', 'on', 'yes'], TRUE)) {
        return 1;
      }
    }

    return 0;
  }

  /**
   * Normalize the selection limit.
   */
  private function normalizeLimit($value): int {
    if (is_numeric($value)) {
      $limit = (int) $value;
      return $limit >= -1 ? $limit : -1;
    }

    return -1;
  }

  /**
   * Restrict jsTree themes to known values.
   */
  private function normalizeTheme(string $theme): string {
    return in_array($theme, ['default', 'default-dark'], TRUE) ? $theme : 'default';
  }

}
