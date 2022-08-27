<?php

namespace Drupal\field_collection_to_paragraphs\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Converts source paragraph bundle to a Paragraphs entity reference settings.
 *
 * Example:
 *
 * @code
 * process:
 *   'settings/handler_settings':
 *     plugin: field_collection_to_paragraphs_handler_settings
 *     source: source_paragraph_bundle
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "field_collection_to_paragraphs_handler_settings"
 * )
 */
class ParagraphsHandlerSettings extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\migrate\MigrateSkipProcessException
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_string($value)) {
      throw new MigrateSkipProcessException('The paragraph bundle type must be a string.');
    }

    return [
      'target_bundles' => [
        $value => $value,
      ],
      'target_bundles_drag_drop' => [
        $value => [
          'enabled' => TRUE,
        ],
      ],
    ];
  }

}
