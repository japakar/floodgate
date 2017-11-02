<?php
require_once 'config.php';

/* Create a shortlink from the given URL. Only needs to be valid for about 20 minutes. */
function shortlink_create($longurl) {
  global $cfg_eliwin_key;

  $result = @json_decode(file_get_contents('https://elibtc.win/api?api=' . $cfg_eliwin_key . '&url=' . urlencode($longurl)), TRUE);
  if($result['status'] === 'error')
    die($result['message']);
  else
    return $result['shortenedUrl'];
}
?>
