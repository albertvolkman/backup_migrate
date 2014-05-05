<?php

/**
 * @file
 * Contains \Drupal\backup_migrate\Controller\BackupMigrateCrudController.
 */

namespace Drupal\backup_migrate\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\backup_migrate\Form\BackupMigrateCrudEditForm;
use Drupal\backup_migrate\Form\BackupMigrateProfileForm;
use Drupal\backup_migrate\Form\BackupMigrateManualBackupForm;

/**
 * Controller for export form.
 */
class BackupMigrateCrudController extends ControllerBase {

  function __construct() {
    module_load_include('inc', 'backup_migrate', 'includes/crud');
  }

  /**
   * List all items.
   */
  public function listAll() {
    $out = '';
    if ($type = backup_migrate_crud_type_load(arg(BACKUP_MIGRATE_MENU_DEPTH))) {
      $out = $type->get_list();
    }
    return $out;
  }

  /**
   * Add a new item.
   */
  public function add() {
    if ($type = backup_migrate_crud_type_load(arg(BACKUP_MIGRATE_MENU_DEPTH))) {
      $item = $type->create(array());
      return \Drupal::formBuilder()->getForm('Drupal\backup_migrate\Form\BackupMigrateCrudEditForm', $item);
    }
  }

  /**
   * Delete an item.
   */
  public function delete($item_id = NULL) {
    if ($type = backup_migrate_crud_type_load(arg(BACKUP_MIGRATE_MENU_DEPTH))) {
      if ($item_id && $item = $type->item($item_id)) {
        return \Drupal::formBuilder()->getForm('Drupal\backup_migrate\Form\BackupMigrateCrudDeleteConfirmForm', $item);
      }
      drupal_goto('admin/content/backup_migrate/'. arg(BACKUP_MIGRATE_MENU_DEPTH));
//      $this->redirect()
    }
  }

  /**
   * Edit an item.
   */
  public function edit($item_id = NULL) {
    if ($type = backup_migrate_crud_type_load(arg(BACKUP_MIGRATE_MENU_DEPTH))) {
      if ($item_id && $item = $type->item($item_id)) {
        return \Drupal::formBuilder()->getForm('Drupal\backup_migrate\Form\BackupMigrateCrudEditForm', $item);
      }
      drupal_goto(BACKUP_MIGRATE_MENU_PATH. '/' . arg(BACKUP_MIGRATE_MENU_DEPTH));
//      $this->redirect()
    }
  }

  /**
   * Export an item.
   */
  public function export($item_id = NULL) {
    if ($type = backup_migrate_crud_type_load(arg(BACKUP_MIGRATE_MENU_DEPTH))) {
      if ($item_id && $item = $type->item($item_id)) {
        return \Drupal::formBuilder()->getForm('Drupal\backup_migrate\Form\BackupMigrateExportForm', $item);
      }
      drupal_goto(BACKUP_MIGRATE_MENU_PATH . '/' . arg(BACKUP_MIGRATE_MENU_DEPTH));
//      $this->redirect()
    }
  }

}
