<?php

namespace Drupal\backup_migrate\Item;

use Drupal\Component\Utility\String;

/**
 * A base class for items which can be stored in the database, listed, edited, deleted etc.
 */
class ItemBase {
  var $db_table = '';
  var $type_name = '';
  var $storage = FALSE;
  var $default_values = array();
  var $singular = 'item';
  var $plural = 'items';

  /**
   * Constructor, set the basic info pulled from the db or generated programatically.
   */
  function __construct($params = array()) {
    $this->from_array((array)$params + (array)$this->get_default_values());
  }

  /**
   * Get the default values for standard parameters.
   */
  function get_default_values() {
    return $this->default_values;
  }

  /**
   * Save the item to the database.
   */
  function save() {
    if (!$this->get_id()) {
      $this->generate_id();
    }
    $data = $this->to_array();
    drupal_write_record($this->db_table, $data, !empty($this->storage) ? $this->get_primary_key() : array());
  }

  /**
   * Delete the item from the database.
   */
  function delete() {
    $keys = (array)$this->get_primary_key();
    db_query('DELETE FROM {' . $this->db_table . '} WHERE ' . $keys[0] . ' = :id', array(':id' => $this->get_id()));
  }


  /**
   * Load an existing item from an array.
   */
  function from_array($params) {
    foreach ($params as $key => $value) {
      if (method_exists($this, 'set_'. $key)) {
        $this->{'set_'. $key}($value);
      }
      else {
        $this->{$key} = $value;
      }
    }
  }

  /**
   * Return as an array of values.
   */
  function to_array() {
    $out = array();
    // Return fields as specified in the schema.
    $schema = $this->get_schema();
    if (!empty($schema['fields']) && is_array($schema['fields'])) {
      foreach ($schema['fields'] as $field => $info) {
        $out[$field] = $this->get($field);
      }
    }
    return $out;
  }

  /**
   * Return as an exported array of values.
   */
  function export() {
    $out = $this->to_array();
    ob_start();
    var_export($out);
    $out = ob_get_contents();
    ob_end_clean();
    return $out;
  }

  /**
   * Load an existing item from an database (serialized) array.
   */
  function load_row($data) {
    $params = array();
    $schema = $this->get_schema();
    // Load fields as specified in the schema.
    foreach ($schema['fields'] as $field => $info) {
      $params[$field] = empty($info['serialize']) ? $data[$field] : unserialize($data[$field]);
    }
    $this->from_array($params);
  }


  /**
   * Decode a loaded db row (unserialize necessary fields).
   */
  function decode_db_row($data) {
    $params = array();
    $schema = $this->get_schema();
    // Load fields as specified in the schema.
    foreach ($schema['fields'] as $field => $info) {
      $params[$field] = empty($info['serialize']) ? $data[$field] : unserialize($data[$field]);
    }
    return $params;
  }

  /**
   * Return the fields which must be serialized before saving to the db.
   */
  function get_serialized_fields() {
    $out = array();
    $schema = $this->get_schema();
    foreach ($schema['fields'] as $field => $info) {
      if (!empty($info['serialize'])) {
        $out[] = $field;
      }
    }
    return $out;
  }

  /**
   * Get the primary key field title from the schema.
   */
  function get_primary_key() {
    $schema = $this->get_schema();
    return @$schema['primary key'];
  }

  /**
   * Get the schema for the item type.
   */
  function get_schema() {
    return drupal_get_schema($this->db_table);
  }

  /**
   * Get the primary id for this item (if any is set).
   *
   * We only handle single field keys since that's all we need.
   */
  function get_id() {
    $keys = (array)$this->get_primary_key();
    return !empty($this->{$keys[0]}) ? (string)$this->{$keys[0]} : '';
  }

  /**
   * Set the primary id for this item (if any is set).
   */
  function set_id($id) {
    $keys = (array)$this->get_primary_key();
    if (!empty($keys[0])) {
      return $this->{$keys[0]} = $id;
    }
    return NULL;
  }

  /**
   * Return a random (very very likely unique) string id for a new item.
   */
  function generate_id() {
    $this->set_id(md5(uniqid(mt_rand(), true)));
  }

  /**
   * Get the name of the item.
   */
  function get_name() {
    return @$this->name;
  }

  /**
   * Get the member with the given key.
   */
  function get($key) {
    if (method_exists($this, 'get_'. $key)) {
      return $this->{'get_'. $key}();
    }
    return @$this->{$key};
  }

  /* UI Stuff */

  /**
   * Get the action links for a destination.
   */
  function get_action_links() {
    $out = array('edit' => '', 'delete' => '');

    $item_id = $this->get_id();

    if (@$this->storage == BACKUP_MIGRATE_STORAGE_DB || @$this->storage == BACKUP_MIGRATE_STORAGE_OVERRIDEN) {
      $out['edit'] = l(t("edit"), BACKUP_MIGRATE_MENU_PATH . "/$this->type_name/list/edit/$item_id");
    }
    else if (@$this->storage == BACKUP_MIGRATE_STORAGE_NONE) {
      $out['edit'] = l(t("override"), BACKUP_MIGRATE_MENU_PATH . "/$this->type_name/list/edit/$item_id");
    }
    if (@$this->storage == BACKUP_MIGRATE_STORAGE_DB) {
      $out['delete'] = l(t("delete"), BACKUP_MIGRATE_MENU_PATH . "/$this->type_name/list/delete/$item_id");
    }
    else if (@$this->storage == BACKUP_MIGRATE_STORAGE_OVERRIDEN) {
      $out['delete'] = l(t("revert"), BACKUP_MIGRATE_MENU_PATH . "/$this->type_name/list/delete/$item_id");
    }

    // Export link disabled until we have an import function.
    //$out['export'] = l(t("export"), BACKUP_MIGRATE_MENU_PATH . "/$this->type_name/list/export/$item_id");

    return $out;
  }

