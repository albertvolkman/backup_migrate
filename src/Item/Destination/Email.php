<?php

namespace Drupal\backup_migrate\Item\Destination;

use Drupal\backup_migrate\Item\Destination\DestinationBase;
use Drupal\backup_migrate\MimeMail;

/**
 * A destination for emailing database backups.
 *
 * @ingroup backup_migrate_destinations
 */
class Email extends DestinationBase {
  var $supported_ops = array('scheduled backup', 'manual backup', 'configure');

  /**
   * Defines a mail class to send a message with an attachment. Eventually Drupal
   * core should provide this functionality, at which time this code will be
   * removed.
   *
   * More info on sending email at <http://php.net/function.mail>.
   * This function taken from dba.module.
   *
   * @param $attachment
   *   An object which contains two variables "path" the path to the file and
   *   filename and "filename" which is just the filename.
   *
   * @param $to
   */
  function _backup_migrate_destination_email_mail_backup($attachment, $to) {
    // Send mail
    $attach        = fread(fopen($attachment->path, "r"), filesize($attachment->path));
    $mail          = new MimeMail();
    $mail->from    = \Drupal::config('system.site')->get('mail');
    $mail->headers = 'Errors-To: [EMAIL='. $mail->from .']'. $mail->from .'[/EMAIL]';
    $mail->to      = $to;
    $mail->subject = t('Database backup from !site: !file', array('!site' => \Drupal::config('system.site')->get('name'), '!file' => $attachment->filename));
    $mail->body    = t('Database backup attached.') ."\n\n";

    $mail->add_attachment("$attach", $attachment->filename, "Content-Transfer-Encoding: base64 /9j/4AAQSkZJRgABAgEASABIAAD/7QT+UGhvdG9zaG", NULL, TRUE);
    $mail->send();
  }

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
    $attachment = new \stdClass();
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

