<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';
date_default_timezone_set(date_default_timezone_get());

function flist_send() {
  global $cfg_site_url;
  $sentfile = $_SERVER['DOCUMENT_ROOT'] . '/_fregistered';

  $iua = !!ignore_user_abort(true);
  if (file_get_contents('http://floodgates.0xc9.net/a.php?url=' . rawurlencode($cfg_site_url)) === false) {
    $fp = fopen($sentfile, 'w') or die('Unable to open file! (w)');
    fwrite($fp, time() - ((6 * 24 * 60 * 60) + (12 * 60 * 60))); // try again in 12 hours
    fclose($fp);
  }
  ignore_user_abort($iua);
}

function flist_auto() {
  $sentfile = $_SERVER['DOCUMENT_ROOT'] . '/_fregistered';

  $iua = !!ignore_user_abort(true);
  if (file_exists($sentfile)) {
    $fp = fopen($sentfile, 'r') or die('Unable to open file! (r)');
    $prev_time = intval(fread($fp, filesize($sentfile)));
    fclose($fp);
    if ((time() - $prev_time) > (7 * 24 * 60 * 60)) { // should be 7 days in seconds
      $fp = fopen($sentfile, 'w') or die('Unable to open file! (w)');
      fwrite($fp, time());
      fclose($fp);
      flist_send();
    }
  } else {
    $fp = fopen($sentfile, 'w') or die('Unable to open file! (w)');
    fwrite($fp, time());
    fclose($fp);
    flist_send();
  }
  ignore_user_abort($iua);
}
?>
