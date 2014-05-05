<?php

namespace Drupal\backup_migrate\Item\Destination;

use Drupal\backup_migrate\Item\Destination\FilesBase;

/**
 * The scheduled files directory.
 */
class FilesScheduled extends FilesBase {
  var $supported_ops = array('scheduled backup', 'restore', 'list files', 'configure', 'delete');
  function __construct($params = array()) {
    $dir = 'private://backup_migrate/scheduled';
    parent::__construct($params + array('location' => $dir, 'name' => t('Scheduled Backups Directory')));
  }
}
