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
 * Fetches parent taxonomy terms with references to migrated field collections.
 *
 * @MigrateSource(
 *   id = "d8_field_collection:taxonomy_term",
 *   source_module = "field_collection_to_paragraphs"
 * )
 */
class FieldCollectionParentTaxonomyTerm extends BaseDrupal8EntitySource {

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
    $this->entityStorage = $entity_type_manager->getStorage('taxonomy_term');
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('taxonomy_term_data', 't')
      ->fields('t', ['tid']);

    $mappings = $this->getMigrationIdMappings(
      $this->migrationManager->createInstance(
        FieldCollectionParentEntityInstance::MIGRATION_ID)
    );

    $or_conditions = $query->orConditionGroup();

    // Field Collection 1 and 3 had slightly different data structures, mainly
    // the main column name was "value" for v1 and "target_id" for v3.
    if ($this->determineFcVersion() == 1) {
      $source_col_name = "value";
    }
    else {
      $source_col_name = 'target_id';
    }

    $bundles = [];
    foreach ($mappings as $mapping) {
      foreach ($mapping as $field_definition => $destination) {
        if (strpos($field_definition, 'field.field.taxonomy_term.') === 0) {
          $bundle = $destination['destid2'];
          $bundles[$bundle] = $bundle;

          $old_field_name = explode('.', $field_definition)[4];

          // Replicating the logic from the entity query fetching taxonomy terms
          // with non-empty fields.
          $alias = $query->leftJoin("taxonomy_term__$old_field_name", NULL, '%alias.entity_id = t.tid');
          $or_conditions->isNotNull("${alias}.${old_field_name}_{$source_col_name}");
        }
      }
    }

    if (empty($bundles)) {
      // If we don't have a valid bundle mapping. Do nothing.
      $this->messenger()->addWarning($this->t('No field collection fields available for the taxonomy term.'));
      $query->where('1 = 0');

      return $query;
    }

    $query->condition($or_conditions);

    $query->condition('t.vid', $bundles, 'IN');

    $query->groupBy('t.tid');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'tid' => $this->t('Taxonomy term ID'),
      'vid' => $this->t('Vocabulary term'),
      'langcode' => $this->t('The taxonomy term langcode'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'tid' => [
        'type' => 'integer',
        'alias' => 't',
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
    $tid = $row->getSourceProperty('tid');

    $taxonomy_term = $this->entityStorage->load($tid);
    if (!$taxonomy_term) {
      throw new MigrateSkipRowException("Could not load taxonomy term with id: $tid.");
    }

    $fields = $taxonomy_term->toArray();

    foreach ($fields as $field_name => $field_value) {
      if (!$row->getSourceProperty($field_name)) {
        $row->setSourceProperty($field_name, $this->getValue($field_value));
      }
    }

    return parent::prepareRow($row);
  }

}
