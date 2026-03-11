<?php

namespace Drupal\schemadotorg_descriptions\Config;

use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Defines the interface for a Schema.org descriptions configuration factory override object.
 */
interface SchemaDotOrgDescriptionConfigFactoryOverrideInterface extends ConfigFactoryOverrideInterface, EventSubscriberInterface {

}
