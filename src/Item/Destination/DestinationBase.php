<?php

namespace Drupal\backup_migrate\Item\Destination;

use Drupal\backup_migrate\BackupFile;
use Drupal\backup_migrate\Item\Destination\BrowserUpload;
use Drupal\backup_migrate\Item\ItemBase;

/**
 * A base class for creating destinations.
 */
class DestinationBase extends ItemBase {
  var $db_table = "backup_migrate_destinations";
  var $type_name = "destination";
  var $default_values = array('settings' => array());
  var $singular = 'destination';
  var $plural = 'destinations';
  var $cache_files = FALSE;
  var $fetch_time = NULL;
  var $cache_expire = 86400; // 24 hours

  var $destination_type = "";
  var $supported_ops = array();

  /**
   * This function is not supposed to be called. It is just here to help the po extractor out.
   */
  function strings() {
    // Help the pot extractor find these strings.
    t('Destination');
    t('Destinations');
    t('destinations');
    t('destination');
  }

  function ops() {
    return $this->supported_ops;
  }

  /**
   * Does this destination support the given operation.
   */
  function op($op) {
    $ops = (array)$this->ops();
    return in_array($op, $ops);
  }

  /**
   * Remove the given op from the support list.
   */
  function remove_op($op) {
    $key = array_search($op, $this->supported_ops);
    if ($key !== FALSE) {
      unset($this->supported_ops[$key]);
    }
  }

  function get_name() {
    return @$this->name;
  }

  function set_name($name) {
    return $this->name = $name;
  }

  function set_type($type) {
    $this->type = $type;
  }

  function set_location($location) {
    $this->location = $location;
  }

  function get_location() {
    return @$this->location;
  }

  function get_display_location() {
    return $this->get_location();
  }

  function file_types() {
    return array();
  }

  function settings($key = NULL) {
    $out = $this->settings;
    if ($key) {
      $out = isset($out[$key]) ? $out[$key] : NULL;
    }
    return $out;
  }

  /**
   * Get the type name of this destination for display to the user.
   */
  function get_destination_type_name() {
    if ($type = $this->destination_type) {
      $types = backup_migrate_get_destination_types();
      return isset($types[$type]['type_name']) ? $types[$type]['type_name'] : $type;
    }
  }

  /**
   * Save the given file to the destination.
   */
  function save_file($file, $settings) {
    // This must be overriden.
    $this->file_cache_clear();

    // Save the file metadata if the destination supports it.
    $this->save_file_info($file, $settings);
    return $this->_save_file($file, $settings);
  }

  /**
   * Save the given file to the destination.
   */
  function _save_file($file, $settings) {
    // This must be overriden.
    return $file;
  }

  /**
   * Save the file metadata
   */
  function save_file_info($file, $settings) {
    $info = $this->create_info_file($file);
    // Save the info file and the actual file.
    return $this->_save_file($info, $settings);
  }

  /**
   * Load the file with the given destination specific id and return as a BackupFile object.
   */
  function load_file($file_id) {
    // This must be overriden.
    return NULL;
  }

  /**
   * Check if a file exists in the given destination.
   */
  function file_exists($file_id) {
    // Check if the file exists in the list of available files. Actual destination types may have more efficient ways of doing this.
    $files = $this->list_files();
    return isset($files[$file_id]);
  }

  /**
   * List all the available files in the given destination with their destination specific id.
   */
  function list_files() {
    $out = NULL;
    if ($this->cache_files) {
      $out = $this->file_cache_get();
    }
    if ($out === NULL) {
      $out = $this->_list_files();
      $out = $this->load_files_info($out);
      if ($this->cache_files) {
        $this->file_cache_set($out);
      }
    }
    return $out;
  }

  /**
   * List all the available files in the given destination with their destination specific id.
   */
  function _list_files() {
    return array();
  }

