<?php

/**
 * @file
 * All of the destination handling code needed for Backup and Migrate.
 */

use Drupal\backup_migrate\Item\Destination\DestinationBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

backup_migrate_include('crud');

/**
 * Get the available destination types.
 */
function backup_migrate_get_destination_types() {
  return module_invoke_all('backup_migrate_destination_types');
}

/**
 * Implementation of hook_backup_migrate_destination_types().
 *
 * Get the built in Backup and Migrate destination types.
 */
function backup_migrate_backup_migrate_destination_types() {
  $out = array();
  if (\Drupal::config('backup_migrate.settings')->get('allow_backup_to_file')) {
    $out += array(
      'file' => array(
        'description' => t('Save the backup files to any directory on the server which the web-server can write to.'),
        'file' => drupal_get_path('module', 'backup_migrate') .'/includes/destinations.file.inc',
        'class' => 'Drupal\backup_migrate\Item\Destination\FilesBase',
        'type_name' => t('Server Directory'),
        'can_create' => TRUE,
      ),
      'file_manual' => array(
        'file' => drupal_get_path('module', 'backup_migrate') .'/includes/destinations.file.inc',
        'type_name' => t('Server Directory'),
        'class' => 'Drupal\backup_migrate\Item\Destination\FilesManual',
      ),
      'file_scheduled' => array(
        'file' => drupal_get_path('module', 'backup_migrate') .'/includes/destinations.file.inc',
        'type_name' => t('Server Directory'),
        'class' => 'Drupal\backup_migrate\Item\Destination\FilesScheduled',
      ),
    );
  }
  $out += array(
    'browser_download' => array(
      'file' => drupal_get_path('module', 'backup_migrate') .'/includes/destinations.browser.inc',
      'class' => 'Drupal\backup_migrate\Item\Destination\BrowserDownload',
    ),
    'browser_upload' => array(
      'file' => drupal_get_path('module', 'backup_migrate') .'/includes/destinations.browser.inc',
      'class' => 'Drupal\backup_migrate\Item\Destination\BrowserUpload',
    ),
    'db' => array(
      'type_name' => t('Database'),
      'description' => t('Import the backup directly into another database. Database destinations can also be used as a source to backup from.'),
      'file' => drupal_get_path('module', 'backup_migrate') .'/includes/destinations.db.inc',
      'class' => 'Drupal\backup_migrate\Item\Destination\RemoteDB',
      'can_create' => FALSE,
    ),
    'mysql' => array(
      'type_name' => t('MySQL Database'),
      'description' => t('Import the backup directly into another MySQL database. Database destinations can also be used as a source to backup from.'),
      'file' => drupal_get_path('module', 'backup_migrate') .'/includes/destinations.db.mysql.inc',
      'class' => 'Drupal\backup_migrate\Item\Destination\RemoteDBMysql',
      'can_create' => TRUE,
    ),
    'ftp' => array(
      'description' => t('Save the backup files to any a directory on an FTP server.'),
      'file' => drupal_get_path('module', 'backup_migrate') .'/includes/destinations.ftp.inc',
      'class' => 'Drupal\backup_migrate\Item\Destination\RemoteFtp',
      'type_name' => t('FTP Directory'),
      'can_create' => TRUE,
    ),
    's3' => array(
      'description' => t('Save the backup files to a bucket on your !link.', array('!link' => l(t('Amazon S3 account'), 'http://aws.amazon.com/s3/'))),
      'file' => drupal_get_path('module', 'backup_migrate') .'/includes/destinations.s3.inc',
      'class' => 'Drupal\backup_migrate\Item\Destination\RemoteS3',
      'type_name' => t('Amazon S3 Bucket'),
      'can_create' => TRUE,
    ),
    'email' => array(
      'type_name' => t('Email'),
      'description' => t('Send the backup as an email attachment to the specified email address.'),
      'file' => drupal_get_path('module', 'backup_migrate') .'/includes/destinations.email.inc',
      'class' => 'Drupal\backup_migrate\Item\Destination\Email',
      'can_create' => TRUE,
    ),
  );

  return $out;
}

/**
 * Implementation of hook_backup_migrate_destinations().
 *
 * Get the built in backup destinations and those in the db.
 */
