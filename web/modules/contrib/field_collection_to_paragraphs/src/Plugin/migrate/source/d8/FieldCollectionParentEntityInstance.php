<?php

namespace Drupal\field_collection_to_paragraphs\Plugin\migrate\source\d8;

use Drupal\migrate\Row;

/**
 * Field Collections parent entity field instances.
 *
 * @MigrateSource(
 *   id = "d8_field_collection_parent_field_instance",
 *   source_module = "field_collection_to_paragraphs"
 * )
 */
class FieldCollectionParentEntityInstance extends BaseDrupal8Source {

  const MIGRATION_ID = 'field_collection_to_paragraph_parent_field_instance';

  const FIELD_FIELD_PREFIX = 'field.field.%.%.';

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
      '%alias.name LIKE CONCAT(:field_instance, SUBSTR(fc.name, 35))', [
        ':field_instance' => static::FIELD_FIELD_PREFIX,
      ]);

    $query->condition('fi.collection', '');
    $this->filterCollectionTypes($query);

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
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
      static::FIELD_COLLECTION_PREFIX . $row->getSourceProperty('data/field_name')
    );

    return parent::prepareRow($row);
  }

}
