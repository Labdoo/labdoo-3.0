<?php

namespace Drupal\field_collection_to_paragraphs\Plugin\migrate\source\d8;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\field_collection_to_paragraphs\Plugin\migrate\FieldCollectionMigrationSourceTrait;
use Drupal\field_collection_to_paragraphs\FieldCollectionVersionTrait;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate\Row;

/**
 * Fetches parent nodes with references to migrated field collections.
 *
 * @MigrateSource(
 *   id = "d8_field_collection:node",
 *   source_module = "field_collection_to_paragraphs"
 * )
 */
class FieldCollectionParentNode extends BaseDrupal8EntitySource {

  use FieldCollectionMigrationSourceTrait;
  use FieldCollectionVersionTrait;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, StateInterface $state, MigrationPluginManagerInterface $migration_manager, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state, $migration_manager, $entity_type_manager);
    $this->entityStorage = $entity_type_manager->getStorage('node');
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function query() {
    $query = $this->select('node', 'n')
      ->fields('n', ['nid', 'vid']);

    $mappings = $this->getMigrationIdMappings(
      $this->migrationManager->createInstance(
        FieldCollectionParentEntityInstance::MIGRATION_ID)
    );

    $or_conditions = $query->orConditionGroup();

    // Field Collection 1 and 3 had slightly different data structures, mainly
    // the main column name was "value" for v1 and "target_id" for v3.
    $source_col_name = $this->sourceColName();

    $bundles = [];
    foreach ($mappings as $mapping) {
      foreach ($mapping as $field_definition => $destination) {
        if (strpos($field_definition, 'field.field.node.') === 0) {
          $bundle = $destination['destid2'];
          $bundles[$bundle] = $bundle;

          $old_field_name = explode('.', $field_definition)[4];

          // Replicating the logic from the entity query fetching nodes with
          // non-empty fields.
          $alias = $query->leftJoin("node__$old_field_name", NULL, '%alias.entity_id = n.nid');
          $or_conditions->isNotNull("${alias}.${old_field_name}_{$source_col_name}");
        }
      }
    }

    if (empty($bundles)) {
      // If we don't have a valid bundle mapping. Do nothing.
      $this->messenger()->addWarning($this->t('No field collection fields available for the node.'));
      $query->where('1 = 0');

      return $query;
    }

    $query->condition($or_conditions);

    $query->condition('n.type', $bundles, 'IN');

    $query
      ->groupBy('n.nid')
      ->groupBy('n.vid');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'nid' => $this->t('Node ID'),
      'vid' => $this->t('Node revision'),
      'uid' => $this->t('Author id'),
      'type' => $this->t('Node bundle'),
      'langcode' => $this->t('The language'),
      'title' => $this->t('The title of the node'),
      'promote' => $this->t('Promoted to frontpage'),
      'sticky' => $this->t('Sticky'),
      'status' => $this->t('Deleted status of the node'),
      'created' => $this->t('Created at'),
      'changed' => $this->t('Updated at'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'nid' => [
        'type' => 'integer',
        'alias' => 'n',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\migrate\MigrateSkipRowException
   * @throws \Exception
   */
  public function prepareRow(Row $row) {
    $nid = $row->getSourceProperty('nid');
    $vid = $row->getSourceProperty('vid');

    $node = $this->entityStorage->loadRevision($vid);

    if (!$node) {
      throw new MigrateSkipRowException("Could not load node with id: $nid ($vid).");
    }

    $fields = $node->toArray();

    foreach ($fields as $field_name => $field_value) {
      if (!$row->getSourceProperty($field_name)) {
        $row->setSourceProperty($field_name, $this->getValue($field_value));
      }
    }

    return parent::prepareRow($row);
  }

}
