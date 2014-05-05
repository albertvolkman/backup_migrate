<?php

namespace Drupal\backup_migrate\Item\Destination;

use Drupal\backup_migrate\Item\Destination\DestinationBase;

/**
 * A base class for creating destinations.
 */
class RemoteBase extends DestinationBase {
  /**
   * The location is a URI so parse it and store the parts.
   */
  function get_location() {
    return $this->url(FALSE);
  }

  /**
   * The location to display is the url without the password.
   */
  function get_display_location() {
    return $this->url(TRUE);
  }

  /**
   * Return the location with the password.
   */
  function set_location($location) {
    $this->location = $location;
    $this->set_url($location);
  }

  /**
   * Get a url from the parts.
   */
  function url($hide_password = TRUE) {
    return $this->glue_url($this->dest_url, $hide_password);
  }

  /**
   * Glue a URLs component parts back into a URL.
   */
  function glue_url($parts, $hide_password = TRUE) {
    // Obscure the password if we need to.
    $parts['pass'] = $hide_password ? "" : $parts['pass'];

    // Assemble the URL.
    $out = "";
    $out .= $parts['scheme'] .'://';
    $out .= $parts['user'] ? urlencode($parts['user']) : '';
    $out .= ($parts['user'] && $parts['pass']) ? ":". urlencode($parts['pass']) : '';
    $out .= ($parts['user'] || $parts['pass']) ? "@" : "";
    $out .= $parts['host'];
    $out .= !empty($parts['port']) ? ':'. $parts['port'] : '';
    $out .= "/". $parts['path'];
    return $out;
  }

  /**
   * Break a URL into it's component parts.
   */
  function set_url($url) {
    $parts          = (array)parse_url($url);
    $parts['user'] = urldecode(@$parts['user']);
    $parts['pass'] = urldecode(@$parts['pass']);
    $parts['path'] = urldecode(@$parts['path']);
    $parts['path']  = ltrim(@$parts['path'], "/");
    $this->dest_url = $parts;
  }

  /**
   * Destination configuration callback.
   */
  function edit_form() {
    $form = parent::edit_form();
    $form['scheme'] = array(
      "#type" => "value",
      "#title" => t("Scheme"),
      "#default_value" => @$this->dest_url['scheme'] ? $this->dest_url['scheme'] : 'mysql',
      "#required" => TRUE,
//      "#options" => array($GLOBALS['db_type'] => $GLOBALS['db_type']),
      "#weight" => 0,
    );
    $form['host'] = array(
      "#type" => "textfield",
      "#title" => t("Host"),
      "#default_value" => @$this->dest_url['host'] ? $this->dest_url['host'] : 'localhost',
      "#required" => TRUE,
      "#weight" => 10,
    );
    $form['path'] = array(
      "#type" => "textfield",
      "#title" => t("Path"),
      "#default_value" => @$this->dest_url['path'],
      "#required" => TRUE,
      "#weight" => 20,
    );
    $form['user'] = array(
      "#type" => "textfield",
      "#title" => t("Username"),
      "#default_value" => @$this->dest_url['user'],
      "#required" => TRUE,
      "#weight" => 30,
    );
    $form['pass'] = array(
      "#type" => "password",
      "#title" => t("Password"),
      "#default_value" => @$this->dest_url['pass'],
      '#description' => '',
      "#weight" => 40,
    );
    if (@$this->dest_url['pass']) {
      $form['old_password'] = array(
        "#type" => "value",
        "#value" => @$this->dest_url['pass'],
      );
      $form['pass']["#description"] .= t(' You do not need to enter a password unless you wish to change the currently saved password.');
    }
    return $form;
  }

  /**
   * Submit the configuration form. Glue the url together and add the old password back if a new one was not specified.
   */
  function edit_form_submit($form, &$form_state) {
    $form_state['values']['pass'] = $form_state['values']['pass'] ? $form_state['values']['pass'] : $form_state['values']['old_password'];
    $form_state['values']['location'] = $this->glue_url($form_state['values'], FALSE);
    parent::edit_form_submit($form, $form_state);
  }
}
