<?php

namespace Drupal\backup_migrate\Filter;

use Drupal\backup_migrate\Filter\FilterBase;

/**
 * @file
 * A filter for compressing bckups with zip, gz bzip etc.
 */

/**
 * A filter to send a notification email on success or failure of backup.
 *
 * @ingroup backup_migrate_filters
 */
class StatusNotify extends FilterBase {
  
  /**
   * Get the default backup settings for this filter.
   */
  function backup_settings_default() {
    $email = \Drupal::config('system.site')->get('mail');
    return array(
      'notify_success_enable' => FALSE,
      'notify_failure_enable' => FALSE,
      'notify_success_email' => $email,
      'notify_failure_email' => $email,
    );
  }

  /**
   * Get the form for the settings for this filter.
   */
  function backup_settings_form($settings) {
    $form = array();
    $form['advanced']['notify_success_enable'] = array(
      "#type" => 'checkbox',
      "#title" => t("Send an email if backup succeeds"),
      "#default_value" => @$settings['notify_success_enable'],
    );
    $form['advanced']['notify_success_email'] = array(
      "#type" => "textfield",
      "#title" => t("Email Address for Success Notices"),
      "#default_value" => @$settings['notify_success_email'],
    );
    $form['advanced']['notify_failure_enable'] = array(
      "#type" => 'checkbox',
      "#title" => t("Send an email if backup fails"),
      "#default_value" => @$settings['notify_failure_enable'],
    );
    $form['advanced']['notify_failure_email'] = array(
      "#type" => "textfield",
      "#title" => t("Email Address for Failure Notices"),
      "#default_value" => @$settings['notify_failure_email'],
    );
    return $form;
  }

  /**
   * Send the success email.
   */
  function backup_succeed($settings) {
    if (@$settings->filters['notify_success_enable'] && $to = @$settings->filters['notify_success_email']) {
      $messages = $this->get_messages();
      $subject = t('!site backup succeeded', array('!site' => \Drupal::config('system.site')->get('name')));
      if ($messages = $this->get_messages()) {
        $body = t("The site backup has completed successfully with the following messages:\n!messages", array('!messages' => $messages));
      }
      else {
        $body = t("The site backup has completed successfully.\n");
      }
      mail($settings->filters['notify_success_email'], $subject, $body);
    }
  }

  /**
   * Send the failure email.
   */
  function backup_fail($settings) {
    if (@$settings->filters['notify_failure_enable'] && $to = @$settings->filters['notify_failure_email']) {
      $messages = $this->get_messages();
      $subject = t('!site backup failed', array('!site' => \Drupal::config('system.site')->get('name')));
      if ($messages = $this->get_messages()) {
        $body = t("The site backup has failed with the following messages:\n!messages", array('!messages' => $messages));
      }
      else {
        $body = t("The site backup has failed for an unknown reason.");
      }
      mail($settings->filters['notify_failure_email'], $subject, $body);
    }
  }

  /**
   * Render the messages and errors for the email.
   */
  function get_messages() {
    $out = "";
    $messages = _backup_migrate_messages();
    foreach ($messages as $message) {
      $out .= strip_tags(t($message['message'], $message['replace'])) . "\n";
    }
    return $out;
  }
}

