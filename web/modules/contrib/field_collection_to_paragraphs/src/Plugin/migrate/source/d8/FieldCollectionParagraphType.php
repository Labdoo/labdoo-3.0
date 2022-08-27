<?php

namespace Drupal\field_collection_to_paragraphs\Plugin\migrate\source\d8;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Row;

/**
 * Drupal 8 Field Collection Paragraph types.
 *
 * @MigrateSource(
 *   id = "d8_field_collection_paragraph_type",
 *   source_module = "field_collection_to_paragraphs"
 * )
 */
class FieldCollectionParagraphType extends BaseDrupal8Source {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('config', 'fc');
    $query->fields('fc', [
      'name',
      'data',
    ]);

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
        'alias' => 'fc',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'id' => $this->t('The ID of the paragraph bundle.'),
      'label' => $this->t('The human-readable label for the field.'),
    ] + parent::fields();
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function prepareRow(Row $row) {
    $this->unserializeData($row);

    $data = $row->getSourceProperty('data');
    $paragraph_id = $this->getParagraphId($data['id']);

    if (mb_strlen($paragraph_id) > EntityTypeInterface::BUNDLE_MAX_LENGTH) {
      throw new MigrateSkipRowException(
        "The machine name '{$paragraph_id}' is longer than " . EntityTypeInterface::BUNDLE_MAX_LENGTH . ' characters.');
    }

    $label = ucfirst(str_replace('_', ' ', $paragraph_id));

    $row->setSourceProperty('id', $paragraph_id);
    $row->setSourceProperty('label', $label);

    return parent::prepareRow($row);
  }

  /**
   * Fetch the new paragraph bundle ID.
   *
   * @param string $field_id
   *   The original field collection ID.
   *
   * @return string
   *   The new ID to assign to the paragraph.
   */
  protected function getParagraphId($field_id) {
    if (isset($this->configuration['field_collection_type_mapping'][$field_id])) {
      return $this->configuration['field_collection_type_mapping'][$field_id];
    }

    // Field Collection types typically have a 'field_' prefix, remove it.
    if (strpos($field_id, 'field_') === 0) {
      return substr($field_id, 6);
    }

    // Keep the original field id if it doesn't start with a "field_" for some
    // reason.
    return $field_id;
  }

}
