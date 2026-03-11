<?php

namespace Drupal\unpublished_404\EventSubscriber;

use Drupal\Core\EventSubscriber\HttpExceptionSubscriberBase;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Redirects 403 node page error responses to 404 page.
 */
class NotFound extends HttpExceptionSubscriberBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * Constructs a NotFound object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current user.
   */
  public function __construct(AccountProxyInterface $account) {
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  protected static function getPriority() {
    return 1000;
  }

  /**
   * {@inheritdoc}
   */
  protected function getHandledFormats() {
    return ['html'];
  }

  /**
   * Handles all 4xx errors for all serialization failures.
   *
   * @param \Symfony\Component\HttpKernel\Event\ExceptionEvent $event
   *   The event to process.
   */
  public function on403(ExceptionEvent $event) {
    // Check if current user has permission to view own unpublished content.
    if ($this->account && !$this->account->hasPermission('view own unpublished content')) {
      $request = $event->getRequest();
      if ($node = $request->attributes->get('node')) {
        if (!$node->isPublished()) {
          $event->setThrowable(new NotFoundHttpException());
        }
      }
    }
  }

}
