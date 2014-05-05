<?php

/**
 * @file
 * General file handling code for Backup and Migrate.
 */

define('BACKUP_MIGRATE_FILENAME_MAXLENGTH', 255);

/**
 * Add a file to the temporary files list for deletion when we're done.
 */
function backup_migrate_temp_files_add($filepath = NULL) {
  static $files = array();
  if (!$filepath) {
    return $files;
  }
  else {
    $files[] = $filepath;
  }
}

/**
 * Delete all temporary files.
 */
function _backup_migrate_temp_files_delete() {
  // Delete the temp files created during this run.
  foreach (backup_migrate_temp_files_add() as $file) {
    $file = drupal_realpath($file);
    if (file_exists($file) && is_writable($file)) {
      unlink($file);
    }
  }

  // Delete temp files abandoned for 6 or more hours.
  $dir = file_stream_wrapper_get_instance_by_scheme('temporary')->getDirectoryPath();
  $expire = time() - \Drupal::config('backup_migrate.settings')->get('cleanup_time');
  if (file_exists($dir) && is_dir($dir) && is_readable($dir) && $handle = opendir($dir)) {
    while (FALSE !== ($file = @readdir($handle))) {
      // Delete 'backup_migrate_' files in the temp directory that are older than the expire time.
      // We should only attempt to delete writable files to prevent errors in shared environments.
      // This could still cause issues in shared environments with poorly configured file permissions.
      if (strpos($file, 'backup_migrate_') === 0 && is_writable("$dir/$file") && @filectime("$dir/$file") < $expire) {
        unlink("$dir/$file");
      }
    }
    closedir($handle);
  }
}

/**
 * Return a list of backup filetypes.
 */
function _backup_migrate_filetypes() {
  backup_migrate_include('filters');

  $out = backup_migrate_filters_file_types();

  foreach ($out as $key => $info) {
    $out[$key]['id'] = empty($info['id']) ? $key : $info['id'];
  }
  return $out;
}

/**
 * Adjust the length of a filename to allow for a string to be appended,
 * staying within the maximum filename limit.
 */
function _backup_migrate_filename_append_prepare($filename, $append_str) {
  $max_name_len = BACKUP_MIGRATE_FILENAME_MAXLENGTH - drupal_strlen($append_str);
  if (drupal_strlen($filename) > $max_name_len) {
    $filename = drupal_substr($filename, 0, $max_name_len);
  }
  return $filename;
}

/**
 * Construct a filename using token and some cleaning.
 */
function _backup_migrate_construct_filename($filename, $timestamp='') {
  $token = \Drupal::token();
  $filename = $token->replace($filename);
  $filename = preg_replace("/[^a-zA-Z0-9\.\-_]/", "", $filename);
  $filename = _backup_migrate_filename_append_prepare($filename, $timestamp);
  $filename .= '-' . $timestamp;
  $filename = trim($filename, '-');

  if (drupal_strlen($filename) == 0) {
    $filename = 'untitled';
  }

  return $filename;
}

/**
 * Construct a default filename using the site's name.
 */
function _backup_migrate_default_filename() {
  return '[site:name]';
}

/**
 * An output buffer callback which simply throws away the buffer instead of sending it to the browser.
 */
function _backup_migrate_file_dispose_buffer($buffer) {
  return "";
}
