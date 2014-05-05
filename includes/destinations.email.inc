<?php


/**
 * @file
 * Functions to handle the email backup destination.
 */

/**
 * @function
 * Temporary mail handler class.
 *
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
