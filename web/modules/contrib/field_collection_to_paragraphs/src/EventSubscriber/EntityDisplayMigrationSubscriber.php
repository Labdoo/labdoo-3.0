<?php

namespace Drupal\field_collection_to_paragraphs\EventSubscriber;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\migrate\Plugin\migrate\destination\PerComponentEntityDisplay;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for entity display.
 */
class EntityDisplayMigrationSubscriber implements EventSubscriberInterface {

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * EntityDisplayMigrationSubscriber constructor.
   *
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository service.
   */
  public function __construct(EntityDisplayRepositoryInterface $entity_display_repository) {
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[MigrateEvents::POST_ROW_SAVE][] = ['postRowSave'];

    return $events;
  }

  /**
   * Event subscriber for acting upon updated migration row entries.
   *
   * @param \Drupal\migrate\Event\MigratePostRowSaveEvent $event
   *   The post row save event.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function postRowSave(MigratePostRowSaveEvent $event) {
    $migration = $event->getMigration();
    $row = $event->getRow();
    $destination_plugin = $migration->getDestinationPlugin();

    if ($destination_plugin instanceof PerComponentEntityDisplay && $event->getDestinationIdValues()) {
      $source_field = $row->getSourceProperty('field_name');

      list($entity_type, $bundle, $view_mode, $destination_field) = $event->getDestinationIdValues();

      if (!$source_field || !$destination_field || $source_field === $destination_field) {
        return;
      }

      // Attempt to migrate display suite settings for the existing field.
      $display_suite_settings = $row->getSourceProperty('data/third_party_settings/ds');
      if (isset($display_suite_settings['regions'])) {
        $view_display = $this->entityDisplayRepository->getViewDisplay($entity_type, $bundle, $view_mode);
        $display_suite_settings = $view_display->getThirdPartySettings('ds') + $display_suite_settings;
        $changed = FALSE;
        foreach ($display_suite_settings['regions'] as $region => $fields) {
          if (($index = array_search($source_field, $fields, TRUE)) !== FALSE &&
            !in_array($destination_field, $fields, TRUE)) {
            array_splice($fields, $index, 0, [$destination_field]);
            $display_suite_settings['regions'][$region] = $fields;
            $changed = TRUE;
          }
        }
        if ($changed) {
          $view_display->setThirdPartySetting('ds', 'regions', $display_suite_settings['regions']);
          $view_display->save();
        }
      }
    }
  }

}
