<?php

namespace Drupal\field_collection_to_paragraphs\Plugin\migrate\process;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Retrieves the field name to use for a Paragraph on a parent entity.
 *
 * Example:
 *
 * @code
 * process:
 *   'destination_field_name':
 *     plugin: field_collection_to_paragraphs_field_name
 *     source: source_field_name
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "field_collection_to_paragraphs_field_name"
 * )
 */
class ParagraphsFieldName extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\migrate\MigrateSkipRowException
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_string($value)) {
      throw new MigrateSkipRowException('The field collection field name must be a string.');
    }

    $field_name_mapping = $row->getSourceProperty('field_collection_field_name_mapping') ?? [];

    // Use the configured field mapping if available.
    if (isset($field_name_mapping[$value])) {
      return $field_name_mapping[$value];
    }

    $max_length = EntityTypeInterface::BUNDLE_MAX_LENGTH;

    $field_collection_type_mapping = $row->getSourceProperty('field_collection_type_mapping') ?? [];

    // If a type mapping was specified, attempt to use that for the field name.
    if (isset($field_collection_type_mapping[$value])) {
      return mb_substr('field_' . $field_collection_type_mapping[$value], 0, $max_length);
    }

    $field_suffix = $row->getSourceProperty('field_collection_field_name_suffix') ?? '_paragraph';

    // Use the field with the appended suffix if it's less than 32 characters.
    $new_field = $value . $field_suffix;
    if (mb_strlen($new_field) <= $max_length) {
      return $new_field;
    }

    // Reduce the suffix to the smallest substring that fits the 32 character.
    if ($max_length > mb_strlen($value)) {
      return mb_substr($value . $field_suffix, 0, $max_length);
    }

    // None of our fancy assumptions worked and the field name is already at
    // the limit.
    // Use the field name without the 'field_' prefix.
    return mb_substr($value . $field_suffix, 6, $max_length);
  }

}
