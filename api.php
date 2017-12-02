<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';

header('Cache-Control: 3600', true);
header('Content-Type: text/plain', true);
header('Access-Control-Allow-Origin: *', true);

switch ($_SERVER['PATH_INFO']) {
 case '/version':
  http_response_code(200);
  echo $cfg__VERSION . "\n";
  break;

 case '/currencies':
  http_response_code(200);
  if ($cfg_BCH_enabled) echo "BCH\n";
  if ($cfg_BLK_enabled) echo "BLK\n";
  if ($cfg_BTC_enabled) echo "BTC\n";
  if ($cfg_BTX_enabled) echo "BTX\n";
  if ($cfg_DASH_enabled) echo "DASH\n";
  if ($cfg_DOGE_enabled) echo "DOGE\n";
  if ($cfg_ETH_enabled) echo "ETH\n";
  if ($cfg_LTC_enabled) echo "LTC\n";
  if ($cfg_PPC_enabled) echo "PPC\n";
  if ($cfg_XPM_enabled) echo "XPM\n";
  break;

 case '/claim_time':
  http_response_code(200);
  echo $cfg_refresh_time . "\n";
  break;

 case '/payout':
  if (isset($_GET['c'])) {
    switch ($_GET['c']) {
     case 'BCH':
     case 'BLK':
     case 'BTC':
     case 'BTX':
     case 'DASH':
     case 'DOGE':
     case 'ETH':
     case 'LTC':
     case 'PPC':
     case 'XPM':
      if (${'cfg_' . rawurlencode($_GET['c']) . '_enabled'}) {
        http_response_code(200);
        echo ${'cfg_' . rawurlencode($_GET['c']) . '_amount'} . "\n";
        break;
      }
     default:
      http_response_code(400);
      break;
    }
    break;
  }
  http_response_code(400);
  break;

 case '/payout_per_minute':
  if (isset($_GET['c'])) {
    switch ($_GET['c']) {
     case 'BCH':
     case 'BLK':
     case 'BTC':
     case 'BTX':
     case 'DASH':
     case 'DOGE':
     case 'ETH':
     case 'LTC':
     case 'PPC':
     case 'XPM':
      if (${'cfg_' . rawurlencode($_GET['c']) . '_enabled'}) {
        http_response_code(200);
        echo ((${'cfg_' . rawurlencode($_GET['c']) . '_amount'} / $cfg_refresh_time) * 60) . "\n";
        break;
      }
     default:
      http_response_code(400);
      break;
    }
    break;
  }
  http_response_code(400);
  break;

 case '/owner':
  http_response_code(200);
  echo $cfg_fh_username . "\n";
  break;

 default:
  http_response_code(400);
  echo 'Invalid request. Please see <https://github.com/sheshiresat/floodgate/blob/master/docs/api.adoc>.';
  break;
}
?>
