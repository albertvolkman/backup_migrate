<?php

/**
 * @file
 * Contains \Drupal\backup_migrate\Form\BackupMigrateExportForm.
 */

namespace Drupal\backup_migrate\Form;

use Drupal\Core\Form\FormBase;

/**
 * Controller for export form.
 */
class BackupMigrateExportForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'backup_migrate_ui_manual_quick_backup_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $config = \Drupal::config('backup_migrate.settings');

    backup_migrate_include('profiles', 'destinations');

    $form = array();

    $form['quickbackup'] = array(
      '#type' => 'details',
      "#title" => t("Quick Backup"),
      "#collapsed" => FALSE,
      "#tree" => FALSE,
    );

    $form['quickbackup']['source_id'] = _backup_migrate_get_source_pulldown($config->get('source_id'));

    $form['quickbackup']['destination_id'] = array(
      "#type" => "select",
      "#title" => t("Destination"),
      "#options" => _backup_migrate_get_destination_form_item_options('manual backup'),
      "#default_value" => $config->get('destination_id'),
    );
    $profile_options = _backup_migrate_get_profile_form_item_options();
    $form['quickbackup']['profile_id'] = array(
      "#type" => "select",
      "#title" => t("Settings Profile"),
      '#default_value' => $config->get('profile_id'),
      "#options" => $profile_options,
    );

    $form['quickbackup']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Backup now'),
      '#weight' => 1,
    );

    $form['advanced'] = array(
      '#type' => 'markup',
      '#markup' => t('For more backup options, try the <a href="!advancedurl">advanced backup page</a>.', array('!advancedurl' => url(BACKUP_MIGRATE_MENU_PATH . '/export/advanced'))),
    );

    $form['#attached']['js'] = array(
      drupal_get_path('module', 'backup_migrate') .'/backup_migrate.js' => array()
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    if ($form_state['values']['source_id'] == $form_state['values']['destination_id']) {
      form_set_error('destination_id', t('A source cannot be backed up to itself. Please pick a different destination for this backup.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $config = \Drupal::config('backup_migrate.settings');

    backup_migrate_include('profiles', 'destinations');
    if (\Drupal::currentUser()->hasPermission('perform backup')) {
      // For a quick backup use the default settings.
      $settings = _backup_migrate_profile_saved_default_profile($form_state['values']['profile_id']);

      // Set the destination to the one chosen in the pulldown.
      $settings->destination_id = $form_state['values']['destination_id'];
      $settings->source_id = $form_state['values']['source_id'];

      // Save the settings for next time.
      $config->set('source_id', $form_state['values']['source_id']);
      $config->set('destination_id', $form_state['values']['destination_id']);
      $config->set('profile_id', $form_state['values']['profile_id']);

      // Do the backup.
      backup_migrate_ui_manual_backup_perform($settings);
    }
    $form_state['redirect'] = backup_migrate_menu_path;
  }

}
