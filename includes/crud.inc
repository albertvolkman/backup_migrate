<?php

/**
 * @file
 * CRUD functions for backup and migrate types (schedules, profiles etc.).
 */

define('BACKUP_MIGRATE_STORAGE_NONE', 0);
define('BACKUP_MIGRATE_STORAGE_DB', 1);
define('BACKUP_MIGRATE_STORAGE_OVERRIDEN', 2);

/**
 * Return a list of CRUD types in the module.
 */
function backup_migrate_crud_types() {
  $out = array(
    'destination' => array(
      'class' => 'Drupal\backup_migrate\Item\Destination\DestinationBase',
      'include' => 'destinations',
    ),
    'profile' => array(
      'class' => 'Drupal\backup_migrate\Item\Profile',
      'include' => 'profiles',
    ),
    'schedule' => array(
      'class' => 'Drupal\backup_migrate\Item\Schedule',
      'include' => 'schedules',
    ),
  );
  return $out;
}

/**
 * Get a generic object of the given type to be used for static-like functions.
 *
 * I'm not using actual static method calls since they don't work on variables prior to PHP 5.3.0
 */
function backup_migrate_crud_type_load($type) {
  $out = NULL;
  $types = backup_migrate_crud_types();
  if (!empty($types[$type])) {
    $info = $types[$type];
    if ($info['include']) {
      backup_migrate_include($info['include']);
    }
    $out = new $info['class'];
  }
  return $out;
}


/**
 * Get all items of the given type.
 */
function backup_migrate_crud_get_items($type) {
  if ($type = backup_migrate_crud_type_load($type)) {
    return $type->all_items();
  }
}

/**
 * Get an item of the specified type.
 */
function backup_migrate_crud_get_item($type, $id) {
  if ($type = backup_migrate_crud_type_load($type)) {
    return $type->item($id);
  }
}

/**
 * Create a new item of the given type.
 */
function backup_migrate_crud_create_item($type, $params) {
  if ($type = backup_migrate_crud_type_load($type)) {
    return $type->create($params);
  }
}
