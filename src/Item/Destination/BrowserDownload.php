<?php

/**
 * @file
 * Functions to handle the browser upload/download backup destination.
 */

namespace Drupal\backup_migrate\Item\Destination;

use Drupal\backup_migrate\Item\Destination\BrowserBase;

/**
 * A destination type for browser download.
 *
 * @ingroup backup_migrate_destinations
 */
class BrowserDownload extends BrowserBase {
  var $supported_ops = array('manual backup');
  function __construct() {
    $params = array();
    $params['name'] = "Download";
    $params['destination_id'] = 'download';
    parent::__construct($params);
  }

  /**
   * File save destination callback.
   */
  function save_file($file, $settings) {
    backup_migrate_include('files');
    $file->transfer();
  }
}