  /**
   * Load up the file's metadata from the accompanying .info file if applicable.
   */
  function load_files_info($files) {
    foreach ($files as $key => $file) {
      if (isset($files[$key . '.info'])) {
        // See if there is an info file with the same name as the backup.
        $info = backup_migrate_parse_info_file($files[$key . '.info']->filepath());
        // Allow the stored metadata to override the detected metadata.
        $file->file_info = $info + $file->file_info;
        // Remove the metadata file from the list
        unset($files[$key . '.info']);
      }
    }

    return $files;
  }

  function create_info_file($file) {
    $info = new BackupFile(array('filename' => $file->filename() .'.info'));
    $data = _backup_migrate_array_to_ini($file->file_info);
    $info->put_contents($data);
    return $info;
  }

  /**
   * Cache the file list.
   */
  function file_cache_set($files) {
    cache_set('backup_migrate_file_list:'. $this->get_id(), $files, 'cache', time() + $this->cache_expire);
  }

  /**
   * Retrieve the file list.
   */
  function file_cache_get() {
    backup_migrate_include('files');
    $cache = cache_get('backup_migrate_file_list:'. $this->get_id());
    if (!empty($cache->data) && $cache->created > (time() - $this->cache_expire)) {
      $this->fetch_time = $cache->created;
      return $cache->data;
    }
    $this->fetch_time = 0;
    return NULL;
  }

  /**
   * Retrieve the file list.
   */
  function file_cache_clear() {
    if ($this->cache_files) {
      $this->file_cache_set(NULL);
    }
  }

  /**
   * Delete the file with the given destination specific id.
   */
  function delete_file($file_id) {
    $this->file_cache_clear();
    $this->_delete_file($file_id);
  }

  /**
   * Delete the file with the given destination specific id.
   */
  function _delete_file($file_id) {
    // This must be overriden.
  }

  /**
   * Get the edit form for the item.
   */
  function edit_form() {
    if (get_class($this) !== 'backup_migrate_destination') {
      $form = parent::edit_form();
      $form['name'] = array(
        "#type" => "textfield",
        "#title" => t("Destination name"),
        "#default_value" => $this->get_name(),
        "#required" => TRUE,
      );
      $form['type'] = array(
        "#type" => "value",
        "#default_value" => $this->destination_type,
      );
    }
    else {
      $types = backup_migrate_get_destination_types();
      $items = array();
      // If no (valid) node type has been provided, display a node type overview.
      foreach ($types as $key => $type) {
        if (@$type['can_create']) {
          $type_url_str = str_replace('_', '-', $key);
          $out = '<dt>'. l($type['type_name'], BACKUP_MIGRATE_MENU_PATH . "/destination/list/add/$type_url_str", array('attributes' => array('title' => t('Add a new @s destination.', array('@s' => $type['type_name']))))) .'</dt>';
          $out .= '<dd>'. filter_xss_admin($type['description']) .'</dd>';
          $items[] = $out;
        }
      }
      if (count($items)) {
        $output = t('Choose the type of destination you would like to create:') .'<dl>'. implode('', $items) .'</dl>';
      }
      else {
        $output = t('No destination types available.');
      }
      $form['select_type'] = array(
        '#type' => 'markup',
        '#markup' => $output,
      );
    }
    return $form;
  }

  /**
   * Get the message to send to the user when confirming the deletion of the item.
   */
  function delete_confirm_message() {
    return t('Are you sure you want to delete the destination %name? Backup files already saved to this destination will not be deleted.', array('%name' => $this->get_name()));
  }


  /**
   * Get the columns needed to list the type.
   */  
  function get_list_column_info() {
    $out = parent::get_list_column_info();
    $out = array(
      'name'                  => array('title' => t('Name')),
      'destination_type_name' => array('title' => t('Type')),
      'display_location'      => array('title' => t('Location')),
    ) + $out;
    return $out;
  }

  /**
   * Get a row of data to be used in a list of items of this type.
   */  
  function get_list_row() {
    $out = parent::get_list_row();

    // Supress destinations with no actions as there's no value in showing them (and they may confuse new users).
    if (empty($out['actions'])) {
      return NULL;
    }
    return $out;
  }

