<?php

namespace Drupal\backup_migrate\Item\Destination;

use Drupal\backup_migrate\BackupFile;
use Drupal\backup_migrate\Item\Destination\BrowserBase;

/**
 * A destination type for browser upload.
 *
 * @ingroup backup_migrate_destinations
 */
class BrowserUpload extends BrowserBase {
  var $supported_ops = array('restore');
  function __construct() {
    $params = array();
    $params['name'] = "Upload";
    $params['destination_id'] = 'upload';
    parent::__construct($params);
  }

  /**
   * File load destination callback.
   */
  function load_file($file_id) {
    backup_migrate_include('files');
    if ($file = file_save_upload('backup_migrate_restore_upload')) {
      $out = new BackupFile(array('filepath' => $file->uri));
      backup_migrate_temp_files_add($file->uri);
      return $out;
    }
    return NULL;
  }
}
