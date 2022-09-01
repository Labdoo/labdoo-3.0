<?php

namespace Drupal\registration;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\registration\Entity\RegistrationInterface;
use Drupal\registration\Entity\RegistrationSettings;
use Drupal\registration\Entity\RegistrationTypeInterface;

/**
 * Defines the interface for the host entity.
 *
 * This is a pseudo-entity wrapper around a real entity. It provides a
 * mechanism for extending the functionality of content entities without
 * having to override the content entity base class.
 */
interface HostEntityInterface {

  /**
   * Gets the bundle of the wrapped entity.
   *
   * This is a machine name, e.g., "event".
   *
   * @return string
   *   The bundle of the wrapped entity. Defaults to the entity type ID if the
   *   entity type does not make use of different bundles.
   */
  public function bundle(): string;

  /**
   * Gets the wrapped real entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The wrapped real entity.
   */
  public function getEntity(): EntityInterface;

  /**
   * Gets the ID of the type of the wrapped entity.
   *
   * This is a machine name, e.g., "node".
   *
   * @return string
   *   The entity type ID of the wrapped entity.
   */
  public function getEntityTypeId(): string;

  /**
   * Gets the identifier of the wrapped entity.
   *
   * @return string|int|null
   *   The entity identifier, or NULL if the object does not yet have an
   *   identifier.
   */
  public function id(): string|int|NULL;

  /**
   * Determines whether the wrapped entity is new.
   *
   * Usually an entity is new if no ID exists for it yet. However, entities may
   * be enforced to be new with existing IDs too.
   *
   * @return bool
   *   TRUE if the entity is new, or FALSE if the entity has already been saved.
   *
   * @see \Drupal\Core\Entity\EntityInterface::enforceIsNew()
   */
  public function isNew(): bool;

  /**
   * Gets the label of the wrapped entity.
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   The label of the wrapped entity, or NULL if there is no label defined.
   */
  public function label(): string|TranslatableMarkup|NULL;

  /**
   * Adds cache information to a render array.
   *
   * @param array $build
   *   The render array to modify.
   * @param \Drupal\Core\Entity\EntityInterface[] $other_entities
   *   (optional) Other entities that should be added as dependencies.
   */
  public function addCacheableDependencies(array &$build, array $other_entities = []);

  /**
   * Generates a sample registration for use in tests and email preview.
   *
   * The registration is created but not saved, so it is ephemeral unless
   * the caller subsequently saves it. Saving it is not recommended.
   *
   * @return \Drupal\registration\Entity\RegistrationInterface
   *   The generated registration.
   */
  public function generateSampleRegistration(bool $save = FALSE): RegistrationInterface;

  /**
   * Gets the reserved spaces in active registrations.
   *
   * Includes active and held states.
   *
   * @param \Drupal\registration\Entity\RegistrationInterface|null $registration
   *   (optional) If set, an existing registration to exclude from the count.
   *
   * @return int
   *   The total number of reserved spaces for active registrations.
   */
  public function getActiveSpacesReserved(RegistrationInterface $registration = NULL): int;

  /**
   * Gets the default registration settings.
   *
   * @param string|null $langcode
   *   (optional) The language for the settings field.
   *   If not set, the host entity language is used.
   *
   * @return array
   *   The default registration settings for.
   */
  public function getDefaultSettings(string $langcode = NULL): array;

  /**
   * Gets the total number of registrations.
   *
   * Note that this is the number of registrations, not the spaces reserved.
   *
   * @return int
   *   The count of registrations (any status).
   */
  public function getRegistrationCount(): int;

  /**
   * Gets the definition of the registration field.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface|null
   *   The field definition, if available.
   */
  public function getRegistrationField(): ?FieldDefinitionInterface;

  /**
   * Gets the list of registrations.
   *
   * @param array $states
   *   (optional) An array of state IDs to filter on.
   *   For example: ['completed', 'held'].
   *
   * @return \Drupal\registration\Entity\Registration[]
   *   The list of registrations.
   */
  public function getRegistrationList(array $states = []): array;

  /**
   * Gets the registration type.
   *
   * @return \Drupal\registration\Entity\RegistrationTypeInterface|null
   *   The registration type, if available.
   */
  public function getRegistrationType(): ?RegistrationTypeInterface;

  /**
   * Gets the value of the registration type field.
   *
   * This is a Registration Type bundle machine name.
   *
   * @return string|null
   *   The bundle, if available.
   */
  public function getRegistrationTypeBundle(): ?string;

  /**
   * Gets a settings value for a given key.
   *
   * @param string $key
   *   The setting name, for example "status", "reminder date" etc.
   *
   * @return mixed
   *   The setting value. The data type depends on the key.
   */
  public function getSetting(string $key): mixed;

  /**
   * Gets the registration settings entity.
   *
   * @return \Drupal\registration\Entity\RegistrationSettings|null
   *   The settings entity. A new entity is created (but not saved) if needed.
   */
  public function getSettings(): ?RegistrationSettings;

  /**
   * Determines if a host entity has spaces remaining.
   *
   * @param int $spaces
   *   (optional) The number of spaces requested. Defaults to 1.
   * @param \Drupal\registration\Entity\RegistrationInterface|null $registration
   *   (optional) If set, an existing registration to exclude from the count.
   *
   * @return bool
   *   TRUE if there are spaces remaining, FALSE otherwise.
   */
  public function hasRoom(int $spaces = 1, RegistrationInterface $registration = NULL): bool;

  /**
   * Determines whether new registrations are allowed.
   *
   * This checks to make sure registrations are enabled in the settings, and
   * ensures new registrations would occur within the open and close dates if
   * those are set. If those checks pass and the host entity has room for
   * more registrations, then new registrations are allowed.
   *
   * @param int $spaces
   *   (optional) The number of spaces requested. Defaults to 1.
   * @param \Drupal\registration\Entity\RegistrationInterface|null $registration
   *   (optional) If set, an existing registration to exclude from the count.
   * @param array $errors
   *   (optional) If set, any error messages are set into this array.
   *
   * @return bool
   *   TRUE if new registrations are allowed, FALSE otherwise.
   */
  public function isEnabledForRegistration(int $spaces = 1, RegistrationInterface $registration = NULL, array &$errors = []): bool;

  /**
   * Determines whether an email address is already registered.
   *
   * This checks the anonymous email field only. To check if a Drupal
   * user account has registered, use the isUserRegistered function.
   *
   * @param string $email
   *   The email address to check.
   *
   * @return bool
   *   TRUE if the email address has already registered for the host entity.
   */
  public function isEmailRegistered(string $email): bool;

  /**
   * Determines whether a given user is already registered.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return bool
   *   TRUE if the user has already registered for the host entity.
   */
  public function isUserRegistered(AccountInterface $account): bool;

}
