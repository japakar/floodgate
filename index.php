<?php require 'config.php'; ?>
<!DOCTYPE html>
<?php
  $referred = (isset($_GET['r']) ? true : false); // Whether the user was referred.
  $referrer = null; // The address of the referrer.
  $referrer_currency = null; // The currency that the referrer wants to be paid in.

  if ($referred) {
    $referrer = htmlspecialchars(stripslashes($_GET['r']));
    $referrer_currency = htmlspecialchars(stripslashes($_GET['rc']));
  }
?>
<html lang="en">
<head>
 <title><?php echo $cfg_site_name; ?></title>
 <link rel="stylesheet" href="/main.css"/>
 <?php include 'head.i.php'; ?>
</head>
<body>
<header>
 <?php include 'navbar.i.php'; ?>
</header>
<main>
 <h1><?php echo $cfg_site_name; ?></h1>
 <p>Just enter your address below, select your currency, hit submit, and then leave the page open for tons of satoshi!</p>
 <p>There&#700;s no timers or CAPTCHAs<?php if ($cfg_use_captcha) echo ' (apart from the one on this page)'; ?>; this is one of the leakiest faucets out there!</p>
 <p>(This faucet requires your address to be linked to a <a href="http://faucethub.io/r/10082526">FaucetHUB account</a>)</p>
 <p>(If the payout rates seem low, just remember that you get out just as much as you put in, and this faucet is much less &ldquo;labor-intensive&rdquo; than most.)</p>
 <?php if ($cfg_set_mining) {echo '<p>This faucet <strong>does not</strong> freeze your account.</p>';} ?>
 <aside id="motd"><?php echo $cfg_MOTD; ?></aside>
 <div style="padding-left: 1em">
  <form action="verify.php" method="post">
   <?php
     if ($referred) {
       echo '<input type="hidden" name="r" value="' . $referrer . '"/>';
       echo '<input type="hidden" name="rc" value="' . $referrer_currency . '"/>';
     }
   ?>
   <?php
     if ($cfg_use_captcha) {
       require_once 'captcha.lib.php';
       embed_captcha();
     }
   ?>
   <input type="text" name="address" placeholder="address" size="40" style="font-family: monospace"/>
   <select name="currency">
    <?php if ($cfg_BCH_enabled) {echo '<option '; if ($referred && ($referrer_currency == 'BCH')) {echo 'selected="selected" ';} echo 'value="BCH">BCH (~' . ($cfg_BCH_amount) . ' every ' . ($cfg_refresh_time / 60) . ' minutes)</option>';} ?>
    <?php if ($cfg_BLK_enabled) {echo '<option '; if ($referred && ($referrer_currency == 'BLK')) {echo 'selected="selected" ';} echo 'value="BLK">BLK (~' . ($cfg_BLK_amount) . ' every ' . ($cfg_refresh_time / 60) . ' minutes)</option>';} ?>
    <?php if ($cfg_BTC_enabled) {echo '<option '; if ($referred && ($referrer_currency == 'BTC')) {echo 'selected="selected" ';} echo 'value="BTC">BTC (~' . ($cfg_BTC_amount) . ' every ' . ($cfg_refresh_time / 60) . ' minutes)</option>';} ?>
    <?php if ($cfg_DASH_enabled) {echo '<option '; if ($referred && ($referrer_currency == 'DASH')) {echo 'selected="selected" ';} echo 'value="DASH">DASH (~' . ($cfg_DASH_amount) . ' every ' . ($cfg_refresh_time / 60) . ' minutes)</option>';} ?>
    <?php if ($cfg_DOGE_enabled) {echo '<option '; if ($referred && ($referrer_currency == 'DOGE')) {echo 'selected="selected" ';} echo 'value="DOGE">DOGE (~' . ($cfg_DOGE_amount) . ' every ' . ($cfg_refresh_time / 60) . ' minutes)</option>';} ?>
    <?php if ($cfg_ETH_enabled) {echo '<option '; if ($referred && ($referrer_currency == 'ETH')) {echo 'selected="selected" ';} echo 'value="ETH">ETH (~' . ($cfg_ETH_amount) . ' every ' . ($cfg_refresh_time / 60) . ' minutes)</option>';} ?>
    <?php if ($cfg_LTC_enabled) {echo '<option '; if ($referred && ($referrer_currency == 'LTC')) {echo 'selected="selected" ';} echo 'value="LTC">LTC (~' . ($cfg_LTC_amount) . ' every ' . ($cfg_refresh_time / 60) . ' minutes)</option>';} ?>
    <?php if ($cfg_PPC_enabled) {echo '<option '; if ($referred && ($referrer_currency == 'PPC')) {echo 'selected="selected" ';} echo 'value="PPC">PPC (~' . ($cfg_PPC_amount) . ' every ' . ($cfg_refresh_time / 60) . ' minutes)</option>';} ?>
    <?php if ($cfg_XPM_enabled) {echo '<option '; if ($referred && ($referrer_currency == 'XPM')) {echo 'selected="selected" ';} echo 'value="XPM">XPM (~' . ($cfg_XPM_amount) . ' every ' . ($cfg_refresh_time / 60) . ' minutes)</option>';} ?>
   </select>
   <input id="start_claiming" type="submit" value="Start claiming"/>
   <?php include 'claim_options.i.php'; ?>
  </form>
 </div>
 <p>Referral link: <code><?php echo $cfg_site_url; ?>?r=<var>YOUR_ADDRESS</var>&amp;rc=<var>CURRENCY</var></code> (rotator owners, please append <code>&amp;rotator=YOUR_ROTATOR_NAME</code> to the URL)</p>
 <p><!-- Please don't change this referral! It is basically my one line of profit XD --><a href='https://a-ads.com?partner=710774'>Advertise with Anonymous&nbsp;Ads</a> (Best ad network ever!)</p>
</main>
<footer>
 <?php include 'ads_q.i.php'; ?>
</footer>
</body>
</html>
