<?php
require_once 'config.php';

// returns true if nastyhosts suggests denying the address
function check_nastyhosts($ip) {
  global $cfg_nasythost_whitelist;

  if (isset($cfg_nastyhost_whitelist[user_ip()]))
    return false;

  $nh_result = file_get_contents('http://v1.nastyhosts.com/' . $ip);
  $nh_result = json_decode($nh_result, true);

  return ($nh_result["suggestion"] == 'deny');
}
?>
