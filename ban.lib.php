<?php
require_once 'config.php';

function ban_user($reason) {
  global $cfg_enable_ban;

  if ($cfg_enable_ban) {
    $ip = user_ip();
    if (!$ip) die('Error detecting IP.');

    if (!file_exists('.htaccess'))
      $fp = fopen('.htaccess', 'w') or die('Error.');
    else
      $fp = fopen('.htaccess', 'a') or die('Error.');

    fwrite($fp, "\n#BAN: " . $reason . "\n");
    fwrite($fp, 'deny from ' . $ip . "\n");
    fclose($fp);
  }
}

/* TODO: unban_ip(string ip) */
?>
