<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';

/* Create a shortlink from the given URL. Only needs to be valid for about 20 minutes. */
function shortlink_create(string $longurl) {
  global $cfg_eliwin_key;

  $result = @json_decode(file_get_contents('http://btc.ms/api/?api=' . $cfg_eliwin_key . '&url=' . urlencode($longurl)), true);

  if($result['status'] === 'error')
    die($result['message']);
  else
    return $result['shortenedUrl'];
}

/* Other shortlink options
 * http://shortlinks.japakar.com
 * http://btc.ms/ref/japakar
 * Be sure to change the file_get_contents URL to your account details if you want to get paid! */

/*
function shortlink_create(string $longurl) {
  return 'http://1ink.cc/' . file_get_contents('http://1ink.cc/api/create.php?uid=16969&uname=sheshiresat&url=' . urlencode($longurl));
}
*/

?>
