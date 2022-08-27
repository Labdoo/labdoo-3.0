<?php

namespace Drupal\field_collection_to_paragraphs\Plugin\migrate\source\d8;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base Drupal 8 Entity Source.
 */
abstract class BaseDrupal8EntitySource extends BaseDrupal8Source {

  /**
   * The migration manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationManager;

  /**
   * The entity storage instance.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\Drupal\Core\Entity\Sql\SqlEntityStorageInterface
   */
  protected $entityStorage;

  /**
   * Constructs a BaseDrupal8EntitySource object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration instance.
   * @param \Drupal\Core\State\StateInterface $state
   *   The Drupal state.
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_manager
   *   The migration manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, StateInterface $state, MigrationPluginManagerInterface $migration_manager, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state);
    $this->migrationManager = $migration_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('state'),
      $container->get('plugin.manager.migration'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    throw new \LogicException('Implement the fields method');
  }

  /**
   * Process an entity value.
   *
   * @param mixed $value
   *   The original field value.
   *
   * @return mixed
   *   The processed value.
   */
  protected function getValue($value) {
    if (is_array($value) && count($value) === 1) {
      if (isset($value[0]['value']) && count($value[0]) === 1) {
        return $value[0]['value'];
      }
    }

    return $value;
  }

}
