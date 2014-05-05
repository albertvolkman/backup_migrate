<?php

namespace Drupal\backup_migrate\Item\Destination;

use Drupal\backup_migrate\Item\Destination\DestinationBase;

/**
 * A destination for emailing database backups.
 *
 * @ingroup backup_migrate_destinations
 */
class Email extends DestinationBase {
  var $supported_ops = array('scheduled backup', 'manual backup', 'configure');

  /**
   * Save to (ie. email the file) to the email destination.
   */
  function save_file($file, $settings) {
    $size = filesize($file->filepath());
    $max = \Drupal::config('backup_migrate.settings')->get('max_email_size');
    if ($size > $max) {
      _backup_migrate_message('Could not email the file @file because it is @size and Backup and Migrate only supports emailing files smaller than @max.', array('@file' => $file->filename(), '@size' => format_size($size), '@max' => format_size($max)), 'error');
      return FALSE;
    }
    $attachment = new stdClass();
    $attachment->filename = $file->filename();
    $attachment->path = $file->filepath();
    _backup_migrate_destination_email_mail_backup($attachment, $this->get_location());
    return $file;
  }

  /**
   * Get the form for the settings for this filter.
   */
  function edit_form() {
    $form = parent::edit_form();
    $form['location'] = array(
      "#type" => "textfield",
      "#title" => t("Email Address"),
      "#default_value" => $this->get_location(),
      "#required" => TRUE,
      "#description" => t('Enter the email address to send the backup files to. Make sure the email sever can handle large file attachments'),
    );
    return $form;
  }

  /**
   * Validate the configuration form. Make sure the email address is valid.
   */
  function settings_form_validate($values) {
    if (!valid_email_address($values['location'])) {
      form_set_error('[location]', t('The e-mail address %mail is not valid.', array('%mail' => $form_state['values']['location'])));
    }
  }
}

