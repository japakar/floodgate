<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';

function ban_user($reason) {
  global $cfg_enable_ban;

  if ($cfg_enable_ban) {
    $ip = user_ip();
    if (!$ip) die('Error detecting IP.');

    $tmp = ignore_user_abort(true); // no idea if necessary, better safe than sorry

    if (!file_exists($_SERVER['DOCUMENT_ROOT'] . '/.htaccess'))
      $fp = fopen($_SERVER['DOCUMENT_ROOT'] . '/.htaccess', 'w') or die('Error.');
    else
      $fp = fopen($_SERVER['DOCUMENT_ROOT'] . '/.htaccess', 'a') or die('Error.');

    fwrite($fp, "\n#BAN: " . $reason . "\n");
    fwrite($fp, 'deny from ' . $ip . "\n");
    fclose($fp);

    ignore_user_abort($tmp);
  }
}

/* TODO: unban_ip(string ip) */
?>
