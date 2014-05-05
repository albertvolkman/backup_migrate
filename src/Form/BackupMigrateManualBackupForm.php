<?php

/**
 * @file
 * Contains \Drupal\backup_migrate\Form\BackupMigrateManualBackupForm.
 */

namespace Drupal\backup_migrate\Form;

use Drupal\Core\Form\FormBase;

/**
 * Controller for export form.
 */
class BackupMigrateManualBackupForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'backup_migrate_manual_backup_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $config = \Drupal::config('backup_migrate.settings');

    $profile = $form_state['build_info']['args'][0];

    $form = array();

    $form += _backup_migrate_get_source_form('db');
    $form += backup_migrate_ui_backup_settings_form($profile);

    $form['profile_id'] = array(
      "#type" => "value",
      '#default_value' => $profile->get_id(),
    );
    $form['storage'] = array(
      "#type" => "value",
      '#default_value' => $profile->storage,
    );
    $form['destination'] = array(
      '#type' => 'details',
      "#title" => t("Backup Destination"),
      "#collapsed" => FALSE,
      "#tree" => FALSE,
      "#description" => t("Choose where the backup file will be saved. Backup files contain sensitive data, so be careful where you save them. Select 'Download' to download the file to your desktop."),
      '#weight' => 70,
    );
    $form['destination']['destination_id'] = array(
      "#type" => "select",
      "#title" => t("Destination"),
      "#options" => _backup_migrate_get_destination_form_item_options('manual backup'),
      "#default_value" => $config->get('destination_id'),
    );
    if (\Drupal::currentUser()->hasPermission('administer backup and migrate')) {
      $form['destination']['destination_id']['#description'] = l(t("Create new destination"), BACKUP_MIGRATE_MENU_PATH . "/destination/add");
      $form['save_settings'] = array(
        "#type" => "checkbox",
        "#title" => t('Save these settings.'),
        "#default_value" => FALSE,
        '#weight' => 80,
      );
      $form['save_options'] = array(
        '#prefix' => '<div class="backup-migrate-save-options">',
        '#suffix' => '</div>',
        '#weight' => 90,
      );
      $name = array(
        '#default_value' => $profile->get('name'),
        '#type' => 'textfield',
        '#title' => t('Save the settings as'),
      );

      if ($profile->get_id()) {
        $form['save_options']['create_new'] = array(
          '#default_value' => $profile->get('name'),
          '#type' => 'radios',
          '#default_value' => 0,
          '#options' => array(
            0 => t("Replace the '%profile' profile", array('%profile' => $profile->get('name'))),
            1 => t('Create new profile'),
          ),
        );

        $name["#title"] = t('Profile name');
        $name["#description"] = t("This will be the name of your new profile if you select 'Create new profile' otherwise it will become the name of the '%profile' profile.", array('%profile' => $profile->get('name')));
      }
      else {
        $name["#title"] = t('Save the settings as');
        $name["#description"] = t('Pick a name for the settings. Your settings will be saved as a profile and will appear in the <a href="!url">Profiles Tab</a>.', array('!url' => url(BACKUP_MIGRATE_MENU_PATH . '/profile')));
        $name["#default_value"] = t('Untitled Profile');
      }
      $form['save_options']['name'] = $name;
      $form['save_options'][] = array(
        '#type' => 'submit',
        '#value' => t('Save Without Backing Up'),
      );
    }

    $form['save_options'][] = array(
      '#type' => 'submit',
      '#value' => t('Backup now'),
      '#weight' => 100,
    );

    $form['#attached']['js'] = array(
      drupal_get_path('module', 'backup_migrate') .'/backup_migrate.js' => array('type' => 'module', 'scope' => 'footer')
    );


    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    if ($form_state['values']['source_id'] == $form_state['values']['destination_id']) {
      $this->setFormError('destination_id', t('A source cannot be backed up to itself. Please pick a different destination for this backup.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $config = \Drupal::config('backup_migrate.settings');

    // Save the settings profile if the save box is checked.
//    $form_state['values']['nodata_tables'] = array_filter((array)$form_state['values']['nodata_tables']);
//    $form_state['values']['exclude_tables'] = array_filter((array)$form_state['values']['exclude_tables']);

    $profile = backup_migrate_crud_create_item('profile', $form_state['values']);

    // Save the settings profile if the save box is checked.
    if ($form_state['values']['save_settings'] && \Drupal::currentUser()->hasPermission('administer backup and migrate')) {
      if (@$form_state['values']['create_new']) {
        // Reset the id and storage so a new item will be saved.
        $profile->set_id(NULL);
        $profile->storage = BACKUP_MIGRATE_STORAGE_NONE;
      }
      $profile->save();
      $config->set('profile_id', $profile->get_id());
      $config->set('destination_id', $form_state['values']['destination_id']);
    }

    // Perform the actual backup if that is what was selected.
    if ($form_state['values']['op'] == t('Backup now') && \Drupal::currentUser()->hasPermission('perform backup')) {
      backup_migrate_ui_manual_backup_perform($profile);
    }
    $form_state['redirect'] = BACKUP_MIGRATE_MENU_PATH . "/export/advanced";
  }

}
