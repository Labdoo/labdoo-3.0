<?php

namespace Drupal\field_collection_to_paragraphs\Plugin\migrate\source\d8;

use Drupal\migrate\Row;

/**
 * Field Collections parent entity storage definition.
 *
 * @MigrateSource(
 *   id = "d8_field_collection_parent_field_storage",
 *   source_module = "field_collection_to_paragraphs"
 * )
 */
class FieldCollectionParentEntityStorage extends BaseDrupal8Source {

  const MIGRATION_ID = 'field_collection_to_paragraph_parent_field_storage';

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('config', 'fc');
    $query->fields('fs', [
      'name',
      'data',
    ]);
    $query->innerJoin('config', 'fs',
      '%alias.name LIKE CONCAT(:field_storage, SUBSTR(fc.name, 35))', [
        ':field_storage' => 'field.storage.%.',
      ]);

    $query->condition('fs.collection', '');
    $this->filterCollectionTypes($query);

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      // The machine name of the field_collection config.
      'name' => [
        'type' => 'string',
        'alias' => 'fs',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function prepareRow(Row $row) {
    $this->unserializeData($row);

    return parent::prepareRow($row);
  }

}
