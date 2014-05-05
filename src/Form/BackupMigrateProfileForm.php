<?php

/**
 * @file
 * Contains \Drupal\backup_migrate\Form\BackupMigrateProfileForm.
 */

namespace Drupal\backup_migrate\Form;

use Drupal\Core\Form\FormBase;

/**
 * Controller for export form.
 */
class BackupMigrateProfileForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'backup_migrate_profile_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $profile = $form_state['build_info']['args'][0];
    $form = array();

    $profile_options = _backup_migrate_get_profile_form_item_options();
    if (count($profile_options) > 0) {
      $profile_options = array(0 => t('-- Select a Settings Profile --')) + $profile_options;
      $form['profile'] = array(
        "#title" => t("Settings Profile"),
        "#prefix" => '<div class="container-inline">',
        "#suffix" => '</div>',
        "#tree" => FALSE,
        "#description" => t("You can load a profile. Any changes you made below will be lost."),
      );
      $form['profile']['profile_id'] = array(
        "#type" => "select",
        "#title" => t("Load Settings"),
        '#default_value' => is_object($profile) ? $profile->get_id() : 0,
        "#options" => $profile_options,
      );
      $form['profile']['load_profile'] = array(
        '#type' => 'submit',
        '#value' => t('Load Profile'),
      );
    }

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
    $config = \Drupal::config('backup_migrate.settings');

    if ($profile = backup_migrate_get_profile($form_state['values']['profile_id'])) {
      $config->set('profile_id', $profile->get_id());
      $form_state['redirect'] = BACKUP_MIGRATE_MENU_PATH . '/advanced';
    }
    else {
      $config->set('profile_id', NULL);
    }
  }

}