  /**
   * Get the action links for a destination.
   */
  function get_action_links() {
    $out = parent::get_action_links();
    $item_id = $this->get_id();

    // Don't display the download/delete/restore ops if they are not available for this destination.
    if ($this->op('list files') && \Drupal::currentUser()->hasPermission('administer backup files')) {
      $out = array('list files' => l(t("list files"), BACKUP_MIGRATE_MENU_PATH . "/$this->type_name/list/files/". $item_id)) + $out;
    }
    if (!$this->op('configure') || !\Drupal::currentUser()->hasPermission('administer backup and migrate')) {
      unset($out['edit']);
    }
    return $out;
  }

  /**
   * Get the action links for a file on a given destination.
   */
  function get_file_links($file_id) {
    $out = array('download' => '', 'restore' => '', 'delete' => '');

    // Don't display the download/delete/restore ops if they are not available for this destination.
    $can_read       = $this->can_read_file($file_id);
    $can_delete     = $this->can_delete_file($file_id);

    $destination_id = $this->get_id();
    if ($can_read && \Drupal::currentUser()->hasPermission('access backup files')) {
      $out['download'] = l(t("download"), BACKUP_MIGRATE_MENU_PATH . "/destination/downloadfile/". $destination_id .'/'. $file_id);
    }
    if ($can_read && \Drupal::currentUser()->hasPermission('restore from backup')) {
      $out['restore'] = l(t("restore"), BACKUP_MIGRATE_MENU_PATH . "/destination/restorefile/". $destination_id .'/'. $file_id);
    }
    if ($can_delete && \Drupal::currentUser()->hasPermission('delete backup files')) {
      $out['delete'] = l(t("delete"), BACKUP_MIGRATE_MENU_PATH . "/destination/deletefile/". $destination_id .'/'. $file_id);
    }
    return $out;
  }

  /**
   * Determine if we can read the given file.
   */
  function can_read_file($file_id) {
    return $this->op('restore');
  }

  /**
   * Determine if we can read the given file.
   */
  function can_delete_file($file_id) {
    return $this->op('delete');
  }

  /**
   * Get the form for the settings for this destination type.
   */
  function settings_default() {
    return array();
  }

  /**
   * Get the form for the settings for this destination.
   */
  function settings_form($form) {
    return $form;
  }

  /**
   * Validate the form for the settings for this destination.
   */
  function settings_form_validate($form_values) {
  }

  /**
   * Submit the settings form. Any values returned will be saved.
   */
  function settings_form_submit($form_values) {
    return $form_values;
  }
  

  /**
   * Create a new destination of the correct type.
   */
  function create($params = array()) {
    $out = NULL;
    $types = backup_migrate_get_destination_types();

    // Get the type passed in in the params, or if none, check the url for a valid type name.
    // This is to allow new destination type to be specified in the path.
    $destination_type = !empty($params['type']) ? $params['type'] : arg(BACKUP_MIGRATE_MENU_DEPTH + 3);

    if ($destination_type && ($type = @$types[$destination_type])) {
      // Include the necessary file if specified by the type.
      $out = new $type['class']($params + array('destination_type' => $destination_type));
    }
    if (empty($out)) {
      $out = new DestinationBase();
    }
    return $out;
  }

  /**
   * Get the form for the settings for this filter.
   */
  function backup_settings_default() {
    return array();
  }

  /**
   * Get the form for the settings for this filter.
   */
  function backup_settings_form($settings) {
    return array();
  }

  /**
   * Get the form for the settings for this filter.
   */
  function backup_settings_form_validate($form, &$form_state) {
  }

  /**
   * Submit the settings form. Any values returned will be saved.
   */
  function backup_settings_form_submit($form, &$form_state) {
  }

  /**
   * Get the form for the settings for this filter.
   */
  function restore_settings_default() {
    return array();
  }

  /**
   * Get the form for the settings for this filter.
   */
  function restore_settings_form($settings) {
    return array();
  }

  /**
   * Get the form for the settings for this filter.
   */
  function restore_settings_form_validate($form_values) {
  }

  /**
   * Submit the settings form. Any values returned will be saved.
   */
  function restore_settings_form_submit($form_values) {
    return $form_values;
  }
}