  /**
   * Get a table of all items of this type.
   */
  function get_list() {
    $items = $this->all_items();
    $rows = array();
    foreach ($items as $item) {
      if ($row = $item->get_list_row()) {
        $rows[] = $row;
      }
    }
    if (count($rows)) {
      $out = theme('table', array('header' => $this->get_list_header(), 'rows' => $rows));
    }
    else {
      $out = t('There are no !items to display.', array('!items' => $this->plural));
    }
    return $out;
  }

  /**
   * Get the columns needed to list the type.
   */
  function get_list_column_info() {
    return array(
      'actions' => array('title' => t('Operations'), 'html' => TRUE),
    );
  }

  /**
   * Get header for a lost of this type.
   */
  function get_list_header() {
    $out = array();
    foreach ($this->get_list_column_info() as $key => $col) {
      $out[] = $col['title'];
    }
    return $out;
  }

  /**
   * Get a row of data to be used in a list of items of this type.
   */
  function get_list_row() {
    $out = array();
    foreach ($this->get_list_column_info() as $key => $col) {
      $out[$key] = empty($col['html']) ? String::checkPlain($this->get($key)) : $this->get($key);
    }
    return $out;
  }

  /**
   * Get the rendered action links for a destination.
   */
  function get_actions() {
    $links = $this->get_action_links();
    return implode(" &nbsp; ", $links);
  }

  /**
   * Get the edit form for the item.
   */
  function edit_form() {
    $form = array();
    $form['item'] = array(
      '#type' => 'value',
      '#value' => $this,
    );
    $form['id'] = array(
      '#type' => 'value',
      '#value' => $this->get_id(),
    );
    $form['actions'] = array('#prefix' => '<div class="container-inline">', '#suffix' => '</div>', '#weight' => 99);
    $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('Save !type', array('!type' => t($this->singular))));
    $form['actions']['cancel'] = array('#markup' => l(t('Cancel'), BACKUP_MIGRATE_MENU_PATH . '/destination'));
    return $form;
  }

  /**
   * Validate the edit form for the item.
   */
  function edit_form_validate($form, &$form_state) {
  }

  /**
   * Submit the edit form for the item.
   */
  function edit_form_submit($form, &$form_state) {
    $this->from_array($form_state['values']);
    $this->save();
    _backup_migrate_message('!type saved', array('!type' => t(ucwords($this->singular))));
  }

  /**
   * Get the message to send to the user when confirming the deletion of the item.
   */
  function delete_confirm_message() {
    return t('Are you sure you want to delete this !type?', array('!type' => t($item->singular)));
  }

  /* Static Functions */

   /**
   * This function is not supposed to be called. It is just here to help the po extractor out.
   */
  function strings() {
    // Help the pot extractor find these strings.
    t('List !type');
    t('Create !type');
    t('Delete !type');
    t('Edit !type');
    t('Export !type');
  }

  /**
   * Create a new items with the given input. Doesn't load the parameters, but could use them to determine what type to create.
   */
  function create($params = array()) {
    $type = get_class($this);
    return new $type($params);
  }

  /**
   * Get all of the given items.
   */
  function all_items() {
    static $cache = array();

    // Allow other modules to declare destinations programatically.
    $items = \Drupal::moduleHandler()->invokeAll($this->db_table);

    // Get any items stored as a variable. This allows destinations to be defined in settings.php
    foreach (\Drupal::config('backup_migrate.settings')->get('destinations') as $info) {
      if (is_array($info) && $item = $this->create($info)) {
        $items[$item->get_id()] = $item;
      }
    }

    // Get the items from the db.
    $result = db_query("SELECT * FROM {{$this->db_table}}", array(), array('fetch' => \PDO::FETCH_ASSOC));
    foreach ($result as $info) {
      $info = $this->decode_db_row($info);
      if ($item = $this->create($info)) {
        $item->storage = empty($items[$item->get_id()]) ? BACKUP_MIGRATE_STORAGE_DB : BACKUP_MIGRATE_STORAGE_OVERRIDEN;
        $items[$item->get_id()] = $item;
      }
    }

    // Allow other modules to alter the items. This should maybe be before the db override code above
    // but then the filters are not able to set defaults for missing values. Other modules should just
    // be careful not to overwrite the user's UI changes in an unexpected way.
    \Drupal::moduleHandler()->alter($this->db_table, $items);

    return $items;
  }

  /**
   * A particular item.
   */
  function item($item_id) {
    $items = $this->all_items();
    return !empty($items[$item_id]) ? $items[$item_id] : NULL;
  }
}
