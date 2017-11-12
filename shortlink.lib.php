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

/* Alternate version using 1ink.cc (http://1ink.cc/?ref=16969)
 * Editing it should be trivial, just delete the eliwin version above and uncomment this one.
 * Be sure to change the file_get_contents URL to your account details if you want to get paid! */

/*
function shortlink_create($longurl) {
  return 'http://1ink.cc/' . file_get_contents('http://1ink.cc/api/create.php?uid=16969&uname=sheshiresat&url=' . urlencode($longurl));
}
*/

?>
