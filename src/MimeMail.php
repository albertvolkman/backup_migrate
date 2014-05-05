<?php

namespace Drupal\backup_migrate;

class MimeMail {
  var $parts;
  var $to;
  var $from;
  var $headers;
  var $subject;
  var $body;

  function __construct() {
    $this->parts   = array();
    $this->to      = "";
    $this->from    = "";
    $this->headers = "";
    $this->subject = "";
    $this->body    = "";
  }

  function add_attachment($message, $name = "", $ctype = "application/octet-stream", $encode = NULL, $attach = FALSE) {
    $this->parts[] = array(
      "ctype" => $ctype,
      "message" => $message,
      "encode" => $encode,
      "name" => $name,
      "attach" => $attach,
    );
  }

  function build_message($part) {
    $message  = $part["message"];
    $message  = chunk_split(base64_encode($message));
    $encoding = "base64";
    $disposition = $part['attach'] ? "Content-Disposition: attachment; filename=$part[name]\n" : '';
    return "Content-Type: ". $part["ctype"] . ($part["name"] ? "; name = \"". $part["name"] ."\"" : "") ."\nContent-Transfer-Encoding: $encoding\n$disposition\n$message\n";
  }

  function build_multipart() {
    $boundary = "b". md5(uniqid(time()));
    $multipart = "Content-Type: multipart/mixed; boundary = $boundary\n\nThis is a MIME encoded message.\n\n--$boundary";
    for ($i = sizeof($this->parts) - 1; $i >= 0; $i--) {
      $multipart .= "\n". $this->build_message($this->parts[$i]) ."--$boundary";
    }
    return $multipart .= "--\n";
  }

  function send() {
    $mime = "";
    if (!empty($this->from)) $mime .= "From: ". $this->from ."\n";
    if (!empty($this->headers)) $mime .= $this->headers ."\n";
    if (!empty($this->body)) $this->add_attachment($this->body, "", "text/plain");
    $mime .= "MIME-Version: 1.0\n". $this->build_multipart();
    mail($this->to, $this->subject, "", $mime);
  }
}
