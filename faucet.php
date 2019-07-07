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
  $too_fast = false;
  $referrer_abuse = false;
  $faucet_empty = false;

  require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/faucethub.php';

  if (!isset($_GET['address']) || !isset($_GET['currency'])) {
    $dry_run = true;
    goto end_payout;
  }

  $address = '' . $_GET['address'];
  $currency = '' . $_GET['currency'];

  if ($cfg_use_captcha || $cfg_use_shortlink) {
    if (isset($_GET['key'])) {
      if ($_GET['key'] != md5($address . ' ' . $cfg_cookie_key)) {
        http_response_code(400);
        require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/ban.php';
        ban_user('Invalid key');
        die('Cheating detected, Start over from main page!');
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
   case 'DGB':
   case 'DOGE':
   case 'ETH':
   case 'HORA':
   case 'LTC':
   case 'POT':
   case 'PPC':
   case 'TRX':
   case 'XMR':
   case 'XPM':
   case 'ZEC':
    if (!${'cfg_' . $currency . '_enabled'}) {
      http_response_code(400);
      $errmsg = '<p>Invalid currency. Nice try.</p>';
      goto end_payout;
    }
    $faucethub = new FaucetHub(${'cfg_' . $currency . '_api_key'}, $currency, false);
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
    goto end_payout;
  }

  $a = $faucethub->checkAddress($address, $currency);
  if (!isset($a['payout_user_hash'])) {
    http_response_code(502);
    $errmsg = '<p>Error connecting to FaucetHUB to check address!</p>'
            . '<dl>'
            .   '<dt>Status</dt>'
            .   '<dd>' . $a['status'] . '</dd>'
            .   '<dt>Message</dt>'
            .   '<dd>' . $a['message'] . '</dd>'
            . '</dl>';

    if ($a['status'] == 441) {
      http_response_code(503);
      $overload = true;
    }

    goto end_payout;
  }
  $user_hash = $a['payout_user_hash'];
  unset($a);

  if (empty($user_hash)) {
    goto end_payout;
  }

  if (too_fast_hash($user_hash)) {
    $too_fast = true;
    goto end_payout;
  }

  $user_ip = user_ip();
  if (!$user_ip) {
    http_response_code(400);
    $errmsg = '<p>Could not detect IP address.</p>';
    goto end_payout;
  }

  $referred = isset($_GET['r']);

  if (!$referred) { // referral url not set, check for saved referrer
    /* TODO: replace with generated code? */
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
	    if ($cfg_DGB_enabled) {
      if (file_exists('referrers/DGB/' . rawurlencode($address))) {
        $fp = fopen('referrers/DGB/' . rawurlencode($address), 'r') or die('I/O Error.');
        $referred = true;
        $refer_file = true;
        $referrer_currency = 'DGB';
        $referrer = fread($fp, filesize('referrers/DGB/' . rawurlencode($address)));
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
    if ($cfg_HORA_enabled) {
      if (file_exists('referrers/HORA/' . rawurlencode($address))) {
        $fp = fopen('referrers/HORA/' . rawurlencode($address), 'r') or die('I/O Error.');
        $referred = true;
        $refer_file = true;
        $referrer_currency = 'HORA';
        $referrer = fread($fp, filesize('referrers/HORA/' . rawurlencode($address)));
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
    if ($cfg_POT_enabled) {
      if (file_exists('referrers/POT/' . rawurlencode($address))) {
        $fp = fopen('referrers/POT/' . rawurlencode($address), 'r') or die('I/O Error.');
        $referred = true;
        $refer_file = true;
        $referrer_currency = 'POT';
        $referrer = fread($fp, filesize('referrers/POT/' . rawurlencode($address)));
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
	    if ($cfg_TRX_enabled) {
      if (file_exists('referrers/TRX/' . rawurlencode($address))) {
        $fp = fopen('referrers/TRX/' . rawurlencode($address), 'r') or die('I/O Error.');
        $referred = true;
        $refer_file = true;
        $referrer_currency = 'TRX';
        $referrer = fread($fp, filesize('referrers/TRX/' . rawurlencode($address)));
        fclose($fp);
      }
    }
	    if ($cfg_XMR_enabled) {
      if (file_exists('referrers/XMR/' . rawurlencode($address))) {
        $fp = fopen('referrers/XMR/' . rawurlencode($address), 'r') or die('I/O Error.');
        $referred = true;
        $refer_file = true;
        $referrer_currency = 'XMR';
        $referrer = fread($fp, filesize('referrers/XMR/' . rawurlencode($address)));
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
    if ($cfg_ZEC_enabled) {
      if (file_exists('referrers/ZEC/' . rawurlencode($address))) {
        $fp = fopen('referrers/ZEC/' . rawurlencode($address), 'r') or die('I/O Error.');
        $referred = true;
        $refer_file = true;
        $referrer_currency = 'ZEC';
        $referrer = fread($fp, filesize('referrers/ZEC/' . rawurlencode($address)));
        fclose($fp);
      }
    }
  }

  if ($referred) {
    if (!$refer_file) {
      if (!isset($_GET['r']) || !isset($_GET['rc'])) {
        http_response_code(400);
        $errmsg = '<p>The referral URL is incorrect. It should have both <code>&amp;r=&hellip;</code> <em>and</em> <code>&amp;rc=&hellip;</code>!</p>';
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
	 case 'DGB':
     case 'DOGE':
     case 'ETH':
	 case 'HORA':
     case 'LTC':
     case 'POT':
     case 'PPC':
	 case 'TRX':
	 case 'XMR':
     case 'XPM':
     case 'ZEC':
      $faucethub_ref = new FaucetHub(${'cfg_' . $referrer_currency . '_api_key'}, $referrer_currency, false);
      break;
     default:
      http_response_code(400);
      $errmsg = '<p>Invalid referrer currency.</p>';
      goto end_payout;
    }

    $a = $faucethub_ref->checkAddress($referrer, $referrer_currency);
    if (!isset($a['payout_user_hash'])) {
      http_response_code(502);
      $errmsg = '<p>Error connecting to FaucetHUB to check referral!</p>'
              . '<dl>'
              .   '<dt>Status</dt>'
              .   '<dd>' . $a['status'] . '</dd>'
              .   '<dt>Message</dt>'
              .   '<dd>' . $a['message'] . '</dd>'
              . '</dl>';

      if ($a['status'] == 441) {
        http_response_code(503);
        $overload = true;
      }

      goto end_payout;
    }
    $referrer_hash = $a['payout_user_hash'];
    unset($a);

    if ($referrer_hash == $user_hash) {
      http_response_code(400);
      $referrer_abuse = true;
      goto end_payout;
    }

    $ref_result = $faucethub_ref->sendReferralEarnings(
                   $referrer,
                   intval(($amount
                           * ${'cfg_' . $referrer_currency . '_amount'})
                          / 2)
                  );

    if (!$refer_file) { // the user was referred and the referral isn't saved
      if (!file_exists('referrers/' . rawurlencode($referrer_currency) . '/' . rawurlencode($address))) {
        $fp = fopen('referrers/' . rawurlencode($referrer_currency) . '/' . rawurlencode($address), 'w');
        if ($fp) {
          fwrite($fp, $referrer);
          fclose($fp);
        }
      }
    }
  }

  $result = $faucethub->send(
             $address,
             intval($amount * ${'cfg_' . $currency . '_amount'}),
             false,
             $user_ip
            );

 end_payout:
  if (isset($result)) {
    switch (json_decode($result['response'], true)['status']) {
     case 402:
      $faucet_empty = true;
      break;
     case 441:
      $overload = true;
      break;
     default:
      break;
    }
  }

  if (isset($ref_result)) {
    switch (json_decode($ref_result['response'], true)['status']) {
     case 402:
      $faucet_empty = true;
      break;
     case 441:
      $overload = true;
      break;
     default:
      break;
    }
  }

  if ($too_fast) {
    http_response_code(429);
    header(
     'Retry-After: ' . (($prev_time + $cfg_refresh_time) - $current_time),
     true
    );
  }

  if ($overload) {
    http_response_code(503);
  }
  if ($faucet_empty) {
    http_response_code(503);
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>

<link rel="stylesheet" type="text/css" href="website.css">

<title><?php echo $cfg_site_name; ?></title>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/custom/head.php'; ?>
<?php
  if ($overload) {
    echo '<meta http-equiv="refresh" content="2;url=' . $cfg_site_url . '/441.php"/>';
  } else if ($too_fast) {
    echo '<script type="text/javascript">setTimeout("location.reload(true);",' . ($cfg_real_refresh_time * 1000) . ');</script>';
  } else if (isset($result)) {
    if ($result['success'] === true) {
      echo '<script type="text/javascript">setTimeout("';
      if ($cfg_enable_google_analytics) {
        echo 'window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}';
        echo 'gtag(\'event\', \'complete_claim\');';
      }
      echo 'location.reload(true);';
      echo '",' . ($cfg_real_refresh_time * 1000) . ');</script>';
    }
  }
?>

<style>
table, th, td {
    border: 1px solid black;
    border-radius: 25px;
}
</style>

</head> 
            
<body>


<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = 'https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v2.11';
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>


<?php include $_SERVER['DOCUMENT_ROOT'] . '/bannernavbar.php';?>



<div class="row">
  <div class="column side">
<br>
    Put ads here!
<br>
	<center>


	</center>
  </div>
  
  
  
  
  <div class="column middle">
<center><table width="99%" bgcolor="#F2F3F4"></center>
  <tr>
    <td><center><h2>Welcome</h2></center>
<h1><?php echo $cfg_site_name; ?></h1>
<?php
  if ($dryrun) {
    if ($cfg_enable_google_analytics) {
      echo '<script type="text/javascript">';
      echo 'gtag(\'event\', \'dry_run\');';
      echo '</script>';
    }
    echo '<p>No claim detected, here is what the page looks like.</p>';
  } else {
    if ($faucet_empty) {
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
    } else if (isset($result)) {
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
        echo '<p>Referral link: <code>' . htmlspecialchars($cfg_site_url, ENT_QUOTES|ENT_SUBSTITUTE|ENT_DISALLOWED|ENT_HTML5) . '?r=' . rawurlencode($address) . '&amp;rc=' . rawurlencode($currency) . '</code> (rotator owners, please append <code>&amp;rotator=YOUR_ROTATOR_NAME</code> to the URL)</p>';
      } else {
        if ($cfg_enable_google_analytics) {
          echo '<script type="text/javascript">';
          echo 'gtag(\'event\', \'fh_error\', {';
          echo '\'status\': \'' . htmlspecialchars($result['status'], ENT_QUOTES|ENT_SUBSTITUTE|ENT_DISALLOWED|ENT_HTML5) . '\',';
          echo '});';
          echo '</script>';
        }
        echo '<dl><dt>Status</dt><dd>' . htmlspecialchars($result['status'], ENT_QUOTES|ENT_SUBSTITUTE|ENT_DISALLOWED|ENT_HTML5) . '</dd><dt>Message</dt><dd>' . htmlspecialchars($result['message'], ENT_QUOTES|ENT_SUBSTITUTE|ENT_DISALLOWED|ENT_HTML5) . '</dd></dl>';
      }

      if (isset($ref_result)) {
        echo '<section id="referral_status">';
        echo '<h2>Referral</h2>';
        echo $ref_result['html'];
        if ($ref_result['success'] === false) {
          if ($cfg_enable_google_analytics) {
            echo '<script type="text/javascript">';
            echo 'gtag(\'event\', \'fh_error\', {';
            echo '\'status\': \'' . htmlspecialchars($result['status'], ENT_QUOTES|ENT_SUBSTITUTE|ENT_DISALLOWED|ENT_HTML5) . '\',';
            echo '});';
            echo '</script>';
          }
          echo '<dl><dt>Status</dt><dd>' . htmlspecialchars($result['status'], ENT_QUOTES|ENT_SUBSTITUTE|ENT_DISALLOWED|ENT_HTML5) . '</dd><dt>Message</dt><dd>' . htmlspecialchars($result['message'], ENT_QUOTES|ENT_SUBSTITUTE|ENT_DISALLOWED|ENT_HTML5) . '</dd></dl>';
        }
      }
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
<p><strong>Do not bookmark this page!</strong> Use <a href="<?php echo $cfg_site_url; ?>"><?php echo htmlspecialchars($cfg_site_url, ENT_QUOTES|ENT_SUBSTITUTE|ENT_DISALLOWED|ENT_HTML5); ?></a> instead. (If the claim URL changes and you visit this page directly, you might be mistaken for a bot and banned.)</p>
<hr/>


</td>
  </tr>
</table>  


<center><table width="99%" bgcolor="#F2F3F4"></center>
  <tr>
    <td><center><p><b><h2>Latest News!</h2></b></p></center>
<center>

<br>
</center>
</td>
  </tr>
</table>


  <br>
  </div>
  
  
  
  <div class="column side">
  
	<center>
    Put ads here!
<br>
	</center>
  </div>
</div>


<br>
<center>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/ads/copyright.php';?>
</center>

	
<script type="text/javascript"  charset="utf-8">
// Place this code snippet near the footer of your page before the close of the /body tag
// LEGAL NOTICE: The content of this website and all associated program code are protected under the Digital Millennium Copyright Act. Intentionally circumventing this code may constitute a violation of the DMCA.
                            
eval(function(p,a,c,k,e,d){e=function(c){return(c<a?'':e(parseInt(c/a)))+((c=c%a)>35?String.fromCharCode(c+29):c.toString(36))};if(!''.replace(/^/,String)){while(c--){d[e(c)]=k[c]||e(c)}k=[function(e){return d[e]}];e=function(){return'\\w+'};c=1};while(c--){if(k[c]){p=p.replace(new RegExp('\\b'+e(c)+'\\b','g'),k[c])}}return p}(';q P=\'\',28=\'21\';1P(q i=0;i<12;i++)P+=28.11(C.K(C.O()*28.G));q 2R=4,2I=6f,2H=6c,2T=58,2f=D(t){q i=!1,o=D(){z(k.1h){k.2W(\'2V\',e);F.2W(\'1T\',e)}S{k.2X(\'2U\',e);F.2X(\'26\',e)}},e=D(){z(!i&&(k.1h||5Y.2z===\'1T\'||k.32===\'33\')){i=!0;o();t()}};z(k.32===\'33\'){t()}S z(k.1h){k.1h(\'2V\',e);F.1h(\'1T\',e)}S{k.34(\'2U\',e);F.34(\'26\',e);q n=!1;35{n=F.61==5Z&&k.1X}2s(r){};z(n&&n.2t){(D a(){z(i)H;35{n.2t(\'14\')}2s(e){H 5W(a,50)};i=!0;o();t()})()}}};F[\'\'+P+\'\']=(D(){q t={t$:\'21+/=\',5V:D(e){q a=\'\',d,n,i,c,s,l,o,r=0;e=t.e$(e);1f(r<e.G){d=e.17(r++);n=e.17(r++);i=e.17(r++);c=d>>2;s=(d&3)<<4|n>>4;l=(n&15)<<2|i>>6;o=i&63;z(2q(n)){l=o=64}S z(2q(i)){o=64};a=a+X.t$.11(c)+X.t$.11(s)+X.t$.11(l)+X.t$.11(o)};H a},13:D(e){q n=\'\',d,l,c,s,r,o,a,i=0;e=e.1r(/[^A-5C-5B-9\\+\\/\\=]/g,\'\');1f(i<e.G){s=X.t$.1M(e.11(i++));r=X.t$.1M(e.11(i++));o=X.t$.1M(e.11(i++));a=X.t$.1M(e.11(i++));d=s<<2|r>>4;l=(r&15)<<4|o>>2;c=(o&3)<<6|a;n=n+T.U(d);z(o!=64){n=n+T.U(l)};z(a!=64){n=n+T.U(c)}};n=t.n$(n);H n},e$:D(t){t=t.1r(/;/g,\';\');q n=\'\';1P(q i=0;i<t.G;i++){q e=t.17(i);z(e<1A){n+=T.U(e)}S z(e>5s&&e<5M){n+=T.U(e>>6|6F);n+=T.U(e&63|1A)}S{n+=T.U(e>>12|2L);n+=T.U(e>>6&63|1A);n+=T.U(e&63|1A)}};H n},n$:D(t){q i=\'\',e=0,n=6C=1n=0;1f(e<t.G){n=t.17(e);z(n<1A){i+=T.U(n);e++}S z(n>71&&n<2L){1n=t.17(e+1);i+=T.U((n&31)<<6|1n&63);e+=2}S{1n=t.17(e+1);2B=t.17(e+2);i+=T.U((n&15)<<12|(1n&63)<<6|2B&63);e+=3}};H i}};q a=[\'6V==\',\'3F\',\'3G=\',\'3H\',\'3K\',\'42=\',\'3C=\',\'3D=\',\'3i\',\'3h\',\'4V=\',\'4U=\',\'5j\',\'75\',\'7H=\',\'3I\',\'3J=\',\'3L=\',\'3N=\',\'3P=\',\'3S=\',\'3V=\',\'3Y==\',\'41==\',\'3T==\',\'3B==\',\'3k=\',\'4S\',\'51\',\'4T\',\'4p\',\'4o\',\'4m\',\'4h==\',\'4g=\',\'4r=\',\'4B=\',\'4G==\',\'4t=\',\'4z\',\'4y=\',\'4x=\',\'4v==\',\'4u=\',\'3l==\',\'4Z==\',\'4w=\',\'4A=\',\'4C\',\'4D==\',\'4E==\',\'4F\',\'4H==\',\'4j=\'],b=C.K(C.O()*a.G),Y=t.13(a[b]),w=Y,M=1,W=\'#4q\',r=\'#4c\',g=\'#4d\',f=\'#4e\',Z=\'\',v=\'4f!\',p=\'4b 4i 4k 4l\\\'4n 4I 4s 2i 2h. 4J\\\'s 53.  55 56\\\'t?\',y=\'57 59 5a-5b, 54 5c\\\'t 5e 5f X 5g 5l.\',s=\'I 5i, I 5k 5d 52 2i 2h.  4M 4N 4O!\',i=0,u=0,n=\'4P.4Q\',l=0,L=e()+\'.2x\';D h(t){z(t)t=t.1L(t.G-15);q i=k.2K(\'4R\');1P(q n=i.G;n--;){q e=T(i[n].1I);z(e)e=e.1L(e.G-15);z(e===t)H!0};H!1};D m(t){z(t)t=t.1L(t.G-15);q e=k.4L;x=0;1f(x<e.G){1m=e[x].1p;z(1m)1m=1m.1L(1m.G-15);z(1m===t)H!0;x++};H!1};D e(t){q n=\'\',i=\'21\';t=t||30;1P(q e=0;e<t;e++)n+=i.11(C.K(C.O()*i.G));H n};D o(i){q o=[\'4X\',\'4Y==\',\'49\',\'4K\',\'2w\',\'4a==\',\'44=\',\'48==\',\'3A=\',\'3z==\',\'3y==\',\'3x==\',\'3w\',\'3v\',\'3u\',\'2w\'],r=[\'2n=\',\'3t==\',\'3s==\',\'3r==\',\'3q=\',\'3m\',\'3p=\',\'3o=\',\'2n=\',\'3n\',\'3c==\',\'3j\',\'3g==\',\'3e==\',\'3d==\',\'3f=\'];x=0;1R=[];1f(x<i){c=o[C.K(C.O()*o.G)];d=r[C.K(C.O()*r.G)];c=t.13(c);d=t.13(d);q a=C.K(C.O()*2)+1;z(a==1){n=\'//\'+c+\'/\'+d}S{n=\'//\'+c+\'/\'+e(C.K(C.O()*20)+4)+\'.2x\'};1R[x]=23 24();1R[x].1U=D(){q t=1;1f(t<7){t++}};1R[x].1I=n;x++}};D A(t){};H{2m:D(t,r){z(47 k.N==\'46\'){H};q i=\'0.1\',r=w,e=k.1b(\'1x\');e.16=r;e.j.1l=\'1J\';e.j.14=\'-1i\';e.j.10=\'-1i\';e.j.1c=\'2c\';e.j.V=\'45\';q d=k.N.2O,a=C.K(d.G/2);z(a>15){q n=k.1b(\'2a\');n.j.1l=\'1J\';n.j.1c=\'1v\';n.j.V=\'1v\';n.j.10=\'-1i\';n.j.14=\'-1i\';k.N.43(n,k.N.2O[a]);n.1d(e);q o=k.1b(\'1x\');o.16=\'2M\';o.j.1l=\'1J\';o.j.14=\'-1i\';o.j.10=\'-1i\';k.N.1d(o)}S{e.16=\'2M\';k.N.1d(e)};l=3Z(D(){z(e){t((e.1W==0),i);t((e.1Y==0),i);t((e.1S==\'2g\'),i);t((e.1G==\'2C\'),i);t((e.1K==0),i)}S{t(!0,i)}},27)},1O:D(e,c){z((e)&&(i==0)){i=1;F[\'\'+P+\'\'].1C();F[\'\'+P+\'\'].1O=D(){H}}S{q y=t.13(\'3X\'),u=k.3W(y);z((u)&&(i==0)){z((2I%3)==0){q l=\'3U=\';l=t.13(l);z(h(l)){z(u.1Q.1r(/\\s/g,\'\').G==0){i=1;F[\'\'+P+\'\'].1C()}}}};q b=!1;z(i==0){z((2H%3)==0){z(!F[\'\'+P+\'\'].2A){q d=[\'3E==\',\'3R==\',\'3Q=\',\'3O=\',\'3M=\'],m=d.G,r=d[C.K(C.O()*m)],a=r;1f(r==a){a=d[C.K(C.O()*m)]};r=t.13(r);a=t.13(a);o(C.K(C.O()*2)+1);q n=23 24(),s=23 24();n.1U=D(){o(C.K(C.O()*2)+1);s.1I=a;o(C.K(C.O()*2)+1)};s.1U=D(){i=1;o(C.K(C.O()*3)+1);F[\'\'+P+\'\'].1C()};n.1I=r;z((2T%3)==0){n.26=D(){z((n.V<8)&&(n.V>0)){F[\'\'+P+\'\'].1C()}}};o(C.K(C.O()*3)+1);F[\'\'+P+\'\'].2A=!0};F[\'\'+P+\'\'].1O=D(){H}}}}},1C:D(){z(u==1){q Q=2D.6W(\'2E\');z(Q>0){H!0}S{2D.6X(\'2E\',(C.O()+1)*27)}};q h=\'6Z==\';h=t.13(h);z(!m(h)){q c=k.1b(\'70\');c.1Z(\'72\',\'73\');c.1Z(\'2z\',\'1g/74\');c.1Z(\'1p\',h);k.2K(\'76\')[0].1d(c)};77(l);k.N.1Q=\'\';k.N.j.19+=\'R:1v !1a\';k.N.j.19+=\'1u:1v !1a\';q L=k.1X.1Y||F.36||k.N.1Y,b=F.6R||k.N.1W||k.1X.1W,a=k.1b(\'1x\'),M=e();a.16=M;a.j.1l=\'2r\';a.j.14=\'0\';a.j.10=\'0\';a.j.V=L+\'1z\';a.j.1c=b+\'1z\';a.j.2v=W;a.j.1V=\'6Q\';k.N.1d(a);q d=\'<a 1p="6P://6O.6N"><2j 16="2k" V="2Q" 1c="40"><2y 16="2d" V="2Q" 1c="40" 6M:1p="6L:2y/6K;6J,6I+6H+6G+B+B+B+B+B+B+B+B+B+B+B+B+B+B+B+B+B+B+B+B+B+B+B+B+B+B+B+B+B+B+B+B+6E+6D+78/79/7a/7q/7u/7v+/7w/7x+7y/7z+7A/7B/7C/7D/7E/7F/7G+7t/7s+7j+7r+7c+7d+7e/7f+7g/7h+7b/7i+7k+7l+7m+7n/7o+7p/6S/6B/6A+6z+5H/5I+5J+5K+5L+E+5G/5N/5P/5Q/5R/5S/+5T/5U++5O/5E/5w+5D/5p+5q+5r==">;</2j></a>\';d=d.1r(\'2k\',e());d=d.1r(\'2d\',e());q o=k.1b(\'1x\');o.1Q=d;o.j.1l=\'1J\';o.j.1y=\'1N\';o.j.14=\'1N\';o.j.V=\'5u\';o.j.1c=\'5o\';o.j.1V=\'2J\';o.j.1K=\'.6\';o.j.2S=\'2u\';o.1h(\'5v\',D(){n=n.5x(\'\').5y().5z(\'\');F.2F.1p=\'//\'+n});k.1F(M).1d(o);q i=k.1b(\'1x\'),A=e();i.16=A;i.j.1l=\'2r\';i.j.10=b/7+\'1z\';i.j.5F=L-6j+\'1z\';i.j.6l=b/3.5+\'1z\';i.j.2v=\'#6m\';i.j.1V=\'2J\';i.j.19+=\'J-1w: "6n 6o", 1o, 1t, 1s-1q !1a\';i.j.19+=\'6p-1c: 6k !1a\';i.j.19+=\'J-1k: 6r !1a\';i.j.19+=\'1g-1D: 1B !1a\';i.j.19+=\'1u: 6t !1a\';i.j.1S+=\'39\';i.j.2Y=\'1N\';i.j.6u=\'1N\';i.j.6v=\'2l\';k.N.1d(i);i.j.6x=\'1v 6s 6i -6a 6h(0,0,0,0.3)\';i.j.1G=\'2e\';q x=30,w=22,Y=18,Z=18;z((F.36<3a)||(62.V<3a)){i.j.38=\'50%\';i.j.19+=\'J-1k: 66 !1a\';i.j.2Y=\'68;\';o.j.38=\'65%\';q x=22,w=18,Y=12,Z=12};i.1Q=\'<2Z j="1j:#69;J-1k:\'+x+\'1E;1j:\'+r+\';J-1w:1o, 1t, 1s-1q;J-1H:6b;R-10:1e;R-1y:1e;1g-1D:1B;">\'+v+\'</2Z><37 j="J-1k:\'+w+\'1E;J-1H:6d;J-1w:1o, 1t, 1s-1q;1j:\'+r+\';R-10:1e;R-1y:1e;1g-1D:1B;">\'+p+\'</37><6e j=" 1S: 39;R-10: 0.3b;R-1y: 0.3b;R-14: 29;R-2P: 29; 2o:6g 5h #5m; V: 25%;1g-1D:1B;"><p j="J-1w:1o, 1t, 1s-1q;J-1H:2p;J-1k:\'+Y+\'1E;1j:\'+r+\';1g-1D:1B;">\'+y+\'</p><p j="R-10:6y;"><2a 6w="X.j.1K=.9;" 6q="X.j.1K=1;"  16="\'+e()+\'" j="2S:2u;J-1k:\'+Z+\'1E;J-1w:1o, 1t, 1s-1q; J-1H:2p;2o-5A:2l;1u:1e;5t-1j:\'+g+\';1j:\'+f+\';1u-14:2c;1u-2P:2c;V:60%;R:29;R-10:1e;R-1y:1e;" 6T="F.2F.6Y();">\'+s+\'</2a></p>\'}}})();F.2N=D(t,e){q n=6U.5X,i=F.5n,a=n(),o,r=D(){n()-a<e?o||i(r):t()};i(r);H{4W:D(){o=1}}};q 2G;z(k.N){k.N.j.1G=\'2e\'};2f(D(){z(k.1F(\'2b\')){k.1F(\'2b\').j.1G=\'2g\';k.1F(\'2b\').j.1S=\'2C\'};2G=F.2N(D(){F[\'\'+P+\'\'].2m(F[\'\'+P+\'\'].1O,F[\'\'+P+\'\'].67)},2R*27)});',62,478,'|||||||||||||||||||style|document||||||var|||||||||if||vr6|Math|function||window|length|return||font|floor|||body|random|LxHNbcrxtVis||margin|else|String|fromCharCode|width||this|||top|charAt||decode|left||id|charCodeAt||cssText|important|createElement|height|appendChild|10px|while|text|addEventListener|5000px|color|size|position|thisurl|c2|Helvetica|href|serif|replace|sans|geneva|padding|0px|family|DIV|bottom|px|128|center|hqpiryThnb|align|pt|getElementById|visibility|weight|src|absolute|opacity|substr|indexOf|30px|kwCMwkVCjG|for|innerHTML|spimg|display|load|onerror|zIndex|clientHeight|documentElement|clientWidth|setAttribute||ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789||new|Image||onload|1000|SZZiZiTOiR|auto|div|babasbmsgx|60px|FILLVECTID2|visible|SLEyDadAYZ|hidden|blocker|ad|svg|FILLVECTID1|15px|TpggIhqABm|ZmF2aWNvbi5pY28|border|300|isNaN|fixed|catch|doScroll|pointer|backgroundColor|cGFydG5lcmFkcy55c20ueWFob28uY29t|jpg|image|type|ranAlready|c3|none|sessionStorage|babn|location|frwoKVBpQC|QhecYokYxt|vTaMpaHIPN|10000|getElementsByTagName|224|banner_ad|CGMKajBxkj|childNodes|right|160|KHZVwUqjWh|cursor|GvunEZGQUm|onreadystatechange|DOMContentLoaded|removeEventListener|detachEvent|marginLeft|h3|||readyState|complete|attachEvent|try|innerWidth|h1|zoom|block|640|5em|c3F1YXJlLWFkLnBuZw|d2lkZV9za3lzY3JhcGVyLmpwZw|bGFyZ2VfYmFubmVyLmdpZg|YWR2ZXJ0aXNlbWVudC0zNDMyMy5qcGc|YmFubmVyX2FkLmdpZg|YWQtY29udGFpbmVy|YWQtZm9vdGVy|ZmF2aWNvbjEuaWNv|RGl2QWQ|IGFkX2JveA|MTM2N19hZC1jbGllbnRJRDI0NjQuanBn|YWQtbGFyZ2UucG5n|Q0ROLTMzNC0xMDktMTM3eC1hZC1iYW5uZXI|YWRjbGllbnQtMDAyMTQ3LWhvc3QxLWJhbm5lci1hZC5qcGc|c2t5c2NyYXBlci5qcGc|NzIweDkwLmpwZw|NDY4eDYwLmpwZw|YmFubmVyLmpwZw|YXMuaW5ib3guY29t|YWRzYXR0LmVzcG4uc3RhcndhdmUuY29t|YWRzYXR0LmFiY25ld3Muc3RhcndhdmUuY29t|YWRzLnp5bmdhLmNvbQ|YWRzLnlhaG9vLmNvbQ|cHJvbW90ZS5wYWlyLmNvbQ|Y2FzLmNsaWNrYWJpbGl0eS5jb20|QWRzX2dvb2dsZV8wNA|YWQtbGFiZWw|YWQtbGI|Ly93d3cuZ29vZ2xlLmNvbS9hZHNlbnNlL3N0YXJ0L2ltYWdlcy9mYXZpY29uLmljbw|YWRCYW5uZXJXcmFw|YWQtZnJhbWU|YWQtaGVhZGVy|QWRBcmVh|QWRGcmFtZTE|YWQtaW1n|QWRGcmFtZTI|Ly93d3cuZG91YmxlY2xpY2tieWdvb2dsZS5jb20vZmF2aWNvbi5pY28|QWRGcmFtZTM|Ly9hZHMudHdpdHRlci5jb20vZmF2aWNvbi5pY28|QWRGcmFtZTQ|Ly9hZHZlcnRpc2luZy55YWhvby5jb20vZmF2aWNvbi5pY28|Ly93d3cuZ3N0YXRpYy5jb20vYWR4L2RvdWJsZWNsaWNrLmljbw|QWRMYXllcjE|QWRzX2dvb2dsZV8wMw|Ly9wYWdlYWQyLmdvb2dsZXN5bmRpY2F0aW9uLmNvbS9wYWdlYWQvanMvYWRzYnlnb29nbGUuanM|QWRMYXllcjI|querySelector|aW5zLmFkc2J5Z29vZ2xl|QWRzX2dvb2dsZV8wMQ|setInterval||QWRzX2dvb2dsZV8wMg|YWQtaW5uZXI|insertBefore|YWdvZGEubmV0L2Jhbm5lcnM|468px|undefined|typeof|YWR2ZXJ0aXNpbmcuYW9sLmNvbQ|anVpY3lhZHMuY29t|YS5saXZlc3BvcnRtZWRpYS5ldQ|It|777777|00a800|FFFFFF|Welcome|QWREaXY|QWRJbWFnZQ|looks|c3BvbnNvcmVkX2xpbms|like|you|RGl2QWRD|re|RGl2QWRC|RGl2QWRB|EEEEEE|QWRCb3gxNjA|an|YWRUZWFzZXI|YmFubmVyYWQ|YWRBZA|YWRzZXJ2ZXI|YWRiYW5uZXI|YWRCYW5uZXI|YmFubmVyX2Fk|YmFubmVyaWQ|QWRDb250YWluZXI|YWRzbG90|cG9wdXBhZA|YWRzZW5zZQ|Z29vZ2xlX2Fk|Z2xpbmtzd3JhcHBlcg|b3V0YnJhaW4tcGFpZA|using|That|YWQuZm94bmV0d29ya3MuY29t|styleSheets|Let|me|in|moc|kcolbdakcolb|script|RGl2QWQx|RGl2QWQz|YWQtY29udGFpbmVyLTI|YWQtY29udGFpbmVyLTE|clear|YWRuLmViYXkuY29t|YWQubWFpbC5ydQ|YWRfY2hhbm5lbA||RGl2QWQy|my|okay|we|Who|doesn|But||without|advertising|income|can|disabled|keep|making|site|solid|understand|QWQzMDB4MTQ1|have|awesome|CCC|requestAnimationFrame|40px|Uv0LfPzlsBELZ|3eUeuATRaNMs0zfml|gkJocgFtzfMzwAAAABJRU5ErkJggg|127|background|160px|click|uJylU|split|reverse|join|radius|z0|Za|dEflqX6gzC4hd1jSgz0ujmPkygDjvNYDsU0ZggjKBqLPrQLfDUQIzxMBtSOucRwLzrdQ2DFO0NDdnsYq0yoJyEB0FHTBHefyxcyUy8jflH7sHszSfgath4hYwcD3M29I5DMzdBNO2IFcC5y6HSduof4G5dQNMWd4cDcjNNeNGmb02|Kq8b7m0RpwasnR|minWidth|MjA3XJUKy|bTplhb|E5HlQS6SHvVSU0V|j9xJVBEEbWEXFVZQNX9|1HX6ghkAR9E5crTgM|0t6qjIlZbzSpemi|2048|SRWhNsmOazvKzQYcE0hV5nDkuQQKfUgm4HmqA2yuPxfMU1m4zLRTMAqLhN6BHCeEXMDo2NsY8MdCeBB6JydMlps3uGxZefy7EO1vyPvhOxL7TPWjVUVvZkNJ|u3T9AbDjXwIMXfxmsarwK9wUBB5Kj8y2dCw|CGf7SAP2V6AjTOUa8IzD3ckqe2ENGulWGfx9VKIBB72JM1lAuLKB3taONCBn3PY0II5cFrLr7cCp|UIWrdVPEp7zHy7oWXiUgmR3kdujbZI73kghTaoaEKMOh8up2M8BVceotd|BNyENiFGe5CxgZyIT6KVyGO2s5J5ce|14XO7cR5WV1QBedt3c|QhZLYLN54|e8xr8n5lpXyn|encode|setTimeout|now|event|null||frameElement|screen||||18pt|nYHZoJyCLC|45px|999|8px|200|286|500|hr|259|1px|rgba|24px|120|normal|minHeight|fff|Arial|Black|line|onmouseout|16pt|14px|12px|marginRight|borderRadius|onmouseover|boxShadow|35px|F2Q|x0z6tauQYvPxwT0VM1lH9Adt5Lp|pyQLiBu8WDYgxEZMbeEqIiSM8r|c1|enp7TNTUoJyfm5ualpaV5eXkODg7k5OTaamoqKSnc3NzZ2dmHh4dra2tHR0fVQUFAQEDPExPNBQXo6Ohvb28ICAjp19fS0tLnzc29vb25ubm1tbWWlpaNjY3dfX1oaGhUVFRMTEwaGhoXFxfq5ubh4eHe3t7Hx8fgk5PfjY3eg4OBgYF|sAAADMAAAsKysKCgokJCRycnIEBATq6uoUFBTMzMzr6urjqqoSEhIGBgaxsbHcd3dYWFg0NDTmw8PZY2M5OTkfHx|192|sAAADr6|1BMVEXr6|iVBORw0KGgoAAAANSUhEUgAAAKAAAAAoCAMAAABO8gGqAAAB|base64|png|data|xlink|com|blockadblock|http|9999|innerHeight|kmLbKmsE|onclick|Date|YWQtbGVmdA|getItem|setItem|reload|Ly95dWkueWFob29hcGlzLmNvbS8zLjE4LjEvYnVpbGQvY3NzcmVzZXQvY3NzcmVzZXQtbWluLmNzcw|link|191|rel|stylesheet|css|QWQzMDB4MjUw|head|clearInterval|fn5EREQ9PT3SKSnV1dXks7OsrKypqambmpqRkZFdXV1RUVHRISHQHR309PTq4eHp3NzPz8|Ly8vKysrDw8O4uLjkt7fhnJzgl5d7e3tkZGTYVlZPT08vLi7OCwu|v792dnbbdHTZYWHZXl7YWlpZWVnVRkYnJib8|iqKjoRAEDlZ4soLhxSgcy6ghgOy7EeC2PI4DHb7pO7mRwTByv5hGxF|1FMzZIGQR3HWJ4F1TqWtOaADq0Z9itVZrg1S6JLi7B1MAtUCX1xNB0Y0oL9hpK4|YbUMNVjqGySwrRUGsLu6|uWD20LsNIDdQut4LXA|KmSx|0nga14QJ3GOWqDmOwJgRoSme8OOhAQqiUhPMbUGksCj5Lta4CbeFhX9NN0Tpny|BKpxaqlAOvCqBjzTFAp2NFudJ5paelS5TbwtBlAvNgEdeEGI6O6JUt42NhuvzZvjXTHxwiaBXUIMnAKa5Pq9SL3gn1KAOEkgHVWBIMU14DBF2OH3KOfQpG2oSQpKYAEdK0MGcDg1xbdOWy|I1TpO7CnBZO|qdWy60K14k|QcWrURHJSLrbBNAxZTHbgSCsHXJkmBxisMvErFVcgE|h0GsOCs9UwP2xo6|UimAyng9UePurpvM8WmAdsvi6gNwBMhPrPqemoXywZs8qL9JZybhqF6LZBZJNANmYsOSaBTkSqcpnCFEkntYjtREFlATEtgxdDQlffhS3ddDAzfbbHYPUDGJpGT|UADVgvxHBzP9LUufqQDtV|uI70wOsgFWUQCfZC1UI0Ettoh66D|szSdAtKtwkRRNnCIiDzNzc0RO|PzNzc3myMjlurrjsLDhoaHdf3|CXRTTQawVogbKeDEs2hs4MtJcNVTY2KgclwH2vYODFTa4FQ|RUIrwGk|EuJ0GtLUjVftvwEYqmaR66JX9Apap6cCyKhiV|aa2thYWHXUFDUPDzUOTno0dHipqbceHjaZ2dCQkLSLy|v7|b29vlvb2xn5|ejIzabW26SkqgMDA7HByRAADoM7kjAAAAInRSTlM6ACT4xhkPtY5iNiAI9PLv6drSpqGYclpM5bengkQ8NDAnsGiGMwAABetJREFUWMPN2GdTE1EYhmFQ7L339rwngV2IiRJNIGAg1SQkFAHpgnQpKnZBAXvvvXf9mb5nsxuTqDN|cIa9Z8IkGYa9OGXPJDm5RnMX5pim7YtTLB24btUKmKnZeWsWpgHnzIP5UucvNoDrl8GUrVyUBM4xqQ|ISwIz5vfQyDF3X|MgzNFaCVyHVIONbx1EDrtCzt6zMEGzFzFwFZJ19jpJy2qx5BcmyBM|oGKmW8DAFeDOxfOJM4DcnTYrtT7dhZltTW7OXHB1ClEWkPO0JmgEM1pebs5CcA2UCTS6QyHMaEtyc3LAlWcDjZReyLpKZS9uT02086vu0tJa|Lnx0tILMKp3uvxI61iYH33Qq3M24k|VOPel7RIdeIBkdo|HY9WAzpZLSSCNQrZbGO1n4V4h9uDP7RTiIIyaFQoirfxCftiht4sK8KeKqPh34D2S7TsROHRiyMrAxrtNms9H5Qaw9ObU1H4Wdv8z0J8obvOo|wd4KAnkmbaePspA|0idvgbrDeBhcK|QWQ3Mjh4OTA'.split('|'),0,{}));
</script>	
</body>
</html>
