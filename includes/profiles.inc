<?php

/**
 * @file
 * All of the settings profiles handling code for Backup and Migrate.
 */

backup_migrate_include('crud');

/**
 * Get all the available backup profiles.
 */
function backup_migrate_get_profiles() {
  backup_migrate_include('filters');
  static $profiles = NULL;

  // Get the list of profiles and cache them locally.
  if ($profiles === NULL) {
    $profiles = backup_migrate_crud_get_items('profile');
  }
  return $profiles;
}

/**
 * Implementation of hook_backup_migrate_profiles_alter().
 *
 * Add default settings for any plugins which didn't exist when the profile was saved.
 */
function backup_migrate_backup_migrate_profiles_alter(&$profiles) {
  foreach ($profiles as $id => $profile) {
    // Set the default values for filter setting which don't exist in the profile.
    $profiles[$id]->filters = (array)@$profile->filters + (array)backup_migrate_filters_settings_default('backup');
  }
}

/**
 * Get the profile info for the profile with the given ID, or NULL if none exists.
 */
function backup_migrate_get_profile($profile_id) {
  $profiles = backup_migrate_get_profiles();
  return @$profiles[$profile_id];
}

/**
 * Implementation of hook_backup_migrate_profiles().
 */
function backup_migrate_backup_migrate_profiles() {
  $out = array();

  // Get the module default profile.
  $out['default'] = backup_migrate_crud_create_item('profile', array('name' => t("Default Settings"), 'profile_id' => 'default'));

  return $out;
}

/* Utilities */

/**
 * Get the available profiles as an options array for a form item.
 */
function _backup_migrate_get_profile_form_item_options() {
  $out = array();
  foreach ((array)backup_migrate_get_profiles() as $key => $profile) {
    $out[$key] = $profile->get('name');
  }
  return $out;
}

/**
 * Get a form to configure the profile.
 */
function backup_migrate_ui_backup_settings_form($profile) {
  backup_migrate_include('files', 'destinations', 'filters');

  $form = array();

  $form['file'] = array(
    "#type" => "details",
    "#title" => t("Backup File"),
    "#collapsed" => FALSE,
    "#tree" => FALSE,
  );
  $form['file']['filename'] = array(
    "#type" => "textfield",
    "#title" => t("Backup file name"),
    "#default_value" => $profile->filename,
  );
  $form['file']['filename']['#description'] = t('You can use tokens in the file name.');

  $form['file']['token_help'] = array(
    '#title' => t('Replacement patterns'),
    '#type' => 'details',
    '#collapsed' => TRUE,
  );
  $form['file']['token_help']['help'] = array(
    '#theme' => 'token_tree',
    '#token_types' => array('current-date', 'site'),
    '#global_types' => FALSE,
  );

  $form['file']['append_timestamp'] = array(
    "#type" => "checkbox",
    "#title" => t("Append a timestamp."),
    "#default_value" => $profile->append_timestamp,
  );
  $form['file']['timestamp_format'] = array(
    "#type" => "textfield",
    "#title" => t("Timestamp format"),
    "#default_value" => $profile->timestamp_format,
    "#description" => t('Should be a PHP <a href="!url">date()</a> format string.', array('!url' => 'http://www.php.net/date')),
  );

  $form['advanced'] = array('#weight' => 10);
  $form = array_merge_recursive($form, backup_migrate_filters_settings_form($profile->filters, 'backup'));

  // Add the advanced fieldset if there are any fields in it.
  if ($form['advanced']) {
    $form['advanced']['#type'] = 'details';
    $form['advanced']['#title'] = t('Advanced Options');
    $form['advanced']['#collapsed'] = true;
  }

  $form['#attached']['js'][] = array(
    drupal_get_path('module', 'backup_migrate') . '/backup_migrate.js' => array(
      'type' => 'file',
      'scope' => 'footer',
    )
  );
  $form['#attached']['js'][] = array(
    'data' => array('backup_migrate' => array('checkboxLinkText' => t('View as checkboxes'))),
    'type' => 'setting',
  );
  $form['#attached']['css'][] = drupal_get_path('module', 'backup_migrate') . '/backup_migrate.css';


  $form['#validate'][]  = '_backup_migrate_ui_backup_settings_form_validate';
  $form['#submit'][]    = '_backup_migrate_ui_backup_settings_form_submit';

  return $form;
}

/**
 * Validate the profile form.
 */
function _backup_migrate_ui_backup_settings_form_validate($form, &$form_state) {
  backup_migrate_filters_settings_form_validate('backup', $form, $form_state);
}

/**
 * Submit the profile form.
 */
function _backup_migrate_ui_backup_settings_form_submit($form, &$form_state) {
  backup_migrate_filters_settings_form_submit('backup', $form, $form_state);
}
  
/**
 * Get the default profile.
 */
function _backup_migrate_profile_default_profile() {
  backup_migrate_include('files', 'filters');
  return array(
    'source_id' => 'db',
    'filename' => _backup_migrate_default_filename(),
    'append_timestamp' => 1,
    'timestamp_format' => 'Y-m-d\TH-i-s',
    'filters' => backup_migrate_filters_settings_default('backup'),
  );
}

/**
 * Get the default profile saved by the user (or the module default if none exists).
 */
function _backup_migrate_profile_saved_default_profile($profile_id = NULL) {
  $profile_id = $profile_id ? $profile_id : \Drupal::config('backup_migrate.settings')->get('profile_id');
  $profile = NULL;
  if ($profile_id) {
    $profile = backup_migrate_get_profile($profile_id);
  }
  if (!$profile) {
    $profile = backup_migrate_get_profile('default');
  }
  return $profile;
}