function backup_migrate_backup_migrate_destinations() {
  $out = array();

  $config = \Drupal::config('backup_migrate.settings');

  // Add the default, out of the box destinations for new users.
  if ($config->get('allow_backup_to_file')) {
    $system_config = \Drupal::config('system.file');
    if ($file_private_path = $system_config->get('path.private')) {
      $out['manual'] = backup_migrate_create_destination('file_manual', array('destination_id' => 'manual'));
      $out['scheduled'] = backup_migrate_create_destination('file_scheduled', array('destination_id' => 'scheduled'));
    }
    else {
      _backup_migrate_message('You must specify a private file system path in the !settings to backup to the server.', array('!settings' => l(t('file system settings'), 'admin/config/media/file-system')), 'warning');
    }
  }
  // Add the browser destination for downloading to the desktop.
  if ($config->get('allow_backup_to_download')) {
    $out['download'] = backup_migrate_create_destination('browser_download');
  }
  $out['upload'] = backup_migrate_create_destination('browser_upload');

  // Expose the configured databases as sources.
  backup_migrate_include('filters');
  $out += backup_migrate_filters_invoke_all('destinations');
/*
  global $db_url;
  $urls = is_array($db_url) ? $db_url : array('default' => $db_url);
  foreach ((array)$urls as $key => $url) {
    // Only mysql is currently supported. If more DBMs's are implemented, theis will need to be abstacted.
    if (strpos($url, 'mysql') === 0) {
      if ($destination = backup_migrate_create_destination('mysql', array('url' => $url))) {
        // Treat the default database differently because it is probably the only one available.
        if ($key == 'default') {
          $destination->set_id('db');
          $destination->set_name(t('Default Database'));
          // Dissalow backing up to the default database because that's confusing and potentially dangerous.
          unset($destination->supported_ops['scheduled backup']);
          unset($destination->supported_ops['manual backup']);
        }
        else {
          $destination->set_id('db:'. $key);
          $destination->set_name($key .": ". $destination->get_display_location());
        }
        $out[$destination->get_id()] = $destination;
      }
    }
  }
*/
  return $out;
}

/**
 * Get all the available backup destination.
 *
 * @param $op
 *  The operation which will be performed on the destination. Hooks can use this
 *  to return only those destinations appropriate for the given op.
 *  Options include:
 *    'manual backup' - destinations available for manual backup
 *    'scheduled backup' - destinations available for schedules backup
 *    'list files' - destinations whose backup files can be listed
 *    'restore' - destinations whose files can be restored from
 *    'all' - all available destinations should be returned
 */
function backup_migrate_get_destinations($op = 'all') {
  static $destinations = NULL;

  // Get the list of destinations and cache them locally.
  if ($destinations === NULL) {
    $destinations = backup_migrate_crud_get_items('destination');
  }

  // Return all if that's what was asked for.
  if ($op == 'all') {
    return $destinations;
  }

  // Return only those destinations which support the given op.
  $out = array();
  foreach ($destinations as $key => $destination) {
    if ($destination->op($op)) {
      $out[$key] = $destination;
    }
  }
  return $out;
}

/**
 * Get the destination of the given id.
 */
function backup_migrate_get_destination($id) {
  $destinations = backup_migrate_get_destinations('all');
  return empty($destinations[$id]) ? NULL : $destinations[$id];
}

/**
 * Create a destination object of the given type with the given params.
 */
function backup_migrate_create_destination($destination_type, $params = array()) {
  $params['type'] = $destination_type;
  // Create a new dummy destination to call the create method on because the base item create is not static.
  $destination = new DestinationBase();
  return $destination->create($params);
}

/**
 * Load a file from a destination and return the file info.
 */
function backup_migrate_destination_get_file($destination_id, $file_id) {
  if ($destination = backup_migrate_get_destination($destination_id)) {
    return $destination->load_file($file_id);
  }
  return NULL;
}

/**
 * Check if a file exists in the given destination.
 */
function backup_migrate_destination_file_exists($destination_id, $file_id) {
  if ($destination = backup_migrate_get_destination($destination_id)) {
    return $destination->file_exists($file_id);
  }
  return NULL;
}

/**
 * Send a file to the destination specified by the settings array.
 */
function backup_migrate_destination_save_file($file, &$settings) {
  if ($destination = $settings->get_destination()) {
    $file = $destination->save_file($file, $settings);
    return $file;
  }
  return NULL;
}

/**
 * Delete a file in the given destination.
 */
function backup_migrate_destination_delete_file($destination_id, $file_id) {
  if ($destination = backup_migrate_get_destination($destination_id)) {
    return $destination->delete_file($file_id);
  }
}

/**
 * Get the action links for a file on a given destination.
 */
function _backup_migrate_destination_get_file_links($destination_id, $file_id) {
  $out = array();
  if ($destination = backup_migrate_get_destination($destination_id)) {
    $out = $destination->get_file_links($file_id);
  }
  return $out;
}

/* UI Menu Callbacks */

/**
 * List the backup files in the given destination.
 */
