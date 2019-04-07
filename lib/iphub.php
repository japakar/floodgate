<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';

// returns true if iphub suggests blocking the address
function check_iphub(string $ip) {
  global $cfg_iphub_block_level;
  global $cfg_iphub_key;
  global $cfg_iphub_whitelist;

  if (isset($cfg_iphub_whitelist[$ip]))
    return false;

  $context = stream_context_create([
    'http' => [
      'header'  => 'X-Key: ' . $cfg_iphub_key . "\r\n",
      'method'  => 'GET',
    ]
  ]);

  $response = json_decode(file_get_contents('http://v2.api.iphub.info/ip/' . $ip, false, $context));

  return ($response->block == $cfg_iphub_block_level);
}
?>
