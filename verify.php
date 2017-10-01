<?php
require 'config.php';

$claim_url = $cfg_site_url . '/claim.php?';

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

$post_data = [
	'secret' => $cfg_coinhive_secret,
	'token' => $_POST['coinhive-captcha-token'],
	'hashes' => 256
];

$post_context = stream_context_create([
	'http' => [
		'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
		'method'  => 'POST',
		'content' => http_build_query($post_data)
	]
]);

$url = 'https://api.coinhive.com/token/verify';
$response = json_decode(file_get_contents($url, false, $post_context));

if ($response && $response->success) {
  setcookie($cfg_fh_username . '_captcha_key', $cfg_cookie_key, time() + (86400 * 2));
} else {die('Failed to verify CAPTCHA.');}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Redirecting&hellip;</title>
<meta http-equiv="refresh" content="2;url=<?php echo $claim_url; ?>"/>
</head>
<body>
<main>
<p><a href="<?php echo $claim_url; ?>">Click here if you are not automatically redirected.</a></p>
</main>
</body>
</html>