function backup_migrate_ui_destination_display_files($destination_id = NULL) {
  $rows = $sort = array();
  if ($destination = backup_migrate_get_destination($destination_id)) {
    // Refresh the file listing cache if requested.
    if (isset($_GET['refresh'])) {
      $destination->file_cache_clear();
      drupal_goto($_GET['q']);
    }

    drupal_set_title(t('@title Files', array('@title' => $destination->get_name())));
    $headers = array(
      array('data' => t('Filename'), 'field' => 'filename'),
      array('data' => t('Date'), 'field' => 'filetime'),
      array('data' => t('Age'),  'field' => 'filetime', 'sort' => 'desc'),
      array('data' => t('Size'), 'field' => 'filesize'),
    );

    $sort_order = tablesort_get_order($headers);
    $sort_key   = $sort_order['sql'] ? $sort_order['sql'] : 'filetime';
    $sort_dir   = tablesort_get_sort($headers) == 'desc' ? SORT_DESC : SORT_ASC;

    $files      = $destination->list_files();

    $i          = 0;
    $ops        = 0;
    foreach ((array)$files as $file) {
      $info = $file->info();
      $operations = $destination->get_file_links($file->file_id());

      $description = '';
      // Add the description as a new row.
      if (!empty($info['description'])) {
        $description = ' <div title="'.check_plain($info['description']).'" class="backup-migrate-description">'. $info['description'] .'</div>';
      }

      // Show only files that can be restored from.
      if ($file->is_recognized_type()) {
        $sort[] = $info[$sort_key];
        $rows[] = array_merge(array(
          check_plain($info['filename']) . $description,
          format_date($info['filetime'], 'small'),
          format_interval(time() - $info['filetime'], 1),
          format_size($info['filesize']),
        ), $operations);
      }
      $ops = max($ops, count($operations));
    }

    // Add the operations if any
    if ($ops) {
      $headers[] = array('data' => t('Operations'), 'colspan' => $ops);
    }

    array_multisort($sort, $sort_dir, $rows);

    $out = array();
    $out['#attached']['css'][] = array(drupal_get_path('module', 'backup_migrate') . '/backup_migrate.css');

    if ($rows) {
      $out = array(
        '#theme' => 'table',
        '#header' => $header,
        '#rows' => $rows,
      );
    }
    else {
      $out = array(
        '#theme' => 'markup',
	'#markup' => t('There are no backup files to display.'),
      );
    }

    $out = drupal_render($out);

    if ($destination->cache_files && $destination->fetch_time) {
      $out .= '<div class="backup-migrate-cache-time">'. t('This listing was fetched !time ago. !refresh', array('!time' => format_interval(time() - $destination->fetch_time, 1), '!refresh' => l(t('fetch now'), $_GET['q'], array('query' => array('refresh' => 'true'))))) .'</div>';
    }
    return $out;
  }
  drupal_goto(BACKUP_MIGRATE_MENU_PATH . "/destination");
}

/**
 * Download a file to the browser.
 */
function backup_migrate_ui_destination_download_file($destination_id = NULL, $file_id = NULL) {
  if ($file = backup_migrate_destination_get_file($destination_id, $file_id)) {
    backup_migrate_include('files');
    $file->transfer();
  }
  return new RedirectResponse(\Drupal::url('backup_migrate.export'));
}

/**
 * Restore a backup file from a destination.
 */
function backup_migrate_ui_destination_restore_file($destination_id = NULL, $file_id = NULL) {
  if (backup_migrate_destination_file_exists($destination_id, $file_id)) {
    return \Drupal::formBuilder()->getForm('Drupal\backup_migrate\Form\BackupMigrateRestoreFileConfirmForm', $destination_id, $file_id);
//    return drupal_get_form('backup_migrate_ui_destination_restore_file_confirm', $destination_id, $file_id);
  }
  drupal_goto(\Drupal::currentUser()->hasPermission('access backup files') ? BACKUP_MIGRATE_MENU_PATH . "/destination/list/files/". $destination_id : BACKUP_MIGRATE_MENU_PATH);
}

/**
 * Ask confirmation for file restore.
 */
