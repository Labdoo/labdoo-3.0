<?php

namespace Drupal\field_collection_to_paragraphs\Plugin\migrate;

use Drupal\field_collection_to_paragraphs\Plugin\migrate\source\d8\FieldCollectionItem;
use Drupal\field_collection_to_paragraphs\Plugin\migrate\source\d8\FieldCollectionParentEntityStorage;
use Drupal\field_collection_to_paragraphs\FieldCollectionVersionTrait;
use Drupal\migrate\Plugin\Migration;

/**
 * Migration plugin for the Field Collection item parent entities.
 */
class FieldCollectionItemParentSourceMigration extends Migration {

  use FieldCollectionMigrationSourceTrait;
  use FieldCollectionVersionTrait;

  /**
   * Flag indicating whether the field data has been filled already.
   *
   * @var bool
   */
  protected $init = FALSE;

  /**
   * {@inheritdoc}
   */
  public function getProcess() {
    if (!$this->init) {
      $this->init = TRUE;

      $parent_entity_type = $this->getDestinationEntityType($this);

      /**
       * @var $migration \Drupal\migrate\Plugin\MigrationInterface
       */
      $migration = $this->migrationPluginManager->createInstance(
        FieldCollectionParentEntityStorage::MIGRATION_ID
      );
      $source_mappings = $this->getMigrationIdMappings($migration);
      $field_mappings = [];

      // Fetch the field mappings.
      foreach ($source_mappings as $source_mapping) {
        foreach ($source_mapping as $field_storage_definition => $destination) {
          if (strpos($field_storage_definition, "field.storage.$parent_entity_type.") === 0) {
            $old_field_name = explode('.', $field_storage_definition)[3];
            // Map the old field name to the new one.
            $field_mappings[$old_field_name] = $destination['destid2'];
          }
        }
      }

      // Field Collection 1 and 3 had slightly different data structures, mainly
      // the main column name was "value" for v1 and "target_id" for v3.
      $source_col_name = $this->sourceColName();

      foreach ($field_mappings as $old_field => $new_field) {
        $process_definition = [
          [
            'plugin' => 'sub_process',
            'source' => $old_field,
            'process' => [
              'destination_ids' => [
                'plugin' => 'migration_lookup',
                'migration' => FieldCollectionItem::MIGRATION_ID,
                'source' => $source_col_name,
              ],
              'target_id' => [
                'plugin' => 'extract',
                'source' => '@destination_ids',
                'index' => [0],
              ],
              'target_revision_id' => [
                'plugin' => 'extract',
                'source' => '@destination_ids',
                'index' => [1],
              ],
            ],
          ],
        ];

        $this->setProcessOfProperty($new_field, $process_definition);
      }
    }

    return parent::getProcess();
  }

}
