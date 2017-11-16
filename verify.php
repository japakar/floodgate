<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';

$claim_url = $cfg_site_url . '/faucet.php?';

$first = true;
foreach ($_POST as $key => $value) {
  if ($first) {
    $claim_url .= urlencode($key) . '=' . urlencode(htmlspecialchars(stripslashes($value)));
    $first = false;
  } else {
    $claim_url .= '&' . urlencode($key) . '=' . urlencode(htmlspecialchars(stripslashes($value)));
  }
}
unset($key);
unset($value);
unset($first);

if ($cfg_use_captcha) {
  require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/captcha.php';

  if (!verify_captcha())
    die('Failed to verify CAPTCHA.');
}

$claim_url .= '&key=' . urlencode(md5(htmlspecialchars(stripslashes($_POST['address'])) . ' ' . $cfg_cookie_key));

if ($cfg_use_shortlink) {
  require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/shortlink.php';

  $claim_url = shortlink_create($claim_url);
}

header('Location: ' . $claim_url, true, 303);
?>
