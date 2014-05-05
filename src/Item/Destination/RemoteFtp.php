<?php

namespace Drupal\backup_migrate\Item\Destination;

use Drupal\backup_migrate\BackupFile;
use Drupal\backup_migrate\Item\Destination\DestinationBase;

/**
 * A destination for sending database backups to an FTP server.
 *
 * @ingroup backup_migrate_destinations
 */
class RemoteFtp extends RemoteBase {
  var $supported_ops = array('scheduled backup', 'manual backup', 'restore', 'list files', 'configure', 'delete');
  var $ftp = NULL;

  /**
   * Save to the ftp destination.
   */
  function _save_file($file, $settings) {
    $ftp = $this->ftp_object();
    if (drupal_ftp_file_to_ftp($file->filepath(), $file->filename(), '.', $ftp)) {
      return $file;
    }
    return FALSE;
  }

  /**
   * Load from the ftp destination.
   */
  function load_file($file_id) {
    backup_migrate_include('files');
    $file = new BackupFile(array('filename' => $file_id));
    $this->ftp_object();
    if (drupal_ftp_ftp_to_file($file->filepath(), $file_id, '.', $this->ftp)) {
      return $file;
    }
    return FALSE;
  }

  /**
   * Delete from the ftp destination.
   */
  function _delete_file($file_id) {
    $this->ftp_object();
    drupal_ftp_delete_file($file_id, $this->ftp);
  }

  function _list_files() {
    backup_migrate_include('files');
    $files = array();
    $this->ftp_object();
    $ftp_files = drupal_ftp_file_list('.', $this->ftp);
    foreach ($ftp_files as $file) {
      $files[$file['filename']] = new BackupFile($file);
    }
    return $files;
  }

  /**
   * Get the form for the settings for this filter.
   */
  function edit_form() {
    $form = parent::edit_form();
    $form['scheme']['#type'] = 'value';
    $form['scheme']['#value'] = 'ftp';
    $form['port'] = array(
      "#type" => "textfield",
      "#title" => t("Port"),
      "#default_value" => @$this->dest_url['port'] ? $this->dest_url['port'] : '21',
      "#weight" => 15,
    );
    $form['pasv'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use PASV transfers'),
      '#default_value' => $this->get_pasv(),
      '#weight' => 50,
    );
    return $form;
  }

  function set_pasv($value) {
    $this->settings['pasv'] = (bool)$value;
  }

  function get_pasv() {
    return isset($this->settings['pasv']) ? $this->settings['pasv'] : FALSE;
  }

  function ftp_object() {
    if (!$this->ftp) {
      $this->dest_url['port'] = empty($this->dest_url['port']) ? '21' : $this->dest_url['port'];
      $this->dest_url['pasv'] = $this->get_pasv();
      $this->ftp = drupal_ftp_ftp_object($this->dest_url['host'], $this->dest_url['port'], $this->dest_url['user'], $this->dest_url['pass'], $this->dest_url['path'], $this->dest_url['pasv']);
    }
    return $this->ftp;
  }
}
