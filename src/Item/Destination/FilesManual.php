<?php

namespace Drupal\backup_migrate\Item\Destination;

use Drupal\backup_migrate\Item\Destination\FilesBase;

/**
 * The manual files directory.
 */
class FilesManual extends FilesBase {
  var $supported_ops = array('manual backup', 'restore', 'list files', 'configure', 'delete');
  function __construct($params = array()) {
    $dir = 'private://backup_migrate/manual';
    parent::__construct($params + array('location' => $dir, 'name' => t('Manual Backups Directory')));
  }
}
