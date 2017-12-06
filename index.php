<?php require $_SERVER['DOCUMENT_ROOT'] . '/config.php'; ?>
<?php
  if (isset($_GET['ref_source'])) { // undocumented alias for cryptator
    header('Location: ' . str_replace('?ref_source=', '?rotator=', str_replace('&ref_source=', '&rotator=', (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]")), true, 301);
    exit;
  }

  $referrer = null; // The address of the referrer.
  if (isset($_GET['r']))
    $referrer = $_GET['r'];

  $referrer_currency = null; // The currency that the referrer wants to be paid in.
  if (isset($_GET['rc']))
    $referrer_currency = $_GET['rc'];

  $referred = (isset($referrer) && isset($referrer_currency)); // Whether the user was referred.

  if (!isset($_GET['rotator']))
    header('X-Frame-Options: sameorigin', true);

  if ($cfg_list_faucet) {
    include_once $_SERVER['DOCUMENT_ROOT'] . '/lib/flist.php';
    flist_auto();
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
 <title><?php echo $cfg_site_name; ?></title>
 <?php include $_SERVER['DOCUMENT_ROOT'] . '/custom/head.php'; ?>
</head>
<body>
<header><?php include $_SERVER['DOCUMENT_ROOT'] . '/custom/navbar.php'; ?></header>
<main>
 <h1><?php echo $cfg_site_name; ?></h1>
 <p>Just enter your address below, select your currency, hit submit, and then leave the page open for tons of satoshi!</p>
 <p>There&#700;s no timers or CAPTCHAs<?php if ($cfg_use_captcha) echo ' (apart from the one on this page)'; ?>; this is one of the leakiest faucets out there!</p>
 <p>(This faucet requires your address to be linked to a <a href="http://faucethub.io/r/10082526">FaucetHUB account</a>)</p>
 <p>(If the payout rates seem low, just remember that you get out just as much as you put in, and this faucet is much less &ldquo;labor-intensive&rdquo; than most.)</p>
 <?php if ($cfg_set_mining) echo '<p>This faucet <strong>does not</strong> freeze your account.</p>'; ?>
 <?php if (!empty($cfg_MOTD)) echo '<aside id="motd"><div style="min-width:40vw"><b>Announcements</b></div>' . $cfg_MOTD . '</aside>'; ?>
 <div style="padding-left: 1em">
  <form action="verify.php" method="post">
   <?php
     if ($referred) {
       echo '<input type="hidden" name="r" value="' . htmlspecialchars($referrer, ENT_QUOTES|ENT_SUBSTITUTE|ENT_DISALLOWED|ENT_HTML5) . '"/>';
       echo '<input type="hidden" name="rc" value="' . htmlspecialchars($referrer_currency, ENT_QUOTES|ENT_SUBSTITUTE|ENT_DISALLOWED|ENT_HTML5) . '"/>';
     }
     if (isset($_GET['rotator']))
       echo '<input type="hidden" name="rotator" value="' . htmlspecialchars($_GET['rotator'], ENT_QUOTES|ENT_SUBSTITUTE|ENT_DISALLOWED|ENT_HTML5) . '"/>';
   ?>
   <?php
     if ($cfg_use_captcha) {
       require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/captcha.php';
       embed_captcha();
     }
   ?>
   <input type="text" name="address" required="required" pattern="[A-Za-z0-9]+" placeholder="address" size="40" style="font-family: monospace"/><br/>
   <select name="currency" required="required">
    <?php if ($cfg_BCH_enabled) {echo '<option '; if (isset($referrer_currency) && ($referrer_currency == 'BCH')) {echo 'selected="selected" ';} echo 'value="BCH">BCH (~' . ($cfg_BCH_amount) . ' every ' . ($cfg_refresh_time / 60) . ' minutes)</option>';} ?>
    <?php if ($cfg_BLK_enabled) {echo '<option '; if (isset($referrer_currency) && ($referrer_currency == 'BLK')) {echo 'selected="selected" ';} echo 'value="BLK">BLK (~' . ($cfg_BLK_amount) . ' every ' . ($cfg_refresh_time / 60) . ' minutes)</option>';} ?>
    <?php if ($cfg_BTC_enabled) {echo '<option '; if (isset($referrer_currency) && ($referrer_currency == 'BTC')) {echo 'selected="selected" ';} echo 'value="BTC">BTC (~' . ($cfg_BTC_amount) . ' every ' . ($cfg_refresh_time / 60) . ' minutes)</option>';} ?>
    <?php if ($cfg_BTX_enabled) {echo '<option '; if (isset($referrer_currency) && ($referrer_currency == 'BTX')) {echo 'selected="selected" ';} echo 'value="BTX">BTX (~' . ($cfg_BTX_amount) . ' every ' . ($cfg_refresh_time / 60) . ' minutes)</option>';} ?>
    <?php if ($cfg_DASH_enabled) {echo '<option '; if (isset($referrer_currency) && ($referrer_currency == 'DASH')) {echo 'selected="selected" ';} echo 'value="DASH">DASH (~' . ($cfg_DASH_amount) . ' every ' . ($cfg_refresh_time / 60) . ' minutes)</option>';} ?>
    <?php if ($cfg_DOGE_enabled) {echo '<option '; if (isset($referrer_currency) && ($referrer_currency == 'DOGE')) {echo 'selected="selected" ';} echo 'value="DOGE">DOGE (~' . ($cfg_DOGE_amount) . ' every ' . ($cfg_refresh_time / 60) . ' minutes)</option>';} ?>
    <?php if ($cfg_ETH_enabled) {echo '<option '; if (isset($referrer_currency) && ($referrer_currency == 'ETH')) {echo 'selected="selected" ';} echo 'value="ETH">ETH (~' . ($cfg_ETH_amount) . ' every ' . ($cfg_refresh_time / 60) . ' minutes)</option>';} ?>
    <?php if ($cfg_LTC_enabled) {echo '<option '; if (isset($referrer_currency) && ($referrer_currency == 'LTC')) {echo 'selected="selected" ';} echo 'value="LTC">LTC (~' . ($cfg_LTC_amount) . ' every ' . ($cfg_refresh_time / 60) . ' minutes)</option>';} ?>
    <?php if ($cfg_PPC_enabled) {echo '<option '; if (isset($referrer_currency) && ($referrer_currency == 'PPC')) {echo 'selected="selected" ';} echo 'value="PPC">PPC (~' . ($cfg_PPC_amount) . ' every ' . ($cfg_refresh_time / 60) . ' minutes)</option>';} ?>
    <?php if ($cfg_XPM_enabled) {echo '<option '; if (isset($referrer_currency) && ($referrer_currency == 'XPM')) {echo 'selected="selected" ';} echo 'value="XPM">XPM (~' . ($cfg_XPM_amount) . ' every ' . ($cfg_refresh_time / 60) . ' minutes)</option>';} ?>
   </select>
   <input id="start_claiming" type="submit" value="Start claiming"/>
   <div style="max-width:80ch"><?php include $_SERVER['DOCUMENT_ROOT'] . '/custom/claim_options.php'; ?></div>
  </form>
 </div>
 <p>Referral link: <code><?php echo htmlspecialchars($cfg_site_url, ENT_QUOTES|ENT_SUBSTITUTE|ENT|DISALLOWED|ENT_HTML5); ?>?r=<var>YOUR_ADDRESS</var>&amp;rc=<var>CURRENCY</var></code> (rotator owners, please append <code>&amp;rotator=YOUR_ROTATOR_NAME</code> to the URL)</p>
 <?php if ($cfg_enable_google_analytics) echo '<p>This site uses Google&nbsp;Analytics and cookies. It doesn&#700;t really matter, and the information collected is <em>completely</em> anonymous and stripped of any identifying information. Nobody cares anyway; the people who <em>do</em> care about your information don&#700;t tell you that they have it. The information collected here would be akin to glancing at your feet from across the street while holding a censor bar over your face, body, and skin.<br/>Nice shoes, by the way!</p>'; ?>
</main>
<footer>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/custom/ads_q.php'; ?>
</footer>
</body>
</html>
