<?php

namespace Drupal\field_collection_to_paragraphs\Plugin\migrate\source\d8;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Base source for fetching configuration data from a Drupal 8 database.
 */
abstract class BaseDrupal8Source extends SqlBase {

  use MessengerTrait;

  const FIELD_COLLECTION_PREFIX = 'field_collection.field_collection.';

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'name' => $this->t('The config object name.'),
      'data' => $this->t('Unserialized configuration object data.'),
      'data_serialized' => $this->t('Serialized configuration object data.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['collection']['type'] = 'string';
    $ids['name']['type'] = 'string';
    return $ids;
  }

  /**
   * Filter the current query by the collection types configuration.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The select query.
   */
  protected function filterCollectionTypes(SelectInterface $query) {
    if (!empty($this->configuration['field_collection_types'])) {
      // Restrict it to the specified field collection types.
      $keys = array_map(function ($item) {
        // Add the collection prefix.
        return self::FIELD_COLLECTION_PREFIX . $item;
      }, $this->configuration['field_collection_types']);
      $query->condition('fc.name', $keys, 'IN');
    }
    else {
      $cid = self::FIELD_COLLECTION_PREFIX . '%';
      $query->condition('fc.name', $cid, 'LIKE');
    }

    $query->condition('fc.collection', '');
  }

  /**
   * Unserialize the current row's data.
   *
   * @param \Drupal\migrate\Row $row
   *   The current row.
   *
   * @throws \Exception
   */
  protected function unserializeData(Row $row) {
    $serialized_data = $row->getSourceProperty('data');
    if (is_string($serialized_data)) {
      // Unserialize the current data if it appears to be serialized.
      $data = unserialize($serialized_data);
      $row->setSourceProperty('data_serialized', $serialized_data);
      $row->setSourceProperty('data', $data);
    }
  }

  /**
   * Run a query against the config.
   *
   * @param string|array $names
   *   The configs to search for.
   * @param bool $like
   *   Whether to run a LIKE query.
   * @param string $key
   *   The column key to use as the return array index.
   * @param array|null $collections
   *   Collections to search against.
   *
   * @return array
   *   The result set.
   */
  protected function queryConfig($names = NULL, $like = FALSE, $key = NULL, $collections = NULL) {
    $query = $this->select('config', 'c')
      ->fields('c', ['name', 'data']);
    if (!empty($collections)) {
      $query->condition('collection', (array) $collections, 'IN');
    }
    if ($like) {
      $query->condition('name', $names, 'LIKE');
    }
    elseif (!empty($names)) {
      $query->condition('name', (array) $names, 'IN');
    }
    $result = $query->execute()->fetchAll();
    $response = [];
    foreach ($result as $item) {
      $item['data'] = unserialize($item['data']);
      if ($key) {
        $response[$item['key']] = $item;
      }
      else {
        $response[] = $item;
      }
    }
    return $response;
  }

}
