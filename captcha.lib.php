<?php
require_once 'config.php';

/* Echoes the HTML code of the CAPTCHA for embedding it in a form. */
function embed_captcha() {
  global $cfg_coinhive_captcha_site;

  echo '<script src="https://coinhive.com/lib/captcha.min.js" async></script>';
  echo '<div class="coinhive-captcha" data-hashes="256" data-key="' . $cfg_coinhive_captcha_site . '" data-disable-elements="#start_claiming">';
  echo '<em>Loading Captcha&hellip;<br/>If it doesn&#700;t load, please disable Adblock.</em>';
  echo '</div>';
}

/* Used in verify.php, return true if the CAPTCHA is successfully solved, or false. */
function verify_captcha() {
  global $cfg_coinhive_captcha_secret;

  $post_data = [
    'secret' => $cfg_coinhive_captcha_secret,
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

  return ($response && $response->success);
}
?>
