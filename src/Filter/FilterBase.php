<?php

namespace Drupal\backup_migrate\Filter;

/**
 * A base class for basing filters on.
 */
class FilterBase {
  var $weight = 0;
  var $op_weights = array();

  /**
   * Get the weight of the filter for the given op.
   */
  function weight($op = NULL) {
    if ($op && isset($this->op_weights[$op])) {
      return $this->op_weights[$op];
    }
    return $this->weight;
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
    return $form_state['values'];
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
  function restore_settings_form_validate($form, &$form_state) {
  }

  /**
   * Submit the settings form. Any values returned will be saved.
   */
  function restore_settings_form_submit($form, &$form_state) {
    return $form_state['values'];
  }

  /**
   * Get a list of file types handled by this filter.
   */
  function file_types() {
    return array();
  }

  /**
   * Declare any default destinations for this filter.
   */
  function destinations() {
    return array();
  }


  /**
   * This function is called on a backup file after the backup has been completed.
   */
  function backup($file, &$settings) {
    return $file;
  }

  /**
   * This function is called on a backup file before importing it.
   */
  function restore($file, &$settings) {
    return $file;
  }

  /**
   * This function is called immediately prior to backup.
   */
  function pre_backup($source, $file, $settings) {

  }

  /**
   * This function is called immediately post backup.
   */
  function post_backup($source, $file, $settings) {

  }
}
