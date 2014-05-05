<?php

namespace Drupal\backup_migrate\Item\Destination;

use Drupal\backup_migrate\Item\Destination\DestinationBase;

/**
 * A destination type for browser upload/download.
 *
 * @ingroup backup_migrate_destinations
 */
class BrowserBase extends DestinationBase {
  /**
   * Get a row of data to be used in a list of items of this type.
   */  
  function get_list_row() {
    // Return none as this type should not be displayed.
    return array();
  }
}

