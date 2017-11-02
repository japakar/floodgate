<?php
require_once 'config.php';

$claim_url = $cfg_site_url . '/faucet.php?';

if (isset($_POST['address'])) {
  $claim_url = $claim_url . '&address=' . htmlspecialchars(stripslashes($_POST['address']));
}
if (isset($_POST['currency'])) {
  $claim_url = $claim_url . '&currency=' . htmlspecialchars(stripslashes($_POST['currency']));
}
if (isset($_POST['r'])) {
  $claim_url = $claim_url . '&r=' . htmlspecialchars(stripslashes($_POST['r']));
}
if (isset($_POST['rc'])) {
  $claim_url = $claim_url . '&rc=' . htmlspecialchars(stripslashes($_POST['rc']));
}
if (isset($_POST['miner'])) {
  $claim_url = $claim_url . '&miner=' . htmlspecialchars(stripslashes($_POST['miner']));
}

if ($cfg_use_captcha) {
  require_once 'captcha.lib.php';

  if (verify_captcha())
    $claim_url = $claim_url . '&key=' . urlencode(md5(htmlspecialchars(stripslashes($_POST['address'])) . ' ' . $cfg_cookie_key));
  else
    die('Failed to verify CAPTCHA.');
} else if ($cfg_use_shortlink) {
  require_once 'shortlink.lib.php';

  $claim_url = $claim_url . '&key=' . urlencode(md5(htmlspecialchars(stripslashes($_POST['address'])) . ' ' . $cfg_cookie_key));
  $claim_url = shortlink_create($claim_url);
}
?><!DOCTYPE html><html lang="en"><head><title>Redirecting&hellip;</title><meta http-equiv="refresh" content="2;url=<?php echo $claim_url; ?>"/></head><body><main><p><a href="<?php echo $claim_url; ?>">Click here if you are not automatically redirected.</a></p></main></body></html>
