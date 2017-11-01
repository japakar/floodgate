<?php
  require_once 'config.php';

  if ($cfg_enable_nastyhosts) {
    /* Check with nastyhosts.com */
    $nh_result = file_get_contents('http://v1.nastyhosts.com/' . user_ip());
    $nh_result = json_decode($nh_result, true);
    if($nh_result["suggestion"] == 'deny') {
      header('Location: ' . $cfg_site_url . '/nastyhost.php', true, 302);
      exit;
    }
    unset($nh_result);
    /* I might be using too many different paradigms here... */
  }

  // TODO: put the claim timestamps in /tmp/* instead of ./*

  $amount = rand(9, 11) / 10;

  date_default_timezone_set('UTC');

  $dryrun = false;
  $errmsg = '';
  $overload = false;
  $paid = false;
  $too_fast = false;
  $referrer_abuse = false;

  require_once 'faucethub.php';

  if (isset($_GET['address']) && isset($_GET['currency'])) {
    $address = htmlspecialchars(stripslashes($_GET['address']));
    $currency = htmlspecialchars(stripslashes($_GET['currency']));

    if ($cfg_use_captcha) {
      if (isset($_GET['key'])) {
        if (htmlspecialchars(stripslashes($_GET['key'])) != md5($address . ' ' . $cfg_cookie_key)) {
          require_once 'ban.lib.php';
          ban_user('Invalid CAPTCHA key');
          die('Congragulations, you are banned!');
        }
      } else
        die('Missing CAPTCHA key.');
    }

    if ((strlen($address) < 1) || (strlen($currency) < 1)) {
      $errmsg = '<p>One of the parameters is empty.</p>';
      goto end_payout;
    }

    switch ($currency) {
      case 'BCH':
        if (!$cfg_BCH_enabled) {$errmsg = '<p>Invalid currency. Nice try.</p>'; goto end_payout;}
        $faucethub = new FaucetHub($cfg_fh_api_key, 'BCH');
        break;
      case 'BLK':
        if (!$cfg_BLK_enabled) {$errmsg = '<p>Invalid currency. Nice try.</p>'; goto end_payout;}
        $faucethub = new FaucetHub($cfg_fh_api_key, 'BLK');
        break;
      case 'BTC':
        if (!$cfg_BTC_enabled) {$errmsg = '<p>Invalid currency. Nice try.</p>'; goto end_payout;}
        $faucethub = new FaucetHub($cfg_fh_api_key, 'BTC');
        break;
      case 'DASH':
        if (!$cfg_DASH_enabled) {$errmsg = '<p>Invalid currency. Nice try.</p>'; goto end_payout;}
        $faucethub = new FaucetHub($cfg_fh_api_key, 'DASH');
        break;
      case 'DOGE':
        if (!$cfg_DOGE_enabled) {$errmsg = '<p>Invalid currency. Nice try.</p>'; goto end_payout;}
        $faucethub = new FaucetHub($cfg_fh_api_key, 'DOGE');
        break;
      case 'ETH':
        if (!$cfg_ETH_enabled) {$errmsg = '<p>Invalid currency. Nice try.</p>'; goto end_payout;}
        $faucethub = new FaucetHub($cfg_fh_api_key, 'ETH');
        break;
      case 'LTC':
        if (!$cfg_LTC_enabled) {$errmsg = '<p>Invalid currency. Nice try.</p>'; goto end_payout;}
        $faucethub = new FaucetHub($cfg_fh_api_key, 'LTC');
        break;
      case 'PPC':
        if (!$cfg_PPC_enabled) {$errmsg = '<p>Invalid currency. Nice try.</p>'; goto end_payout;}
        $faucethub = new FaucetHub($cfg_fh_api_key, 'PPC');
        break;
      case 'XPM':
        if (!$cfg_XPM_enabled) {$errmsg = '<p>Invalid currency. Nice try.</p>'; goto end_payout;}
        $faucethub = new FaucetHub($cfg_fh_api_key, 'XPM');
        break;
      default:
        $errmsg = '<p>Unknown currency.</p>';
        goto end_payout;
    }

    $current_time = time();
    $prev_time = 0;

    function too_fast_address($addr) {
      global $current_time;
      global $prev_time;
      global $cfg_refresh_time;
      global $cfg_fh_username;
      $tf = false;
      $pth = 'addresses/' . rawurlencode($addr);

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
        $errmsg = '<p>Error connecting to FaucetHUB to check address!</p><dl><dt>Status</dt><dd>' . $a['status'] . '</dd><dt>Message</dt><dd>' . $a['message'] . '</dd></dl>';
        if ($a['status'] == 441) $overload = true;
        goto end_payout;
      }

      if (!empty($user_hash)) {
        if (too_fast_hash($user_hash)) {
          $too_fast = true;
        } else {
          $user_ip = user_ip();

          if (!$user_ip) {
            $errmsg = '<p>Could not detect your IP address. I need it to help make sure you aren&#700;t an asshole!</p>';
            goto end_payout;
          }

          $referred = (isset($_GET['r']) ? true : false);

          if (!$referred) {
            if ($cfg_BCH_enabled) {
              if (file_exists('referrers/BCH/' . $address)) {
                $fp = fopen('referrers/BCH/' . $address, 'r') or die('I/O Error.');
                $referred = true;
                $refer_file = true;
                $referrer_currency = 'BCH';
                $referrer = fread($fp, filesize('referrers/BCH/' . $address));
                fclose($fp);
              }
            }
            if ($cfg_BLK_enabled) {
              if (file_exists('referrers/BLK/' . $address)) {
                $fp = fopen('referrers/BLK/' . $address, 'r') or die('I/O Error.');
                $referred = true;
                $refer_file = true;
                $referrer_currency = 'BLK';
                $referrer = fread($fp, filesize('referrers/BLK/' . $address));
                fclose($fp);
              }
            }
            if ($cfg_BTC_enabled) {
              if (file_exists('referrers/BTC/' . $address)) {
                $fp = fopen('referrers/BTC/' . $address, 'r') or die('I/O Error.');
                $referred = true;
                $refer_file = true;
                $referrer_currency = 'BTC';
                $referrer = fread($fp, filesize('referrers/BTC/' . $address));
                fclose($fp);
              }
            }
            if ($cfg_DASH_enabled) {
              if (file_exists('referrers/DASH/' . $address)) {
                $fp = fopen('referrers/DASH/' . $address, 'r') or die('I/O Error.');
                $referred = true;
                $refer_file = true;
                $referrer_currency = 'DASH';
                $referrer = fread($fp, filesize('referrers/DASH/' . $address));
                fclose($fp);
              }
            }
            if ($cfg_DOGE_enabled) {
              if (file_exists('referrers/DOGE/' . $address)) {
                $fp = fopen('referrers/DOGE/' . $address, 'r') or die('I/O Error.');
                $referred = true;
                $refer_file = true;
                $referrer_currency = 'DOGE';
                $referrer = fread($fp, filesize('referrers/DOGE/' . $address));
                fclose($fp);
              }
            }
            if ($cfg_ETH_enabled) {
              if (file_exists('referrers/ETH/' . $address)) {
                $fp = fopen('referrers/ETH/' . $address, 'r') or die('I/O Error.');
                $referred = true;
                $refer_file = true;
                $referrer_currency = 'ETH';
                $referrer = fread($fp, filesize('referrers/ETH/' . $address));
                fclose($fp);
              }
            }
            if ($cfg_LTC_enabled) {
              if (file_exists('referrers/LTC/' . $address)) {
                $fp = fopen('referrers/LTC/' . $address, 'r') or die('I/O Error.');
                $referred = true;
                $refer_file = true;
                $referrer_currency = 'LTC';
                $referrer = fread($fp, filesize('referrers/LTC/' . $address));
                fclose($fp);
              }
            }
            if ($cfg_PPC_enabled) {
              if (file_exists('referrers/PPC/' . $address)) {
                $fp = fopen('referrers/PPC/' . $address, 'r') or die('I/O Error.');
                $referred = true;
                $refer_file = true;
                $referrer_currency = 'PPC';
                $referrer = fread($fp, filesize('referrers/PPC/' . $address));
                fclose($fp);
              }
            }
            if ($cfg_XPM_enabled) {
              if (file_exists('referrers/XPM/' . $address)) {
                $fp = fopen('referrers/XPM/' . $address, 'r') or die('I/O Error.');
                $referred = true;
                $refer_file = true;
                $referrer_currency = 'XPM';
                $referrer = fread($fp, filesize('referrers/XPM/' . $address));
                fclose($fp);
              }
            }
          }

          if ($referred) {
            if (!$refer_file) {
              if (!isset($_GET['r']) || !isset($_GET['rc'])) {
                $errmsg = '<p>The referral URL is incorrect. It should have both &ldquo;<code>&amp;r=</code>&rdquo;, and &ldquo;<code>&amp;rc=</code>&rdquo;!</p>';
                goto end_payout;
              }

              $referrer = htmlspecialchars(stripslashes($_GET['r']));
              $referrer_currency = htmlspecialchars(stripslashes($_GET['rc']));
            }

            if ((strlen($referrer) < 1) || (strlen($referrer_currency) < 1)) {
              $errmsg = '<p>One of the parameters is empty.</p>';
              goto end_payout;
            }

            if ($referrer_currency == 'BCH') {
              $faucethub_ref = new FaucetHub($cfg_fh_api_key, 'BCH');
            } else if ($referrer_currency == 'BLK') {
              $faucethub_ref = new FaucetHub($cfg_fh_api_key, 'BLK');
            } else if ($referrer_currency == 'BTC') {
              $faucethub_ref = new FaucetHub($cfg_fh_api_key, 'BTC');
            } else if ($referrer_currency == 'DASH') {
              $faucethub_ref = new FaucetHub($cfg_fh_api_key, 'DASH');
            } else if ($referrer_currency == 'DOGE') {
              $faucethub_ref = new FaucetHub($cfg_fh_api_key, 'DOGE');
            } else if ($referrer_currency == 'ETH') {
              $faucethub_ref = new FaucetHub($cfg_fh_api_key, 'ETH');
            } else if ($referrer_currency == 'LTC') {
              $faucethub_ref = new FaucetHub($cfg_fh_api_key, 'LTC');
            } else if ($referrer_currency == 'PPC') {
              $faucethub_ref = new FaucetHub($cfg_fh_api_key, 'PPC');
            } else if ($referrer_currency == 'XPM') {
              $faucethub_ref = new FaucetHub($cfg_fh_api_key, 'XPM');
            } else {
              $errmsg = '<p>Invalid referrer currency.</p>';
              goto end_payout;
            }

            $a = $faucethub_ref->checkAddress($referrer, $referrer_currency);
            if (isset($a['payout_user_hash'])) {
              $referrer_hash = $a['payout_user_hash'];
            } else {
              $errmsg = '<p>Error connecting to FaucetHUB to check referral!</p><dl><dt>Status</dt><dd>' . $a['status'] . '</dd><dt>Message</dt><dd>' . $a['message'] . '</dd></dl>';
              if ($a['status'] == 441) $overload = true;
              goto end_payout;
            }

            if ($referrer_hash == $user_hash) {
              $referrer_abuse = true;
            } else {
              $faucethub_ref->sendReferralEarnings($referrer, intval(($amount * ${'cfg_' . $referrer_currency . '_amount'}) / 2));
              if (!$refer_file) {
                if (!file_exists('referrers/' . $referrer_currency . '/' . $address)) {
                  $fp = fopen('referrers/' . $referrer_currency . '/' . $address, 'w');
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title><?php echo $cfg_site_name; ?></title>
<?php
  if ($paid) {
    if ($result['success'] === true) {
      echo '<script type="text/javascript">setTimeout("location.reload(true);",' . ($cfg_real_refresh_time * 1000) . ');</script>';
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
<link rel="stylesheet" href="/main.css"/>
<?php include 'head.i.php'; ?>
</head>
<body>
<header><?php include 'navbar.i.php'; ?></header>
<main>
<h1><?php echo $cfg_site_name; ?></h1>
<?php
  if ($paid) {
    echo $result['html'];

    if ($result['success'] === true) {
      echo '<p>Just leave this page open, and it should automatically refresh and send you more ' . $currency . ' every ' . ($cfg_refresh_time / 60) . ' minutes!</p>';
      echo '<p>Referral link: <code>' . $cfg_site_url . '?r=' . $address . '&amp;rc=' . $currency . '</code></p>';
      if ($referred) {
        echo '<p>(Some ' . $referrer_currency . ' was sent to ' . $referrer . ' as well.)</p>';
      }
    } else if (json_decode($result['response'], true)['status'] == 402) {
      echo '<p>The faucet is out of this particular currency. Want to try another one?</p>';
      echo '<p>(A few currencies depend on the exchange, the faucet will usually be re-filled once ' . $cfg_fh_username . '&#700;s &lsquo;buy&rsquo; orders are filled.) (If you are <em>really</em> impatient, perhaps someone will /tip ' . $cfg_fh_username . ' some ' . $currency . '?)</p>';
      echo '<p>(I would put a little &ldquo;faucet balance&rdquo; widget on the main page, but that would currently result in a <em>TON</em> of API requests&hellip;)</p>';
    } else {
      echo '<p>Please contact ' . $cfg_fh_username . ' on FaucetHUB and let them know; they can&#700;t see the errors due to the huge volume of claims!</p>';
      echo '<dl><dt>Status</dt><dd>' . $result['status'] . '</dd><dt>Message</dt><dd>' . $result['message'] . '</dd></dl>';
      echo '<hr/>';
      echo '<p>Tried to send ' . intval($amount * (${'cfg_' . $currency . '_amount'} * $cfg_refresh_time)) . '</p>';
      echo '<p>intval(' . $amount . ' &times; (' . ${'cfg_' . $currency . '_amount'} . ' &times; ' . $cfg_refresh_time . '));</p>';
    }
  } else {
    if ($dryrun) {
      echo '<p>No claim detected, here is what the page looks like.</p>';
    } else if ($too_fast) {
      echo '<p>Just leave this page open, and it should automatically send you more ' . $currency . ' every ' . ($cfg_refresh_time / 60) . ' minutes!</p>';
      echo '<p>Time until next payout: ' . (($prev_time + $cfg_refresh_time) - $current_time) . ' seconds.</p>';
      echo '<p>Referral link: <code>' . $cfg_site_url . '?r=' . $address . '&amp;rc=' . $currency . '</code></p>';
      echo '<hr/><p>Timestamp of last claim: <time>' . $prev_time . '</time></p>';
      echo '<hr/><p>Timestamp of last refresh: <time>' . $current_time . '</time></p>';
    } else if ($referrer_abuse) {
      echo '<p>You seem to have tried to cheat the system by double-claiming.</p>';
      echo '<p>This means that the referral address belongs to you.</p>';
      echo '<p>You can get in <em>deep</em> trouble if you do this on a faucet that does not block it; Faucet&nbsp;Hub will automatically detect it and freeze your account and reverse every faucet claim you have made.</p>';
    } else {
      echo $errmsg;
    }
  }
?>
<hr/>
<p><strong>Do not bookmark this page!</strong> Use <a href="<?php echo $cfg_site_url; ?>"><?php echo $cfg_site_url; ?></a> instead. (I randomly change the URL of the &ldquo;claim&rdquo; page and ban anyone who visits the old URL after a certain amount of time.)</p>
<hr/>
<?php include 'iframetraffic.i.php'; ?>
</main>
<footer>
<?php include 'ads.i.php'; ?>
</footer>
</body>
</html>
