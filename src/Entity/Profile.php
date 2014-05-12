<?php

/**
 * @file
 * Contains \Drupal\backup_migrate\Entity\Profile.
 */

namespace Drupal\backup_migrate\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Profile entity.
 *
 * @ConfigEntityType(
 *   id = "backup_migrate_profile",
 *   label = @Translation("Profile"),
 *   controllers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigEntityStorage",
 *     "list_builder" = "Drupal\backup_migrate\Controller\ProfileListBuilder",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "form" = {
 *       "add" = "Drupal\backup_migrate\Form\ProfileForm",
 *       "edit" = "Drupal\backup_migrate\Form\ProfileEditForm",
 *       "delete" = "Drupal\backup_migrate\Form\ProfileDeleteForm"
 *     }
 *   },
 *   config_prefix = "profile",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "edit-form" = "backup_migrate_profile.edit",
 *     "delete-form" = "backup_migrate_profile.delete"
 *   }
 * )
 */
class Profile extends ConfigEntityBase {
  /**
   * The Profile ID.
   *
   * @var string
   */
  public $id;

  /**
   * The Profile UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * The Profile label.
   *
   * @var string
   */
  public $label;

  /**
   * The Profile label.
   *
   * @var string
   */
  public $filename;

  /**
   * The option to append the timestamp to the filename.
   *
   * @var string
   */
  public $append_timestamp;

  /**
   * The timestamp format.
   *
   * @var string
   */
  public $timestamp_format;
}
