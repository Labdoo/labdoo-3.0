<?php

namespace Drupal\field_collection_to_paragraphs\Plugin\migrate;

use Drupal\migrate\Plugin\migrate\destination\Entity;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Provides a trait for the field collection migration sources/destinations.
 */
trait FieldCollectionMigrationSourceTrait {

  /**
   * Get the destination entity type.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration to check.
   *
   * @return string
   *   The destination entity type.
   *
   * @throws \ReflectionException
   */
  protected function getDestinationEntityType(MigrationInterface $migration) {
    $destination_plugin = $migration->getDestinationPlugin();
    if ($destination_plugin instanceof Entity) {
      $plugin_id = $destination_plugin->getPluginId();
      $parts = explode(':', $plugin_id);
      if (count($parts) > 1) {
        // Assume the entity bundle is the last part.
        return end($parts);
      }

      // Fixme @codebymikey:20200303 Is this needed?
      // If a non-conventional plugin destination is used,
      // Since Drupal\migrate\Plugin\migrate\destination\Entity::getEntityTypeId
      // is protected, we must use the Reflection API to get the entity type.
      $destination_class = get_class($destination_plugin);
      $r = new \ReflectionMethod($destination_class, 'getEntityTypeId');
      $r->setAccessible(TRUE);
      return $r->invoke($destination_plugin, $destination_plugin->getPluginId());
    }

    throw new \LogicException('Destination plugin must be an Entity');
  }

  /**
   * Get the source ID to destination ID mapping for a single migration.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration instance.
   * @param bool $ignore_empty_destinations
   *   Whether to ignore empty destinations when mapping them.
   *
   * @return array
   *   The migration ID mappings.
   */
  protected function getMigrationIdMappings(MigrationInterface $migration, $ignore_empty_destinations = FALSE) {
    $source_id_fields = [];
    $count = 1;
    $column_names = [];

    foreach ($migration->getSourcePlugin()->getIds() as $field => $schema) {
      $column_name = 'sourceid' . $count++;
      $column_names[] = $column_name;
      $source_id_fields[$field] = $column_name;
    }

    $destination_id_fields = [];
    $count = 1;

    foreach ($migration->getDestinationPlugin()->getIds() as $field => $schema) {
      $column_name = 'destid' . $count++;
      $column_names[] = $column_name;
      $destination_id_fields[$field] = $column_name;
    }

    /** @var \Drupal\migrate\Plugin\migrate\id_map\Sql $mapping */
    $mapping = $migration->getIdMap();
    $query = $mapping->getDatabase()->select($mapping->mapTableName(), 'map')
      ->fields('map', $column_names);

    if ($ignore_empty_destinations) {
      foreach ($destination_id_fields as $column_name) {
        $query->isNotNull($column_name);
      }
    }

    $query->orderBy('destid1');
    $result = $query->execute();
    $id_mapping = [];

    if ($result) {
      while ($row = $result->fetchAssoc()) {
        $entry = [];
        foreach ($source_id_fields as $sourceIdField) {
          $key = $row[$sourceIdField];
          $entry[$key] = $row;
          // Leave only destination fields.
          unset($entry[$key][$sourceIdField]);
        }
        $id_mapping[] = $entry;
      }
    }

    return $id_mapping;
  }

}
