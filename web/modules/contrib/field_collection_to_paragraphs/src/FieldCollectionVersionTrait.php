<?php

namespace Drupal\field_collection_to_paragraphs;

/**
 * Some shared logic for determining the version of Field Collection.
 *
 * Field Collection 8.x-1.x (aka v1) uses "value" as the primary column in the
 * database for referencing the field_collection_item entity, whereas 8.x-3.x
 * (aka v3) uses "target_id".
 */
trait FieldCollectionVersionTrait {

  /**
   * Determine whether the Field Collection module was version 1 or 3.
   *
   * @return int
   *   Either 1 or 3, indicating the version of Field Collection that was used.
   *
   * @throws \LogicException
   */
  function determineFcVersion() {
    // Get the schema version of the installed Field Collection module.
    $schema_version = drupal_get_installed_schema_version('field_collection');

    // Schema version 8000 means this is Field Collection v1. This is the
    // default when installing a module in Drupal 8 (or 9?) when there are no
    // hook_update_N() hooks defined.
    if ($schema_version == 8000) {
      return 1;
    }

    // Schema version 8001 or higher means this is Field Collection v2.
    elseif ($schema_version >= 8001) {
      return 3;
    }
    // This shouldn't happen.
    else {
      throw new \LogicException('Unable to determine the version of Field Collection that was used.');
    }
  }

  /**
   * Indicates whether the source data is from Field Collection version 1.
   *
   * @return bool
   *   Whether the source data is from Field Collection version 1.
   */
  protected function isFcVersion1() {
    return ($this->determineFcVersion() == 1);
  }

  /**
   * Indicates whether the source data is from Field Collection version 3.
   *
   * @return bool
   *   Whether the source data is from Field Collection version 3.
   */
  protected function isFcVersion3() {
    return ($this->determineFcVersion() == 3);
  }

  /**
   * Identify the name of the database column to use for the source data.
   *
   * @return string
   *   Will be "value" for Field Collection 1 and "target_id" for Field
   *   Collection v3.
   */
  protected function sourceColName() {
    if ($this->determineFcVersion() == 1) {
      return "value";
    }
    else {
      return 'target_id';
    }
  }

}
