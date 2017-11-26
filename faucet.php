<?php
  require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';

  if ($cfg_enable_nastyhosts) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/nastyhosts.php';
    if (check_nastyhosts(user_ip())) {
      header('Location: ' . $cfg_site_url . '/nastyhost.php', true, 302);
      exit;
    }
  }

  if ($cfg_enable_iphub) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/iphub.php';
    if (check_iphub(user_ip())) {
      header('Location: ' . $cfg_site_url . '/iphub_block.php', true, 302);
      exit;
    }
  }

  $amount = rand(9, 11) / 10;

  include $_SERVER['DOCUMENT_ROOT'] . '/custom/claim_options_process.php';

  date_default_timezone_set(date_default_timezone_get());

  $dryrun = false;
  $errmsg = '';
  $overload = false;
  $paid = false;
  $too_fast = false;
  $referrer_abuse = false;

  require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/faucethub.php';

  if (isset($_GET['address']) && isset($_GET['currency'])) {
    $address = '' . $_GET['address'];
    $currency = '' . $_GET['currency'];

    if ($cfg_use_captcha || $cfg_use_shortlink) {
      if (isset($_GET['key'])) {
        if (rawurldecode($_GET['key']) != md5($address . ' ' . $cfg_cookie_key)) {
          http_response_code(400);
          require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/ban.php';
          ban_user('Invalid key');
          die('Congragulations, you are banned!');
        }
      } else {
        http_response_code(400);
        die('Missing key.');
      }
    }

    if ((strlen($address) < 1) || (strlen($currency) < 1)) {
      http_response_code(400);
      $errmsg = '<p>One of the parameters is empty.</p>';
      goto end_payout;
    }

    switch ($currency) {
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
        if (!${'cfg_' . $currency . '_enabled'}) {
          http_response_code(400);
          $errmsg = '<p>Invalid currency. Nice try.</p>';
          goto end_payout;
        }
        $faucethub = new FaucetHub(${'cfg_' . $currency . '_api_key'}, $currency);
        break;
      default:
        http_response_code(400);
        $errmsg = '<p>Unknown currency.</p>';
        goto end_payout;
    }

    $current_time = time();
    $prev_time = 0;

    if (!file_exists(sys_get_temp_dir() . '/floodgate'))
      mkdir(sys_get_temp_dir() . '/floodgate');
    if (!file_exists(sys_get_temp_dir() . '/floodgate/addresses'))
      mkdir(sys_get_temp_dir() . '/floodgate/addresses');

    function too_fast_address($addr) {
      global $current_time;
      global $prev_time;
      global $cfg_refresh_time;
      global $cfg_fh_username;
      $tf = false;
      $pth = sys_get_temp_dir() . '/floodgate/addresses/' . rawurlencode($addr);

      if (file_exists($pth)) {
        $fp = fopen($pth, 'r') or die('Unable to open file! <strong>Alert ' . $cfg_fh_username . ' immediately</strong>!');
        $prev_time = intval(fread($fp, filesize($pth)));
        fclose($fp);
        $tf = ($current_time - $prev_time) < $cfg_refresh_time;
      }

      if (!$tf) {
        $fp = fopen($pth, 'w') or die('Unable to open file! <strong>Alert ' . $cfg_fh_username . ' immediately</strong>!');
        fwrite($fp, $current_time);
        fclose($fp);
      }

      return $tf;
    }

    function too_fast_hash($hash) {
      global $current_time;
      global $faucethub;
      global $prev_time;
      global $cfg_refresh_time;
      global $cfg_fh_username;
      $tf = false;
      $pth = 'users/' . rawurlencode($hash);

      if (file_exists($pth)) {
        $fp = fopen($pth, 'r') or die('Unable to open file! <strong>Alert ' . $cfg_fh_username . ' immediately</strong>!');
        $prev_time = intval(fread($fp, filesize($pth)));
        fclose($fp);
        $tf = ($current_time - $prev_time) < $cfg_refresh_time;
      }

      if (!$tf) {
        $fp = fopen($pth, 'w') or die('Unable to open file! <strong>Alert ' . $cfg_fh_username . ' immediately</strong>!');
        fwrite($fp, $current_time);
        fclose($fp);
      }

      return $tf;
    }

    if (too_fast_address($address)) {
      $too_fast = true;
    } else {
      $a = $faucethub->checkAddress($address, $currency);
      if (isset($a['payout_user_hash'])) {
        $user_hash = $a['payout_user_hash'];
      } else {
        http_response_code(502);
        $errmsg = '<p>Error connecting to FaucetHUB to check address!</p><dl><dt>Status</dt><dd>' . $a['status'] . '</dd><dt>Message</dt><dd>' . $a['message'] . '</dd></dl>';
        if ($a['status'] == 441) {
          http_response_code(503);
          $overload = true;
        }
        goto end_payout;
      }

      if (!empty($user_hash)) {
        if (too_fast_hash($user_hash)) {
          $too_fast = true;
        } else {
          $user_ip = user_ip();

          if (!$user_ip) {
            http_response_code(400);
            $errmsg = '<p>Could not detect your IP address. I need it to help make sure you aren&#700;t an asshole!</p>';
            goto end_payout;
          }

          $referred = !!isset($_GET['r']);

          if (!$referred) {
            if ($cfg_BCH_enabled) {
              if (file_exists('referrers/BCH/' . rawurlencode($address))) {
                $fp = fopen('referrers/BCH/' . rawurlencode($address), 'r') or die('I/O Error.');
                $referred = true;
                $refer_file = true;
                $referrer_currency = 'BCH';
                $referrer = fread($fp, filesize('referrers/BCH/' . rawurlencode($address)));
                fclose($fp);
              }
            }
            if ($cfg_BLK_enabled) {
              if (file_exists('referrers/BLK/' . rawurlencode($address))) {
                $fp = fopen('referrers/BLK/' . rawurlencode($address), 'r') or die('I/O Error.');
                $referred = true;
                $refer_file = true;
                $referrer_currency = 'BLK';
                $referrer = fread($fp, filesize('referrers/BLK/' . rawurlencode($address)));
                fclose($fp);
              }
            }
            if ($cfg_BTC_enabled) {
              if (file_exists('referrers/BTC/' . rawurlencode($address))) {
                $fp = fopen('referrers/BTC/' . rawurlencode($address), 'r') or die('I/O Error.');
                $referred = true;
                $refer_file = true;
                $referrer_currency = 'BTC';
                $referrer = fread($fp, filesize('referrers/BTC/' . rawurlencode($address)));
                fclose($fp);
              }
            }
            if ($cfg_BTX_enabled) {
              if (file_exists('referrers/BTX/' . rawurlencode($address))) {
                $fp = fopen('referrers/BTX/' . rawurlencode($address), 'r') or die('I/O Error.');
                $referred = true;
                $refer_file = true;
                $referrer_currency = 'BTX';
                $referrer = fread($fp, filesize('referrers/BTX/' . rawurlencode($address)));
                fclose($fp);
              }
            }
            if ($cfg_DASH_enabled) {
              if (file_exists('referrers/DASH/' . rawurlencode($address))) {
                $fp = fopen('referrers/DASH/' . rawurlencode($address), 'r') or die('I/O Error.');
                $referred = true;
                $refer_file = true;
                $referrer_currency = 'DASH';
                $referrer = fread($fp, filesize('referrers/DASH/' . rawurlencode($address)));
                fclose($fp);
              }
            }
            if ($cfg_DOGE_enabled) {
              if (file_exists('referrers/DOGE/' . rawurlencode($address))) {
                $fp = fopen('referrers/DOGE/' . rawurlencode($address), 'r') or die('I/O Error.');
                $referred = true;
                $refer_file = true;
                $referrer_currency = 'DOGE';
                $referrer = fread($fp, filesize('referrers/DOGE/' . rawurlencode($address)));
                fclose($fp);
              }
            }
            if ($cfg_ETH_enabled) {
              if (file_exists('referrers/ETH/' . rawurlencode($address))) {
                $fp = fopen('referrers/ETH/' . rawurlencode($address), 'r') or die('I/O Error.');
                $referred = true;
                $refer_file = true;
                $referrer_currency = 'ETH';
                $referrer = fread($fp, filesize('referrers/ETH/' . rawurlencode($address)));
                fclose($fp);
              }
            }
            if ($cfg_LTC_enabled) {
              if (file_exists('referrers/LTC/' . rawurlencode($address))) {
                $fp = fopen('referrers/LTC/' . rawurlencode($address), 'r') or die('I/O Error.');
                $referred = true;
                $refer_file = true;
                $referrer_currency = 'LTC';
                $referrer = fread($fp, filesize('referrers/LTC/' . rawurlencode($address)));
                fclose($fp);
              }
            }
            if ($cfg_PPC_enabled) {
              if (file_exists('referrers/PPC/' . rawurlencode($address))) {
                $fp = fopen('referrers/PPC/' . rawurlencode($address), 'r') or die('I/O Error.');
                $referred = true;
                $refer_file = true;
                $referrer_currency = 'PPC';
                $referrer = fread($fp, filesize('referrers/PPC/' . rawurlencode($address)));
                fclose($fp);
              }
            }
            if ($cfg_XPM_enabled) {
              if (file_exists('referrers/XPM/' . rawurlencode($address))) {
                $fp = fopen('referrers/XPM/' . rawurlencode($address), 'r') or die('I/O Error.');
                $referred = true;
                $refer_file = true;
                $referrer_currency = 'XPM';
                $referrer = fread($fp, filesize('referrers/XPM/' . rawurlencode($address)));
                fclose($fp);
              }
            }
          }

          if ($referred) {
            if (!$refer_file) {
              if (!isset($_GET['r']) || !isset($_GET['rc'])) {
                http_response_code(400);
                $errmsg = '<p>The referral URL is incorrect. It should have both &ldquo;<code>&amp;r=</code>&rdquo;, and &ldquo;<code>&amp;rc=</code>&rdquo;!</p>';
                goto end_payout;
              }

              $referrer = $_GET['r'];
              $referrer_currency = $_GET['rc'];
            }

            if ((strlen($referrer) < 1) || (strlen($referrer_currency) < 1)) {
              http_response_code(400);
              $errmsg = '<p>One of the parameters is empty.</p>';
              goto end_payout;
            }

            switch ($referrer_currency) {
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
                $faucethub_ref = new FaucetHub(${'cfg_' . $referrer_currency . '_api_key'}, $referrer_currency);
                break;
              default:
                http_response_code(400);
                $errmsg = '<p>Invalid referrer currency.</p>';
                goto end_payout;
            }

            $a = $faucethub_ref->checkAddress($referrer, $referrer_currency);
            if (isset($a['payout_user_hash'])) {
              $referrer_hash = $a['payout_user_hash'];
            } else {
              http_response_code(502);
              $errmsg = '<p>Error connecting to FaucetHUB to check referral!</p><dl><dt>Status</dt><dd>' . $a['status'] . '</dd><dt>Message</dt><dd>' . $a['message'] . '</dd></dl>';
              if ($a['status'] == 441) {
                http_response_code(503);
                $overload = true;
              }
              goto end_payout;
            }

            if ($referrer_hash == $user_hash) {
              http_response_code(400);
              $referrer_abuse = true;
            } else {
              $faucethub_ref->sendReferralEarnings($referrer, intval(($amount * ${'cfg_' . $referrer_currency . '_amount'}) / 2));
              if (!$refer_file) {
                if (!file_exists('referrers/' . rawurlencode($referrer_currency) . '/' . rawurlencode($address))) {
                  $fp = fopen('referrers/' . rawurlencode($referrer_currency) . '/' . rawurlencode($address), 'w');
                  if ($fp) {
                    fwrite($fp, $referrer);
                    fclose($fp);
                  }
                }
              }
            }
          }

          if (!$referred || !$referrer_abuse) {
            $result = $faucethub->send($address, intval($amount * ${'cfg_' . $currency . '_amount'}), false, $user_ip);
            $paid = true;
          }
        }
      }
    }
  } else {
    $dryrun = true;
  }

  end_payout:
  if (isset($too_fast) && $too_fast) {
    http_response_code(429);
    header('Retry-After: ' . (($prev_time + $cfg_refresh_time) - $current_time), true);
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title><?php echo $cfg_site_name; ?></title>
<link rel="stylesheet" href="/main.css"/>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/custom/head.php'; ?>
<?php
  if ($paid) {
    if ($result['success'] === true) {
      echo '<script type="text/javascript">setTimeout("';
      if ($cfg_enable_google_analytics) {
        echo 'window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}';
        echo 'gtag(\'event\', \'complete_claim\');';
      }
      echo 'location.reload(true);';
      echo '",' . ($cfg_real_refresh_time * 1000) . ');</script>';
    } else {
      if (json_decode($result['response'], true)['status'] == 441) {
        $overload = true;
        echo '<meta http-equiv="refresh" content="2;url=' . $cfg_site_url . '/441.php"/>';
      }
    }
  } else if ($overload) {
    echo '<meta http-equiv="refresh" content="2;url=' . $cfg_site_url . '/441.php"/>';
  }
  if ($too_fast) {
    echo '<script type="text/javascript">setTimeout("location.reload(true);",' . ($cfg_real_refresh_time * 1000) . ');</script>';
  }
?>
</head>
<body>
<header><?php include $_SERVER['DOCUMENT_ROOT'] . '/custom/navbar.php'; ?></header>
<main>
<h1><?php echo $cfg_site_name; ?></h1>
<?php
  if ($paid) {
    echo $result['html'];

    if ($result['success'] === true) {
      if ($cfg_enable_google_analytics) {
        echo '<script type="text/javascript">';
        echo 'gtag(\'event\', \'claim\', {';
        echo '\'currency\': \'' . htmlspecialchars($currency, ENT_QUOTES|ENT_SUBSTITUTE|ENT_DISALLOWED|ENT_HTML5) . '\',';
        if ($referred)
          echo '\'referred\': true,';
        else
          echo '\'referred\': false,';
        echo '});';
        echo '</script>';
      }
      echo '<p>Just leave this page open, and it should automatically refresh and send you more ' . htmlspecialchars($currency, ENT_QUOTES|ENT_SUBSTITUTE|ENT_DISALLOWED|ENT_HTML5) . ' every ' . ($cfg_refresh_time / 60) . ' minutes!</p>';
      echo '<p>Referral link: <code>' . $cfg_site_url . '?r=' . rawurlencode($address) . '&amp;rc=' . rawurlencode($currency) . '</code> (rotator owners, please append <code>&amp;rotator=YOUR_ROTATOR_NAME</code> to the URL)</p>';
      if ($referred)
        echo '<p>(Some ' . htmlspecialchars($referrer_currency, ENT_QUOTES|ENT_SUBSTITUTE|ENT_DISALLOWED|ENT_HTML5) . ' was sent to ' . htmlspecialchars($referrer, ENT_QUOTES|ENT_SUBSTITUTE|ENT_DISALLOWED|ENT_HTML5) . ' as well.)</p>';
    } else if (json_decode($result['response'], true)['status'] == 402) {
      if ($cfg_enable_google_analytics) {
        echo '<script type="text/javascript">';
        echo 'gtag(\'event\', \'faucet_dry\', {';
        echo '\'currency\': \'' . htmlspecialchars($currency, ENT_QUOTES|ENT_SUBSTITUTE|ENT_DISALLOWED|ENT_HTML5) . '\',';
        echo '});';
        echo '</script>';
      }
      echo '<p>The faucet is out of this particular currency. Want to try another one?</p>';
      echo '<p>(A few currencies depend on the exchange, the faucet will usually be re-filled once ' . $cfg_fh_username . '&#700;s &lsquo;buy&rsquo; orders are filled.) (If you are <em>really</em> impatient, perhaps someone will /tip ' . $cfg_fh_username . ' some ' . htmlspecialchars($currency, ENT_QUOTES|ENT_SUBSTITUTE|ENT_DISALLOWED|ENT_HTML5) . '?)</p>';
      echo '<p>(I would put a little &ldquo;faucet balance&rdquo; widget on the main page, but that would currently result in a <em>TON</em> of API requests&hellip;)</p>';
    } else {
      if ($cfg_enable_google_analytics) {
        echo '<script type="text/javascript">';
        echo 'gtag(\'event\', \'fh_error\', {';
        echo '\'status\': \'' . htmlspecialchars($result['status'], ENT_QUOTES|ENT_SUBSTITUTE|ENT_DISALLOWED|ENT_HTML5) . '\',';
        echo '});';
        echo '</script>';
      }
      echo '<p>Please contact ' . $cfg_fh_username . ' on FaucetHUB and let them know; they can&#700;t see the errors due to the huge volume of claims!</p>';
      echo '<dl><dt>Status</dt><dd>' . htmlspecialchars($result['status'], ENT_QUOTES|ENT_SUBSTITUTE|ENT_DISALLOWED|ENT_HTML5) . '</dd><dt>Message</dt><dd>' . htmlspecialchars($result['message'], ENT_QUOTES|ENT_SUBSTITUTE|ENT_DISALLOWED|ENT_HTML5) . '</dd></dl>';
    }
  } else {
    if ($dryrun) {
      if ($cfg_enable_google_analytics) {
        echo '<script type="text/javascript">';
        echo 'gtag(\'event\', \'dry_run\');';
        echo '</script>';
      }
      echo '<p>No claim detected, here is what the page looks like.</p>';
    } else if ($too_fast) {
      if ($cfg_enable_google_analytics) {
        echo '<script type="text/javascript">';
        echo 'gtag(\'event\', \'too_fast\', {';
        echo '\'currency\': \'' . htmlspecialchars($currency, ENT_QUOTES|ENT_SUBSTITUTE|ENT_DISALLOWED|ENT_HTML5) . '\',';
        echo '});';
        echo '</script>';
      }
      echo '<p>Just leave this page open, and it should automatically send you more ' . htmlspecialchars($currency, ENT_QUOTES|ENT_SUBSTITUTE|ENT_DISALLOWED|ENT_HTML5) . ' every ' . ($cfg_refresh_time / 60) . ' minutes!</p>';
      echo '<p>Time until next payout: ' . (($prev_time + $cfg_refresh_time) - $current_time) . ' seconds.</p>';
      echo '<p>Referral link: <code>' . $cfg_site_url . '?r=' . htmlspecialchars($address, ENT_QUOTES|ENT_SUBSTITUTE|ENT_DISALLOWED|ENT_HTML5) . '&amp;rc=' . htmlspecialchars($currency, ENT_QUOTES|ENT_SUBSTITUTE|ENT_DISALLOWED|ENT_HTML5) . '</code> (rotator owners, please append <code>&amp;rotator=YOUR_ROTATOR_NAME</code> to the URL)</p>';
      echo '<hr/><p>Timestamp of last claim: <time>' . $prev_time . '</time></p>';
      echo '<hr/><p>Timestamp of last refresh: <time>' . $current_time . '</time></p>';
    } else if ($referrer_abuse) {
      if ($cfg_enable_google_analytics) {
        echo '<script type="text/javascript">';
        echo 'gtag(\'event\', \'double_claim\');';
        echo '</script>';
      }
      echo '<p>You seem to have tried to cheat the system by double-claiming.</p>';
      echo '<p>This means that the referral address belongs to you.</p>';
      echo '<p>You can get in <em>deep</em> trouble if you do this on a faucet that does not block it; Faucet&nbsp;Hub will automatically detect it and freeze your account and reverse every faucet claim you have made.</p>';
    } else {
      if ($cfg_enable_google_analytics) {
        echo '<script type="text/javascript">';
        echo 'gtag(\'event\', \'error\');';
        echo '</script>';
      }
      echo $errmsg;
    }
  }
?>
<hr/>
<p><strong>Do not bookmark this page!</strong> Use <a href="<?php echo $cfg_site_url; ?>"><?php echo $cfg_site_url; ?></a> instead. (If the claim URL changes and you visit this page directly, you might be mistaken for a bot and banned.)</p>
<hr/>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/custom/iframetraffic.php'; ?>
</main>
<footer><?php include $_SERVER['DOCUMENT_ROOT'] . '/custom/ads.php'; ?></footer>
</body>
</html>
