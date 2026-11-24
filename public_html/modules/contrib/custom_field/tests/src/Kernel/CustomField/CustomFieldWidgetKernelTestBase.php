<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Kernel\CustomField;

use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Drupal\custom_field\Plugin\CustomFieldWidgetInterface;

/**
 * Base class for Custom Field widget Kernel tests.
 */
abstract class CustomFieldWidgetKernelTestBase extends CustomFieldKernelTestBase {

  /**
   * Creates a widget instance the same way the module does in production.
   *
   * Uses CustomFieldWidgetManager::getInstance() so prepareConfiguration()
   * and isApplicable() are exercised.
   */
  protected function getWidget(
    CustomFieldTypeInterface $subfield,
    string $field_name,
    string $widget_id,
    array $settings = [],
  ): CustomFieldWidgetInterface {
    $options = $this->widgetManager->createOptionsForInstance(
      $field_name,
      $subfield,
      $widget_id,
      $settings,
    );

    /** @var \Drupal\custom_field\Plugin\CustomFieldWidgetInterface $widget */
    $widget = $this->widgetManager->getInstance($options);
    $this->assertNotNull($widget, sprintf('Failed to create widget "%s".', $widget_id));

    return $widget;
  }

}
