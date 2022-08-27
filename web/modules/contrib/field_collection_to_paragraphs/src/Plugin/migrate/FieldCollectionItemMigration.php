<?php

namespace Drupal\field_collection_to_paragraphs\Plugin\migrate;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\migrate\Plugin\MigrateDestinationPluginManager;
use Drupal\migrate\Plugin\MigratePluginManagerInterface;
use Drupal\migrate\Plugin\Migration;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Migration plugin for Field Collection item entities.
 */
class FieldCollectionItemMigration extends Migration {

  /**
   * Flag indicating whether the field data has been filled already.
   *
   * @var bool
   */
  protected $init = FALSE;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a Field Collection item Migration.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_plugin_manager
   *   The migration plugin manager.
   * @param \Drupal\migrate\Plugin\MigratePluginManagerInterface $source_plugin_manager
   *   The source migration plugin manager.
   * @param \Drupal\migrate\Plugin\MigratePluginManagerInterface $process_plugin_manager
   *   The process migration plugin manager.
   * @param \Drupal\migrate\Plugin\MigrateDestinationPluginManager $destination_plugin_manager
   *   The destination migration plugin manager.
   * @param \Drupal\migrate\Plugin\MigratePluginManagerInterface $idmap_plugin_manager
   *   The ID map migration plugin manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationPluginManagerInterface $migration_plugin_manager, MigratePluginManagerInterface $source_plugin_manager, MigratePluginManagerInterface $process_plugin_manager, MigrateDestinationPluginManager $destination_plugin_manager, MigratePluginManagerInterface $idmap_plugin_manager, EntityFieldManagerInterface $entity_field_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration_plugin_manager, $source_plugin_manager, $process_plugin_manager, $destination_plugin_manager, $idmap_plugin_manager);
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.migration'),
      $container->get('plugin.manager.migrate.source'),
      $container->get('plugin.manager.migrate.process'),
      $container->get('plugin.manager.migrate.destination'),
      $container->get('plugin.manager.migrate.id_map'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getProcess() {
    if (!$this->init) {
      $this->init = TRUE;

      $reserved_fields = [
        'item_id',
        'host_type',
        'uuid',
        'revision_id',
        'field_name',
      ];

      $field_collection_storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions('field_collection_item');

      // Map existing field collection item fields to their equivalent
      // paragraph ones.
      foreach ($field_collection_storage_definitions as $field_name => $field_collection_storage_definition) {
        if (!in_array($field_name, $reserved_fields, TRUE)) {
          // Add the field process if it doesn't already exists.
          if (!$this->process[$field_name]) {
            $this->setProcessOfProperty($field_name, $field_name);
          }
        }
      }
    }

    return parent::getProcess();
  }

}
