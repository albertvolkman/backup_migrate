<?php

/**
 * @file
 * A filter to run some basic utility functions. Basically any useful option not big enough to justify it's own class.
 */

namespace Drupal\backup_migrate\Filter;

use Drupal\backup_migrate\Filter\FilterBase;

/**
 * A filter to send a notification email on success or failure of backup.
 *
 * @ingroup backup_migrate_filters
 */
class Utils extends FilterBase {
  var $saved_devel_query = NULL;

  /**
   * Get the default backup settings for this filter.
   */
  function backup_settings_default() {
    return array(
      'utils_disable_query_log' => TRUE,
      'utils_site_offline' => FALSE,
      'utils_description' => '',
    );
  }

  /**
   * Get the default restore settings for this filter.
   */
  function restore_settings_default() {
    return array(
      'utils_disable_query_log' => TRUE,
      'utils_site_offline' => FALSE,
    );
  }

  /**
   * Get the form for the backup settings for this filter.
   */
  function backup_settings_form($settings) {
    $form = array();
    if (module_exists('devel') && \Drupal::config('devel.settings')->get('query_display')) {
      $form['database']['utils_disable_query_log'] = array(
        '#type' => 'checkbox',
        '#title' => t('Disable query log'),
        '#default_value' => !empty($settings['utils_disable_query_log']) ? $settings['utils_disable_query_log'] : NULL,
        '#description' => t('Disable the devel module\'s query logging during the backup operation. It will be enabled again after backup is complete. This is very highly recommended.'),
      );
    }
    $form['advanced']['utils_site_offline'] = array(
      '#type' => 'checkbox',
      '#title' => t('Take site offline'),
      '#default_value' => !empty($settings['utils_site_offline']) ? $settings['utils_site_offline'] : NULL,
      '#description' => t('Take the site offline during backup and show a maintenance message. Site will be taken back online once the backup is complete.'),
    );
    $form['advanced']['utils_site_offline_message'] = array(
      '#type' => 'textarea',
      '#title' => t('Site off-line message'),
      '#default_value' => !empty($settings['utils_site_offline_message']) ? $settings['utils_site_offline_message'] : \Drupal::config('system.maintenance')->get('message'), array('@site' => \Drupal::config('system.site')->get('name')),
      '#description' => t('Message to show visitors when the site is in off-line mode.')
    );
    $form['advanced']['utils_description'] = array(
      '#type' => 'textarea',
      '#title' => t('Description'),
      '#default_value' => !empty($settings['utils_description']) ? $settings['utils_description'] : NULL,
      '#description' => t('Add a short description to the backup file.'),
    );

    return $form;
  }

  /**
   * Get the form for the restore settings for this filter.
   */
  function restore_settings_form($settings) {
    $form = array();
    if (module_exists('devel') && \Drupal::config('devel.settings')->get('query_display')) {
      $form['advanced']['utils_disable_query_log'] = array(
        '#type' => 'checkbox',
        '#title' => t('Disable query log'),
        '#default_value' => @$settings['utils_disable_query_log'],
        '#description' => t('Disable the devel module\'s query logging during the restore operation. It will be enabled again after restore is complete. This is very highly recommended.'),
      );
    }
    $form['advanced']['utils_site_offline'] = array(
      '#type' => 'checkbox',
      '#title' => t('Take site offline'),
      '#default_value' => !empty($settings['utils_site_offline']) ? $settings['utils_site_offline'] : NULL,
      '#description' => t('Take the site offline during restore and show a maintenance message. Site will be taken back online once the restore is complete.'),
    );
    $form['advanced']['utils_site_offline_message'] = array(
      '#type' => 'textarea',
      '#title' => t('Site off-line message'),
      '#default_value' => !empty($settings['utils_site_offline_message']) ? $settings['utils_site_offline_message'] : \Drupal::config('system.maintenance')->get('message'), array('@site' => \Drupal::config('system.site')->get('name')),
      '#description' => t('Message to show visitors when the site is in off-line mode.')
    );
    return $form;
  }

  function pre_backup($source, $file, $settings) {
    $this->take_site_offline($settings);
    $this->disable_devel_query($settings);
  }

  function post_backup($source, $file, $settings) {
    $this->enable_devel_query($settings);
    $this->take_site_online($settings);
    $this->add_file_info($file, $settings);
  }

  function pre_restore($file, $settings) {
    $this->disable_devel_query($settings);
    $this->take_site_offline($settings);
  }

  function post_restore($file, $settings) {
    $this->enable_devel_query($settings);
    $this->take_site_online($settings);
  }

  /**
   * Disable devel query logging if it's active and the user has chosen to do so.
   */
  function disable_devel_query($settings) {
    $query_dispay = \Drupal::config('devel.settings')->get('query_display');
    $this->saved_devel_query = $query_display;
    if (module_exists('devel') && $query_display && !empty($settings->filters['utils_disable_query_log'])) {
      \Drupal::config('devel.settings')->set('query_display', 0);
    }
  }

  /**
   * Restore devel query to previous state.
   */
  function enable_devel_query($settings) {
    if (module_exists('devel')) {
      \Drupal::config('devel.settings')->set('query_display', $this->saved_devel_query);
    }
  }

  /**
   * Add the backup metadata to the file.
   */
  function add_file_info($file, $settings) {
    $file->file_info['description']       = $settings->filters['utils_description'];
    $file->file_info['datestamp']         = time();
    $file->file_info['generator']         = 'Backup and Migrate (http://drupal.org/project/backup_migrate)';
    $file->file_info['generatorversion']  = BACKUP_MIGRATE_VERSION;
    $file->file_info['sites'] = array(
        '0' => array(
          'version' => VERSION,
          'name' => \Drupal::config('system.site')->get('name'),
          'url' => url('', array('absolute' => TRUE)),
        ),
      );

  }

  /**
   * Take the site offline if configured to do so.
   */
  function take_site_offline($settings) {
    // Save the current state of the site in case a restore overwrites it.
    $this->saved_site_offline = \Drupal::state()->get('system.maintenance_mode');
    if (@$settings->filters['utils_site_offline']) {
      $config = \Drupal::config('system.maintenance');
      $this->saved_site_offline_message = $config->get('message');
      if (!empty($settings->filters['utils_site_offline_message'])) {
        $config->set('message', $settings->filters['utils_site_offline_message']);
      }
      \Drupal::state()->set('system.maintenance_mode', 1);
      _backup_migrate_message('Site was taken offline.');
    }
  }

  /**
   * Take the site online again after backup or restore.
   */
  function take_site_online($settings) {
    // Take the site back off/online because the restored db may have changed that setting.
    \Drupal::state()->set('system.maintenance_mode', $this->saved_site_offline);
    if ($settings->filters['utils_site_offline']) {
      if (!empty($this->saved_site_offline_message)) {
        \Drupal::config('system.maintenance')->set('message', $this->saved_site_offline_message);
      }
      _backup_migrate_message('Site was taken online.');
    }
  }
}
