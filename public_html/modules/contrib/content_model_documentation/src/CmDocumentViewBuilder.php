<?php

namespace Drupal\content_model_documentation;

use Drupal\content_model_documentation\Entity\CMDocumentInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\Entity\Role;

/**
 * Defines a class for entity view builder for entities.
 */
class CmDocumentViewBuilder extends EntityViewBuilder {

  use CMDocumentConnectorTrait;

  /**
   * The configuration manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The permission handler.
   *
   * @var \Drupal\user\PermissionHandlerInterface
   */
  protected $permissionHandler;

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $instance = parent::createInstance($container, $entity_type);
    $instance->configManager = $container->get('config.manager');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->permissionHandler = $container->get('user.permissions');
    $instance->moduleExtensionList = $container->get('extension.list.module');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $cm_document, $view_mode = 'full', $langcode = NULL) {
    $build = parent::view($cm_document, $view_mode, $langcode);
    $add_ons = $this->getAddOns($cm_document);
    $build = array_merge($build, $add_ons);

    return $build;
  }

  /**
   * Grabs all the add on render arrays for a given kind of documentation.
   *
   * @param \Drupal\content_model_documentation\Entity\CMDocumentInterface $cm_document
   *   The current document to process.
   *
   * @return array
   *   An array of render arrays to be added on.
   */
  protected function getAddOnTypes(CMDocumentInterface $cm_document): array {
    $type = $cm_document->getDocumentedEntityParameter('type');
    $bundle = $cm_document->getDocumentedEntityParameter('bundle');
    $field = $cm_document->getDocumentedEntityParameter('field');
    // Order of the addons specified determines their order on the page.
    switch (TRUE) {
      case ($type === 'base' && !empty($field)):
        // This is documentation for a base field.
        $add_ons = ['SiblingFields'];
        break;

      case (!empty($field)):
        // This is documentation for a field.
        $add_ons = ['AppearsOn', 'BaseField', 'SiblingFields'];
        break;

      case ((empty($field)) && ($type !== 'site') && ($type !== 'module') && ($type !== 'view')):
        // This is fieldable entity.
        $add_ons = ['FieldsOnEntity'];
        if ($type === 'node' || $type === 'taxonomy_term') {
          array_push($add_ons, 'PermissionOnEntity', 'AliasPattern');
        }
        break;

      default:
        $add_ons = [];
        break;
    }
    return $add_ons;
  }

  /**
   * Gets all the add ons that should appear on a CM Document.
   *
   * @param \Drupal\content_model_documentation\Entity\CMDocumentInterface $cm_document
   *   The current document to process.
   *
   * @return array
   *   An array of render arrays for each of the related add ons.
   */
  protected function getAddOns(CMDocumentInterface $cm_document): array {
    $add_on_types = $this->getAddOnTypes($cm_document);
    $add_ons = [];
    foreach ($add_on_types as $i => $add_on) {
      $func = "get{$add_on}";
      $add_ons["$add_on"] = $this->$func($cm_document);
    }

    return $add_ons;
  }

  /**
   * Gets a render array showing the content type this field appears on.
   *
   * @param \Drupal\content_model_documentation\Entity\CMDocumentInterface $cm_document
   *   The current document to process.
   *
   * @return array
   *   A renderable array for the appears on section of the page.
   */
  protected function getAppearsOn(CMDocumentInterface $cm_document): array {
    $add_on = [];
    $type = $cm_document->getDocumentedEntityParameter('type');
    $bundle = $cm_document->getDocumentedEntityParameter('bundle');
    $field = $cm_document->getDocumentedEntityParameter('field');
    $documented_entity = $cm_document->getDocumentedEntity();
    $label = (empty($documented_entity)) ? $this->t('undefined') : $documented_entity->label();
    $cm_doc_link = $this->getCmDocumentLink($type, $bundle);
    if ($cm_doc_link) {
      $add_on = $cm_doc_link->toRenderable();
      $add_on['#title'] = "$type $label ($bundle)";
    }
    else {
      $add_on = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => "$type $label ($bundle)",
      ];
    }
    $add_on['#prefix'] = "<span class=\"field__label\">{$this->t('Appears on: ')}</span>";
    // This one should be near the top of the page.
    $add_on['#weight'] = -10;
    return $add_on;
  }

  /**
   * Gets a link to the base field CM Document if it exists.
   *
   * @param \Drupal\content_model_documentation\Entity\CMDocumentInterface $cm_document
   *   The current document to process.
   *
   * @return array
   *   A render array for a link if one exists, or an empty array.
   */
  protected function getBaseField(CMDocumentInterface $cm_document): array {
    $add_on = [];
    $field = $cm_document->getDocumentedEntityParameter('field');
    $base_cm_doc = "base.field.$field";
    // Does documentation for the base field exist?
    $cm_doc_link = $this->getCmDocumentLink('base', 'field', $field);
    if ($cm_doc_link) {
      $add_on = $cm_doc_link->toRenderable();
      $add_on['#title'] = $this->t('Base @field documentation', ['@field' => $field]);
      $add_on['#weight'] = 10;
    }
    return $add_on;
  }

  /**
   * Gets a a table of siblings if they exist.
   *
   * @param \Drupal\content_model_documentation\Entity\CMDocumentInterface $cm_document
   *   The current document to process.
   *
   * @return array
   *   A render array for a table if siblings exists, or an empty array.
   */
  protected function getSiblingFields(CMDocumentInterface $cm_document): array {
    $add_on = [];
    $rows = $this->buildSiblingRows($cm_document);
    if (!empty($rows)) {
      $type = $cm_document->getDocumentedEntityParameter('type');
      $field = $cm_document->getDocumentedEntityParameter('field');
      // Adjust the count for display where the documented field was removed.
      $count = ($type === 'base') ? count($rows) : count($rows) + 1;
      $add_on['table'] = [
        '#type' => 'table',
        '#header' => [
          $this->t('Field Name'),
          $this->t('Entity Type'),
          $this->t('Bundle'),
          $this->t('Field Type'),
          $this->t('Documentation'),
          $this->t('Edit'),
        ],
        '#rows' => $rows,
        '#footer' => [["Total instances: $count", '', '', '', '', '']],
        '#empty' => $this->t('No table content found.'),
        '#caption' => $this->t("Sibling instances of field @field.", ['@field' => $field]),
        '#attributes' => [
          'class' => ['sortable'],
        ],

        '#attached' => ['library' => ['content_model_documentation/sortable-init']],
      ];
      $add_on['#weight'] = 20;
    }

    return $add_on;
  }

  /**
   * Builds the rows of all instances of a field.
   *
   * @param \Drupal\content_model_documentation\Entity\CMDocumentInterface $cm_document
   *   The current document to process.
   *
   * @return array
   *   An array of table rows.
   */
  protected function buildSiblingRows(CMDocumentInterface $cm_document): array {
    $sibling_rows = [];
    $field = $cm_document->getDocumentedEntityParameter('field');
    $src_bundle = $cm_document->getDocumentedEntityParameter('bundle');
    $mapped_types = $cm_document->entityFieldManager->getFieldMap();
    foreach ($mapped_types as $entity_type => $fields) {
      if (!empty($fields[$field])) {
        foreach ($fields[$field]['bundles'] as $bundle) {
          // Do not include CM Documents in this list, or the current instance.
          if ($bundle !== 'cm_document' && $src_bundle !== $bundle) {
            $definitions = $cm_document->entityFieldManager->getFieldDefinitions($entity_type, $bundle);
            $sibling_rows[] = [
              'Field Name' => $definitions[$field]->getLabel(),
              'Entity Type' => $entity_type,
              'Bundle' => $bundle,
              'Field Type' => $fields[$field]['type'],
              'Document' => $this->getCmDocumentLink($entity_type, $bundle, $field),
              'Edit' => $edit_link = $this->getFieldEditLink($entity_type, $bundle, $field),
            ];
          }
        }
      }
    }

    return $sibling_rows;
  }

  /**
   * Gets a a table of field data for fields on a fieldable entity.
   *
   * @param \Drupal\content_model_documentation\Entity\CMDocumentInterface $cm_document
   *   The current document to process.
   *
   * @return array
   *   A render array for a table if fields exist, or an empty array.
   */
  protected function getFieldsOnEntity(CMDocumentInterface $cm_document): array {
    $add_on = [];
    $rows = $this->buildFieldRows($cm_document);
    $count = count($rows);
    if (!empty($rows)) {
      $type = $cm_document->getDocumentedEntityParameter('type');
      $bundle = $cm_document->getDocumentedEntityParameter('bundle');
      $add_on['table'] = [
        '#type' => 'table',
        '#header' => [
          $this->t('Field Name'),
          $this->t('Field Machine Name'),
          $this->t('Field Type'),
          $this->t('Description'),
          $this->t('Documentation'),
          $this->t('Edit'),
        ],
        '#rows' => $rows,
        '#footer' => [["Total fields: $count", '', '', '', '', '']],
        '#empty' => $this->t('No table content found.'),
        '#caption' => $this->t("Fields that appear on @type @bundle", ['@type' => $type, '@bundle' => $bundle]),
        '#attributes' => [
          'class' => ['sortable'],
        ],

        '#attached' => ['library' => ['content_model_documentation/sortable-init']],
      ];
      $add_on['#weight'] = 20;
    }

    return $add_on;
  }

  /**
   * Gets a a table of permission data for an entity.
   *
   * @param \Drupal\content_model_documentation\Entity\CMDocumentInterface $cm_document
   *   The current document to process.
   *
   * @return array
   *   A render array of entity permissions.
   */
  protected function getPermissionOnEntity(CMDocumentInterface $cm_document): array {
    $role_names = [];
    $role_permissions = [];
    $admin_roles = [];
    $roles = Role::loadMultiple();
    $entity_type = $cm_document->getDocumentedEntityParameter('type');
    $bundle = $cm_document->getDocumentedEntityParameter('bundle');

    $add_on['permissions'] = [
      '#type' => 'table',
      '#header' => [$this->t('Permission')],
      '#sticky' => TRUE,
      '#caption' => $this->t("Permissions for @type @bundle", ['@type' => $entity_type, '@bundle' => $bundle]),
    ];

    foreach ($roles as $role_name => $role) {
      // Retrieve role names for columns.
      $role_names[$role_name] = $role->label();
      // Fetch permissions for the roles.
      $role_permissions[$role_name] = $role->getPermissions();
      $admin_roles[$role_name] = $role->isAdmin();
    }
    foreach ($role_names as $name) {
      $add_on['permissions']['#header'][] = [
        'data' => $name,
        'class' => ['checkbox'],
      ];
    }

    $bundle_permission = $this->permissionsOfBundle($cm_document->getStorageMap()[$entity_type], $bundle);
    foreach ($bundle_permission as $provider => $permissions) {

      // Module name.
      $add_on['permissions'][$provider] = [
        [
          '#wrapper_attributes' => [
            'colspan' => count($role_names) + 1,
          ],
          '#markup' => $this->moduleExtensionList->getName($provider),
        ],
      ];

      foreach ($permissions as $perm => $perm_item) {
        // Fill in default values for the permission.
        $perm_item += [
          'description' => '',
          'restrict access' => FALSE,
          'warning' => !empty($perm_item['restrict access']) ? $this->t('Warning: Give to trusted roles only; this permission has security implications.') : '',
        ];
        $add_on['permissions'][$perm]['description'] = [
          '#type' => 'inline_template',
          '#template' => '<div class="permission"><span class="title table-filter-text-source">{{ title }}</span>{% if description or warning %}<div class="description">{% if warning %}<em class="permission-warning">{{ warning }}</em> {% endif %}{{ description }}</div>{% endif %}</div>',
          '#context' => [
            'title' => $perm_item['title'],
          ],
        ];

        $add_on['permissions'][$perm]['description']['#context']['description'] = $perm_item['description'];
        $add_on['permissions'][$perm]['description']['#context']['warning'] = $perm_item['warning'];

        foreach ($role_names as $rid => $name) {
          $add_on['permissions'][$perm][$rid] = [
            '#title' => $name . ': ' . $perm_item['title'],
            '#title_display' => 'invisible',
            '#wrapper_attributes' => [
              'class' => ['checkbox'],
            ],
            '#type' => 'checkbox',
            '#default_value' => in_array($perm, $role_permissions[$rid]) ? 1 : 0,
            '#attributes' => ['class' => ['rid-' . $rid, 'js-rid-' . $rid], 'disabled' => 'disabled'],
            '#parents' => [$rid, $perm],
          ];
          $add_on['permissions'][$perm][$rid]['#attributes']['checked'] = in_array($perm, $role_permissions[$rid]) ? 'checked' : FALSE;
          // Show a column of checked checkboxes.
          if ($admin_roles[$rid]) {
            $add_on['permissions'][$perm][$rid]['#attributes']['checked'] = 'checked';
          }
        }
      }
    }
    $add_on['#weight'] = 21;
    return $add_on;
  }

  /**
   * Gets the Pathauto pattern for the entity, if available.
   *
   * @param \Drupal\content_model_documentation\Entity\CMDocumentInterface $cm_document
   *   The current document to process.
   *
   * @return array
   *   A render array for the Pathauto pattern or an empty array.
   */
  protected function getAliasPattern(CMDocumentInterface $cm_document): array {
    if (!$this->moduleExtensionList->exists('pathauto')) {
      return [];
    }

    $type = $cm_document->getDocumentedEntityParameter('type');
    $bundle = $cm_document->getDocumentedEntityParameter('bundle');

    // Load all patterns, including disabled ones.
    $pattern_storage = $this->entityTypeManager->getStorage('pathauto_pattern');
    $patterns = $pattern_storage->loadByProperties([]);
    $applicable = [];
    $pattern_type = $type === 'node' ? 'canonical_entities:node' : 'canonical_entities:taxonomy_term';

    foreach ($patterns as $pattern) {
      if ($pattern->get('type') !== $pattern_type) {
        continue;
      }

      $criteria = $pattern->get('selection_criteria');
      $matches = empty($criteria);

      if (!$matches && !empty($criteria)) {
        foreach ($criteria as $criterion) {
          if (isset($criterion['id']) && $criterion['id'] === "entity_bundle:$type" && isset($criterion['bundles']) && array_key_exists($bundle, $criterion['bundles'])) {
            $matches = TRUE;
            break;
          }
        }
      }

      if ($matches) {
        $label = $pattern->label();
        if (empty($criteria)) {
          $label .= ' (default)';
        }
        if (!$pattern->status()) {
          $label .= ' (disabled)';
        }
        $applicable[] = [
          'pattern' => $pattern->getPattern(),
          'label' => $label,
        ];
        if (empty($criteria)) {
          break;
        }
      }
    }

    $add_on = [
      '#prefix' => '<h3>' . $this->t('Pathauto Patterns') . '</h3>',
      '#weight' => 20.5,
    ];

    if (!empty($applicable)) {
      $items = [];
      foreach ($applicable as $pattern_data) {
        $items[] = [
          '#type' => 'html_tag',
          '#tag' => 'li',
          '#value' => $this->t('@label: @pattern', [
            '@label' => $pattern_data['label'],
            '@pattern' => $pattern_data['pattern'],
          ]),
        ];
      }
      $add_on['#type'] = 'html_tag';
      $add_on['#tag'] = 'ul';
      $add_on['items'] = $items;
    }
    else {
      $add_on['#type'] = 'html_tag';
      $add_on['#tag'] = 'div';
      $add_on['#value'] = $this->t('No pattern set.');
    }

    return $add_on;
  }

  /**
   * Get the array of permissions associated with a entity bundle.
   *
   * @param string $entity_type
   *   The entity type for the permissions.
   * @param string $bundle
   *   The bundle id for the permissions.
   *
   * @return array
   *   An array of permissions related to the entity bundle.
   */
  protected function permissionsOfBundle(string $entity_type, string $bundle): array {

    // Load a specific bundle (e.g., node content type).
    $bundle_info = $this->entityTypeManager->getStorage($entity_type)->load($bundle);

    // Get the names of all config entities that depend on $this->bundle.
    $config_name = $bundle_info->getConfigDependencyName();
    $config_entities = $this->configManager->findConfigEntityDependencies('config', [$config_name]);
    $config_names = array_map(
      fn($dependent_config) => $dependent_config->getConfigDependencyName(),
      $config_entities,
    );
    $config_names[] = $config_name;

    // Find all the permissions that depend on $bundle.
    $permissions = $this->permissionHandler->getPermissions();
    $permissions_by_provider = [];
    foreach ($permissions as $permission_name => $permission) {
      $required_configs = $permission['dependencies']['config'] ?? [];
      if (array_intersect($required_configs, $config_names)) {
        $provider = $permission['provider'];
        $permissions_by_provider[$provider][$permission_name] = $permission;
      }
    }

    return $permissions_by_provider;
  }

  /**
   * Builds the rows of all fields on a fieldable entity.
   *
   * @param \Drupal\content_model_documentation\Entity\CMDocumentInterface $cm_document
   *   The current document to process.
   *
   * @return array
   *   An array of table rows.
   */
  protected function buildFieldRows(CMDocumentInterface $cm_document): array {
    $field_rows = [];
    $type = $cm_document->getDocumentedEntityParameter('type');
    $bundle = $cm_document->getDocumentedEntityParameter('bundle');
    $fields = $cm_document->entityFieldManager->getFieldDefinitions($type, $bundle);
    foreach ($fields as $machine => $value) {
      if (!$this->isField($machine)) {
        // It is not a field element, so bail out.
        continue;
      }
      $field_rows[] = [
        'field _name' => $value->getLabel(),
        'machine name' => $machine,
        'field_type' => $value->getType(),
        'description' => $value->getDescription(),
        'documentation' => $this->getCmDocumentLink($type, $bundle, $machine),
        'edit' => $this->getFieldEditLink($type, $bundle, $machine),
      ];
    }

    return $field_rows;

  }

}
