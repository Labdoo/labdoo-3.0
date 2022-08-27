<?php

namespace Drupal\field_collection_to_paragraphs\Plugin\migrate\source\d8;

use Drupal\migrate\Row;

/**
 * Field Collections field instances.
 *
 * @MigrateSource(
 *   id = "d8_field_collection_field_instance",
 *   source_module = "field_collection_to_paragraphs"
 * )
 */
class FieldCollectionItemFieldInstance extends BaseDrupal8Source {

  const FIELD_FIELD_COLLECTION_PREFIX = 'field.field.field_collection_item.';

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('config', 'fc');
    $query->fields('fi', [
      'name',
      'data',
    ]);
    $query->innerJoin('config', 'fi',
      "%alias.name LIKE CONCAT(:field_field_collection_prefix, SUBSTR(fc.name, 35), '.%')",
      [
        ':field_field_collection_prefix' => static::FIELD_FIELD_COLLECTION_PREFIX,
      ]
    );

    $query->condition('fi.collection', '');
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
        'alias' => 'fi',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'field_collection_bundle_lookup' => $this->t('The field collection bundle to look up against.'),
    ] + parent::fields();
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function prepareRow(Row $row) {
    $this->unserializeData($row);

    $row->setSourceProperty('field_collection_bundle_lookup',
      self::FIELD_COLLECTION_PREFIX . $row->getSourceProperty('data/bundle')
    );

    return parent::prepareRow($row);
  }

}
