<?php

/**
 * @file
 * Contains \Drupal\backup_migrate\Controller\BackupMigrateController.
 */

namespace Drupal\backup_migrate\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\backup_migrate\Form\BackupMigrateProfileForm;
use Drupal\backup_migrate\Form\BackupMigrateManualBackupForm;

/**
 * Controller for export form.
 */
class BackupMigrateController extends ControllerBase {

  /**
   * Advanced manual backups.
   */
  public function advancedManualBackup() {
    $form = array();

    backup_migrate_include('profiles');
    $profile_id = arg(BACKUP_MIGRATE_MENU_DEPTH + 2);
    $profile = _backup_migrate_profile_saved_default_profile($profile_id);

    return array(
      'profile' => \Drupal::formBuilder()->getForm('Drupal\backup_migrate\Form\BackupMigrateProfileForm', $profile),
      'backup' => \Drupal::formBuilder()->getForm('Drupal\backup_migrate\Form\BackupMigrateManualBackupForm', $profile),
    );
  }

}
