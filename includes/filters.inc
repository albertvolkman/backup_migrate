<?php

/**
 * @file
 * All of the filter handling code needed for Backup and Migrate.
 */

use backup_migrate\src\Filter;

/**
 * Get the available destination types.
 */
function backup_migrate_get_filters($op = NULL) {
  static $filters = NULL;
  if ($filters === NULL) {
    $filters = array();
    $definitions = module_invoke_all('backup_migrate_filters');
    foreach ($definitions as $definition) {
      $filters[] = new $definition['class'];
    }
  }
  $sort = array();
  // Sort the filters based on the weight for the given operation.
  foreach ($filters as $filter) {
    $sort[] = $filter->weight($op);
  }
  array_multisort($sort, SORT_ASC, SORT_NUMERIC, $filters);
  return $filters;
}

/**
 * Implementation of hook_backup_migrate_filters().
 *
 * Get the built in Backup and Migrate filters.
 */
function backup_migrate_backup_migrate_filters() {
  return array(
    'backup_restore' => array(
      'file' => drupal_get_path('module', 'backup_migrate') .'/includes/filters.backup_restore.inc',
      'class' => 'Drupal\backup_migrate\Filter\BackupRestore',
    ),
    'compression' => array(
      'file' => drupal_get_path('module', 'backup_migrate') .'/includes/filters.compression.inc',
      'class' => 'Drupal\backup_migrate\Filter\Compression',
    ),
    'encryption' => array(
      'file' => drupal_get_path('module', 'backup_migrate') .'/includes/filters.encryption.inc',
      'class' => 'Drupal\backup_migrate\Filter\Encryption',
    ),
    'statusnotify' => array(
      'file' => drupal_get_path('module', 'backup_migrate') .'/includes/filters.statusnotify.inc',
      'class' => 'Drupal\backup_migrate\Filter\StatusNotify',
    ),
    'utils' => array(
      'file' => drupal_get_path('module', 'backup_migrate') .'/includes/filters.utils.inc',
      'class' => 'Drupal\backup_migrate\Filter\FilterBase',
    ),
  );
}

/**
 * Invoke the given method on all of the available filters.
 */
function backup_migrate_filters_invoke_all() {
  $args    = func_get_args();
  $op      = array_shift($args);
  $out     = array();
  $filters = backup_migrate_get_filters($op);
  foreach ($filters as $filter) {
    if (method_exists($filter, $op)) {
      /* call_user_func_array() ignores the function signature, so we cannot
       * use it to pass references. (Call-time pass-by-reference is deprecated
       * in PHP5.3.) Work around it, since we have unknown function signatures.
       */
      switch (count($args)) {
        case 1:
          $ret = $filter->$op($args[0]);
          break;

        case 2:
          $ret = $filter->$op($args[0], $args[1]);
          break;

        default:
          // This assumes that no functions with more than 2 arguments expect a
          // reference as argument. If so, add another 'case block'.
          $ret = call_user_func_array(array($filter, $op), $args);
      }
      $out = array_merge_recursive($out, (array) $ret);
    }
  }
  return $out;
}

/**
 * Filter a backup file before sending it to the destination.
 */
function backup_migrate_filters_backup($file, &$settings) {
  $filters = backup_migrate_get_filters('backup');
  foreach ($filters as $filter) {
    if ($file) {
      $file = $filter->backup($file, $settings);
    }
  }
  return $file;
}

/**
 * Filter a backup file before sending it to the destination.
 */
function backup_migrate_filters_restore($file, &$settings) {
  $filters = backup_migrate_get_filters('restore');
  foreach ($filters as $filter) {
    if ($file) {
      $file = $filter->restore($file, $settings);
    }
  }
  return $file;
}

/**
 * Get the backup settings for all of the filters.
 */
function backup_migrate_filters_settings_form($settings, $op) {
  $out = backup_migrate_filters_invoke_all($op .'_settings_form', $settings);
  $out = backup_migrate_filters_settings_form_set_parents($out);
  return $out;
}

/**
 * Add a form parent to the filter settings so that the filter values are saved in the right table.
 */
function backup_migrate_filters_settings_form_set_parents($form) {
  foreach (\Drupal\Core\Render\Element::children($form) as $key) {
    if (!isset($form[$key]['#parents'])) {
      $form[$key]['#parents'] = array('filters', $key);
      $form[$key] = backup_migrate_filters_settings_form_set_parents($form[$key]);
    }
  }
  return $form;
}


/**
 * Validate all the filters.
 */
function backup_migrate_filters_settings_form_validate($op, $form, &$form_state) {
  //backup_migrate_filters_invoke_all($op .'_settings_form_validate', $form, $form_state);
}

/**
 * Submit all of the filters.
 */
function backup_migrate_filters_settings_form_submit($op, $form, &$form_state) {
  //backup_migrate_filters_invoke_all($op .'_settings_form_submit', $form, $form_state);
}


/**
 * Get the default settings for the filters.
 */
function backup_migrate_filters_settings_default($op) {
  return backup_migrate_filters_invoke_all($op .'_settings_default');
}

/**
 * Get the file types for all of the filters.
 */
function backup_migrate_filters_file_types() {
  return backup_migrate_filters_invoke_all('file_types');
}
