<?php

/**
 * @file
 * Contains \Drupal\backup_migrate\Form\BackupMigrateRestoreForm.
 */

namespace Drupal\backup_migrate\Form;

use Drupal\Core\Form\FormBase;

/**
 * Controller for export form.
 */
class BackupMigrateRestoreForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'backup_migrate_ui_manual_restore_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    backup_migrate_include('filters', 'destinations');

    $form = array();

    $sources = _backup_migrate_get_destination_form_item_options('source');
    if (count($sources) > 1) {
      $form['source_id'] = array(
        "#type" => "select",
        "#title" => t("Restore to"),
        "#options" => _backup_migrate_get_destination_form_item_options('source'),
        "#description" => t("Choose the database to restore to. Any database destinations you have created and any databases specified in your settings.php can be restored to."),
        "#default_value" => 'db',
      );
    }
    else {
      $form['source_id'] = array(
        "#type" => "value",
        "#value" => 'db',
      );
    }

    $form['backup_migrate_restore_upload'] = array(
      '#title' => t('Upload a Backup File'),
      '#type' => 'file',
      '#description' => t("Upload a backup file created by this version of this module. For other database backups please use another tool for import. Max file size: %size", array("%size" => format_size(file_upload_max_size()))),
    );
    drupal_set_message(t('Restoring will delete some or all of your data and cannot be undone. <strong>Always test your backups on a non-production server!</strong>'), 'warning', FALSE);

    $form = array_merge_recursive($form, backup_migrate_filters_settings_form(backup_migrate_filters_settings_default('restore'), 'restore'));
    // Add the advanced fieldset if there are any fields in it.
    if (@$form['advanced']) {
      $form['advanced']['#type'] = 'details';
      $form['advanced']['#title'] = t('Advanced Options');
      $form['advanced']['#collapsed'] = true;
    }

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Restore now'),
    );
    if (\Drupal::currentUser()->hasPermission('access backup files')) {
      $form[] = array(
        '#type' => 'markup',
        '#markup' => t('<p>Or you can restore one of the files in your <a href="!url">saved backup destinations.</a></p>', array("!url" => url(BACKUP_MIGRATE_MENU_PATH . "/destination"))),
      );
    }
    $form['#attributes'] = array('enctype' => 'multipart/form-data');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $validators = array('file_validate_extensions' => array('gz zip sql mysql bz bz2'));
    if ($file = file_save_upload('backup_migrate_restore_upload', $validators)) {
      backup_migrate_include('destinations');
      backup_migrate_perform_restore('upload', $file->uri, $form_state['values']);
    }
    $form_state['redirect'] = BACKUP_MIGRATE_MENU_PATH . '/restore';
  }

}
