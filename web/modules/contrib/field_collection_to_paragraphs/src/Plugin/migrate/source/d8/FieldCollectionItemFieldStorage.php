<?php

namespace Drupal\field_collection_to_paragraphs\Plugin\migrate\source\d8;

use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Row;

/**
 * Field Collections storage definition.
 *
 * @MigrateSource(
 *   id = "d8_field_collection_field_storage",
 *   source_module = "field_collection_to_paragraphs"
 * )
 */
class FieldCollectionItemFieldStorage extends FieldCollectionItemFieldInstance {

  const FIELD_STORAGE_COLLECTION_PREFIX = 'field.storage.field_collection_item.';

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'storage_data' => $this->t('The field storage definition.'),
    ] + parent::fields();
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function prepareRow(Row $row) {
    $this->unserializeData($row);

    $field_name = $row->getSourceProperty('data/field_name');

    $field_storage_definition = $this->queryConfig(static::FIELD_STORAGE_COLLECTION_PREFIX . $field_name);
    if (empty($field_storage_definition)) {
      throw new MigrateSkipRowException('Field Collection storage for ' . $field_name . ' is missing.');
    }

    $field_storage_definition = $field_storage_definition[0];
    $row->setSourceProperty('storage_data', $field_storage_definition['data']);

    return parent::prepareRow($row);
  }

}
