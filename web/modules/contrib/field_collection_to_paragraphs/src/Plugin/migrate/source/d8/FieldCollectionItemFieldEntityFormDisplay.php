<?php

namespace Drupal\field_collection_to_paragraphs\Plugin\migrate\source\d8;

use Drupal\migrate\Row;

/**
 * Field Collection field entity form display settings.
 *
 * @MigrateSource(
 *   id = "d8_field_collection_field_entity_form_display",
 *   source_module = "field_collection_to_paragraphs"
 * )
 */
class FieldCollectionItemFieldEntityFormDisplay extends BaseDrupal8Source {

  const FIELD_COLLECTION_ENTITY_FORM_DISPLAY_PREFIX = 'core.entity_form_display.field_collection_item.';

  /**
   * {@inheritdoc}
   */
  public function query() {
    $subquery = $this->select('config', 'fc');

    // Use the field definition name + display mode as the unique name.
    $subquery->addExpression("CONCAT(fi.name, '.', SUBSTRING_INDEX(fd.name, '.', -1))", 'name');

    $subquery->addField('fd', 'name', 'form_display');
    $subquery->addField('fd', 'data');

    $subquery->innerJoin('config', 'fi',
      "%alias.name LIKE CONCAT(:field_field_collection_prefix, SUBSTR(fc.name, 35), '.%')",
      [
        ':field_field_collection_prefix' => FieldCollectionItemFieldInstance::FIELD_FIELD_COLLECTION_PREFIX,
      ]
    );
    $subquery->innerJoin('config', 'fd',
      "%alias.name LIKE CONCAT(:field_collection_entity_form_display_prefix, SUBSTR(fc.name, 35), '.%')",
      [
        ':field_collection_entity_form_display_prefix' => static::FIELD_COLLECTION_ENTITY_FORM_DISPLAY_PREFIX,
      ]
    );

    $subquery->condition('fi.collection', '');
    $subquery->condition('fd.collection', '');
    $this->filterCollectionTypes($subquery);

    // Must be a subquery in order to join by the name.
    return $this->select($subquery, 'fd')->fields('fd');
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'name' => [
        'type' => 'string',
        'alias' => 'fd',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'field_collection_bundle_lookup' => $this->t('The field collection bundle to look up against.'),
      'field_name' => $this->t('The field name.'),
      'field_form_options' => $this->t('The field form options.'),
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

    $row->setSourceProperty('field_collection_bundle_lookup',
      self::FIELD_COLLECTION_PREFIX . $row->getSourceProperty('data/bundle')
    );

    list(,,,, $field_name) = explode('.', $row->getSourceProperty('name'));
    $row->setSourceProperty('field_name', $field_name);

    // Set the content form settings.
    $row->setSourceProperty('field_form_options', $row->getSourceProperty("data/content/$field_name"));

    // Hidden flag.
    $row->setSourceProperty('hidden', $row->getSourceProperty("data/hidden/$field_name"));

    return parent::prepareRow($row);
  }

}
