<?php

namespace Drupal\field_collection_to_paragraphs\EventSubscriber;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\migrate\Plugin\migrate\destination\EntityContentBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Detects publication state changes for Node/Taxonomy term entities.
 */
class EntityChangeMigrationSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;
  use MessengerTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a EntityChangeMigrationSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
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
   */
  public function postRowSave(MigratePostRowSaveEvent $event) {
    $migration = $event->getMigration();
    $destination_plugin = $migration->getDestinationPlugin();

    if (!$destination_plugin instanceof EntityContentBase ||
      empty($migration->getDestinationConfiguration()['field_collection_track_status_change'])) {
      // Opt-in behaviour.
      return;
    }

    // Check the entity status.
    $plugin_id = $destination_plugin->getPluginId();
    $parts = explode(':', $plugin_id);
    $entity_type = NULL;
    if (count($parts) > 1) {
      // Assume the entity bundle is the last part.
      $entity_type = end($parts);
    }

    if (!$entity_type || !in_array($entity_type, ['node', 'taxonomy_term'])) {
      // Unsupported entity type.
      return;
    }

    if (!$event->getDestinationIdValues() ||
      !$event->getRow()->hasSourceProperty('status')) {
      return;
    }

    try {
      $entity_storage = $this->entityTypeManager->getStorage($entity_type);
      $entity = $entity_storage
        ->load($event->getDestinationIdValues()[0]);
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      return;
    }

    if ($entity === NULL) {
      return;
    }

    $old_status = (bool) $event->getRow()->getSourceProperty('status');
    $new_status = (bool) $entity->get('status')->value;

    if ($old_status !== $new_status) {
      // The published status changed for some reason, most likely due to some
      // postSave callback hook. Warn the user.
      // e.g in workbench_moderation if a node was originally in draft mode
      // and it's saved, it unpublishes the node.
      $publish_status = $new_status ? 'published' : 'unpublished';

      try {
        $entity_url = $entity->toUrl('edit-form')->toString();
      }
      catch (EntityMalformedException $e) {
        $entity_url = '#no_url';
      }

      $message = $this->t('@entity_type #@id <a href=":entity_url">@entity</a> was %status, most likely because of an update hook.', [
        '@entity_type' => $entity_storage->getEntityType()->getLabel(),
        '@id' => $entity->id(),
        '@entity' => $entity->label(),
        ':entity_url' => $entity_url,
        '%status' => $publish_status,
      ]);

      $this->messenger()->addWarning($message);

      $source_id_values = $event->getRow()->getSourceIdValues();

      $previous_messages = $migration->getIdMap()
        ->getMessages($source_id_values, MigrationInterface::MESSAGE_WARNING);

      $previous_message = '';
      foreach ($previous_messages as $item) {
        $previous_message .= $item . ' | ';
      }

      // Store the error message.
      $migration->getIdMap()
        ->saveMessage($source_id_values, $previous_message . $message,
          MigrationInterface::MESSAGE_WARNING);
    }
  }

}