function backup_migrate_ui_destination_restore_file_confirm($form, &$form_state, $destination_id, $file_id) {
  $sources = _backup_migrate_get_destination_form_item_options('source');
  if (count($sources) > 1) {
    $form['source_id'] = array(
      "#type" => "select",
      "#title" => t("Database"),
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

  $form['destination_id'] = array('#type' => 'value', '#value' => $destination_id);
  $form['file_id'] = array('#type' => 'value', '#value' => $file_id);
  $form = confirm_form($form, t('Are you sure you want to restore the database?'), BACKUP_MIGRATE_MENU_PATH . "/destination/list/files/". $destination_id, t('Are you sure you want to restore the database from the backup file %file_id? This will delete some or all of your data and cannot be undone. <strong>Always test your backups on a non-production server!</strong>', array('%file_id' => $file_id)), t('Restore'), t('Cancel'));
  drupal_set_message(t('Restoring will delete some or all of your data and cannot be undone. <strong>Always test your backups on a non-production server!</strong>'), 'warning', FALSE);
  $form = array_merge_recursive($form, backup_migrate_filters_settings_form(backup_migrate_filters_settings_default('restore'), 'restore'));
  $form['actions']['#weight'] = 100;

  // Add the advanced fieldset if there are any fields in it.
  if (@$form['advanced']) {
    $form['advanced']['#type'] = 'details';
    $form['advanced']['#title'] = t('Advanced Options');
    $form['advanced']['#collapsed'] = true;
  }

  return $form;
}

/**
 * Do the file restore.
 */
function backup_migrate_ui_destination_restore_file_confirm_submit($form, &$form_state) {
  $destination_id = $form_state['values']['destination_id'];
  $file_id = $form_state['values']['file_id'];
  if ($destination_id && $file_id) {
    backup_migrate_perform_restore($destination_id, $file_id, $form_state['values']);
  }
  $redir = \Drupal::currentUser()->hasPermission('access backup files') ? BACKUP_MIGRATE_MENU_PATH . "/destination/list/files/". $destination_id : BACKUP_MIGRATE_MENU_PATH;
  $form_state['redirect'] = $redir;
}

/**
 * Menu callback to delete a file from a destination.
 */
function backup_migrate_ui_destination_delete_file($destination_id = NULL, $file_id = NULL) {
  if (backup_migrate_destination_file_exists($destination_id, $file_id)) {
    return \Drupal::formBuilder()->getForm('Drupal\backup_migrate\Form\BackupMigrateDeleteFileConfirmForm', $destination_id, $file_id);
//    return drupal_get_form('backup_migrate_ui_destination_delete_file_confirm', $destination_id, $file_id);
  }
  return new RedirectResponse(\Drupal::url('backup_migrate.export'));
}

/**
 * Ask confirmation for file deletion.
 */
function backup_migrate_ui_destination_delete_file_confirm($form, &$form_state, $destination_id, $file_id) {
  $form['destination_id'] = array('#type' => 'value', '#value' => $destination_id);
  $form['file_id'] = array('#type' => 'value', '#value' => $file_id);
  return confirm_form($form, t('Are you sure you want to delete the backup file?'), BACKUP_MIGRATE_MENU_PATH . '/destination/list/files/'. $destination_id, t('Are you sure you want to delete the backup file %file_id? <strong>This action cannot be undone.</strong>', array('%file_id' => $file_id)), t('Delete'), t('Cancel'));
}

/**
 * Delete confirmed, perform the delete.
 */
function backup_migrate_ui_destination_delete_file_confirm_submit($form, &$form_state) {
  if (\Drupal::currentUser()->hasPermission('delete backup files')) {
    $destination_id = $form_state['values']['destination_id'];
    $file_id = $form_state['values']['file_id'];
    backup_migrate_destination_delete_file($destination_id, $file_id);
    _backup_migrate_message('Database backup file deleted: %file_id', array('%file_id' => $file_id));
  }
  $form_state['redirect'] = \Drupal::currentUser()->hasPermission('access backup files') ? BACKUP_MIGRATE_MENU_PATH . "/destination/list/files/". $destination_id : BACKUP_MIGRATE_MENU_PATH;
}

/* Utilities */

/**
 * Get the source options as a form element.
 */
function _backup_migrate_get_source_form($source_id = 'db') {
  backup_migrate_include('destinations');

  $form = array();
  $sources = _backup_migrate_get_source_pulldown($source_id);
  if (count($sources['#options']) > 1) {
    $form['source'] = array(
      "#type" => "details",
      "#title" => t("Backup Source"),
      "#collapsed" => FALSE,
      "#tree" => FALSE,
    );
    $sources['#description'] = t("Choose the database to backup. Any database destinations you have created and any databases specified in your settings.php can be backed up.");

    $form['source']['source_id'] = $sources;
  }
  else {
    $form = array();
    $form['source']['source_id'] = array(
      "#type" => "value",
      "#value" => $source_id,
    );
  }
  return $form;
}

/**
 * Get pulldown to select existing source options.
 */
function _backup_migrate_get_source_pulldown($source_id = NULL) {
  backup_migrate_include('destinations');
  $sources = _backup_migrate_get_destination_form_item_options('source');
  $form = array(
    "#type" => "select",
    "#title" => t("Backup Source"),
    "#options" => _backup_migrate_get_destination_form_item_options('source'),
    "#default_value" => $source_id,
  );
  return $form;
}

/**
 * Get the destination options as an options array for a form item.
 */
function _backup_migrate_get_destination_form_item_options($op) {
  $out = array();
  foreach (backup_migrate_get_destinations($op) as $key => $destination) {
    $out[$key] = $destination->get_name();
  }
  return $out;
}

