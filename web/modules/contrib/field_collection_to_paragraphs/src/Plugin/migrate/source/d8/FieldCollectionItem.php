<?php

namespace Drupal\field_collection_to_paragraphs\Plugin\migrate\source\d8;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate\Row;

/**
 * Field Collection items.
 *
 * @MigrateSource(
 *   id = "d8_field_collection_item",
 *   source_module = "field_collection_to_paragraphs"
 * )
 * @see \Drupal\field_collection\Entity\FieldCollectionItem
 */
class FieldCollectionItem extends BaseDrupal8EntitySource {

  const MIGRATION_ID = 'field_collection_to_paragraph_item';

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, StateInterface $state, MigrationPluginManagerInterface $migration_manager, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state, $migration_manager, $entity_type_manager);
    $this->entityStorage = $entity_type_manager->getStorage('field_collection_item');
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('field_collection_item', 'fci')
      ->fields('fci');

    if (!empty($this->configuration['field_collection_types'])) {
      // If an array of field collection type is specified, use it.
      $field_collections = $this->configuration['field_collection_types'];
      $query->condition('fci.field_name', $field_collections, 'IN');
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'item_id' => $this->t('Collection item ID'),
      'revision_id' => $this->t('Revision of the collection'),
      'field_name' => $this->t('Field Collection type'),
      'uuid' => $this->t('Unique id for the collection item'),
      'host_type' => $this->t('Host entity type e.g. node'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'item_id' => [
        'type' => 'integer',
        'alias' => 'fci',
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
    $item_id = $row->getSourceProperty('item_id');
    $field_collection_item = $this->entityStorage->load($item_id);
    if (!$field_collection_item) {
      throw new MigrateSkipRowException("Could not load field collection with id: $item_id.");
    }

    $fields = $field_collection_item->toArray();

    $reserved_fields = [
      'item_id',
      'host_type',
      'uuid',
      'revision_id',
      'field_name',
    ];

    foreach ($fields as $field_name => $field_value) {
      if (!in_array($field_name, $reserved_fields, TRUE)) {
        // Set any additional field properties declared by the field collection.
        $field_value = $this->getValue($field_value);
        $row->setSourceProperty($field_name, $field_value);
      }
    }

    // Specify the full field name bundle to do a migration lookup against.
    $row->setSourceProperty('field_collection_bundle_lookup',
      static::FIELD_COLLECTION_PREFIX . $row->getSourceProperty('field_name'));

    return parent::prepareRow($row);
  }

}
