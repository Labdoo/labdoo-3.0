<?php

namespace Drupal\field_collection_to_paragraphs\Plugin\migrate\source\d8;

use Drupal\migrate\Row;

/**
 * Field Collection parent field entity display settings.
 *
 * @MigrateSource(
 *   id = "d8_field_collection_parent_field_entity_display",
 *   source_module = "field_collection_to_paragraphs"
 * )
 */
class FieldCollectionParentEntityDisplay extends BaseDrupal8Source {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $subquery = $this->select('config', 'fc');

    // Use the field definition name + display mode as the unique name.
    $subquery->addExpression("CONCAT(fi.name, '.', SUBSTRING_INDEX(ed.name, '.', -1))", 'name');

    $subquery->addField('ed', 'name', 'view_display');
    $subquery->addField('ed', 'data');

    $subquery->innerJoin('config', 'fi',
      '%alias.name LIKE CONCAT(:field_instance, SUBSTR(fc.name, 35))', [
        ':field_instance' => FieldCollectionParentEntityInstance::FIELD_FIELD_PREFIX,
      ]);
    $subquery->innerJoin('config', 'ed',
      "%alias.name LIKE CONCAT(:field_collection_entity_display_prefix, SUBSTR(SUBSTRING_INDEX(fi.name, CONCAT('.', SUBSTR(fc.name, 35)), 1), 13), '.%')",
      [
        ':field_collection_entity_display_prefix' => 'core.entity_view_display.',
      ]
    );

    $subquery->condition('ed.collection', '');
    $subquery->condition('fi.collection', '');
    $this->filterCollectionTypes($subquery);

    // Must be a subquery in order to join by the name.
    return $this->select($subquery, 'ed')->fields('ed');
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'name' => [
        'type' => 'string',
        'alias' => 'ed',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'field_name' => $this->t('The field name.'),
      'field_options' => $this->t('The field options.'),
      'hidden' => $this->t('The hidden state.'),
    ] + parent::fields();
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function prepareRow(Row $row) {
    $this->unserializeData($row);

    list(, , , , $field_name) = explode('.', $row->getSourceProperty('name'));
    $row->setSourceProperty('field_name', $field_name);

    // Set the content settings.
    $row->setSourceProperty('field_options', $row->getSourceProperty("data/content/$field_name"));

    // Hidden flag.
    $row->setSourceProperty('hidden', $row->getSourceProperty("data/hidden/$field_name"));

    return parent::prepareRow($row);
  }

}
