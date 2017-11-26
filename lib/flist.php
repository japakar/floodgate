<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';

function flist_send() { // hack since byethost doesn't seem to accept my POSTs
  global $cfg_site_url;
  echo '<iframe src="http://floodgates.byethost18.com/a.php?url=' . rawurlencode($cfg_site_url) . '" style="height:1px;width:1px" sandbox="allow-forms allow-scripts allow-same-origin"></iframe>';
}

function flist_auto() {
  date_default_timezone_set(date_default_timezone_get());
  $sentfile = $_SERVER['DOCUMENT_ROOT'] . '/_fregistered';

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
}
?>
