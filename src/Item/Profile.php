<?php

namespace Drupal\backup_migrate\Item;

use Drupal\backup_migrate\Item\ItemBase;

/**
 * A profile class for crud operations.
 */
class Profile extends ItemBase {
  var $db_table = "backup_migrate_profiles";
  var $type_name = "profile";
  var $singular = 'profile';
  var $plural = 'profiles';

  /**
   * This function is not supposed to be called. It is just here to help the po extractor out.
   */
  function strings() {
    // Help the pot extractor find these strings.
    t('Profile');
    t('Profiles');
    t('profile');
    t('profiles');
  }

  /**
   * Get the default values for standard parameters.
   */
  function get_default_values() {
    return _backup_migrate_profile_default_profile() + array('name' => t("Untitled Profile"));
  }

  /**
   * Get a table of all items of this type.
   */  
  function get_list() {
    drupal_add_css(drupal_get_path('module', 'backup_migrate') .'/backup_migrate.css');
    return parent::get_list();
  }

  /**
   * Get the columns needed to list the type.
   */  
  function get_list_column_info() {
    $out = parent::get_list_column_info();
    $out = array(
      'name'                  => array('title' => t('Name')),
      'source_name'           => array('title' => t('Source')),
      'filename'              => array('title' => t('Filename')),
    ) + $out;
    return $out;
  }

  /**
   * Get a row of data to be used in a list of items of this type.
   */  
  function get_list_row() {
    $row = parent::get_list_row();
    if (empty($this->enabled)) {
      foreach ($row as $key => $field) {
        $row[$key] = array('data' => $field, 'class' => 'profile-list-disabled');
      }
    }
    return $row;
  }

  /**
   * Set the source of this setings profile. Takes either a source object or source id.
   */
  function set_source($source) {
    if (is_object($source)) {
      $this->source = $source;
      $this->source_id = $source->get_id();
    }
    else {
      $this->source_id = $source;
      unset($this->source);
    }
  }

  /**
   * Get the source of the profile.
   */
  function get_source() {
    backup_migrate_include('destinations');
    if (!empty($this->source_id) && (empty($this->source) || $this->source->destination_id !== $this->source_id)) {
      $this->source = backup_migrate_get_destination($this->source_id);
    }
    return empty($this->source) ? NULL : $this->source;
  }

  /**
   * Get the name of the source.
   */
  function get_source_name() {
    if ($source = $this->get_source()) {
      return $source->get_name();
    }
    return t("Missing");
  }

  /**
   * Get the destination of the profile.
   */
  function get_destination() {
    backup_migrate_include('destinations');
    if (!empty($this->destination_id) && (empty($this->destination) || $this->destination->destination_id !== $this->destination_id)) {
      $this->destination = backup_migrate_get_destination($this->destination_id);
    }
    return empty($this->destination) ? NULL : $this->destination;
  }

  /**
   * Get the name of the destination.
   */
  function get_destination_name() {
    if ($destination = $this->get_destination()) {
      return $destination->get_name();
    }
    return t("Missing");
  }

  /**
   * Get the edit form.
   */
  function edit_form() {
    $form = parent::edit_form();
    $form['name'] = array(
      "#type" => "textfield",
      "#title" => t("Profile Name"),
      '#required' => TRUE,
      "#default_value" => $this->get('name'),
    );
    $form += _backup_migrate_ui_backup_settings_form($this);
    return $form;
  }

  /**
   * Get the message to send to the user when confirming the deletion of the item.
   */
  function delete_confirm_message() {
    return t('Are you sure you want to delete the profile %name? Any schedules using this profile will be disabled.', array('%name' => $this->get('name')));
  }
}
