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
 <p>There&#700;s no timers or CAPTCHAs (apart from the one on this page); this is one of the leakiest faucets out there!</p>
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
       if (!captcha_done(false)) {
         embed_captcha();
       } else {
         echo '<p>(You are still verified!)</p>';
       }
     }
   ?>
   <input type="text" name="address" placeholder="address" size="40" style="font-family: monospace"/>
   <select name="currency">
    <?php if ($cfg_BCH_enabled) {echo '<option value="BCH">BCH (' . ($cfg_BCH_amount) . ')</option>';} ?>
    <?php if ($cfg_BLK_enabled) {echo '<option value="BLK">BLK (' . ($cfg_BLK_amount) . ')</option>';} ?>
    <?php if ($cfg_BTC_enabled) {echo '<option value="BTC">BTC (' . ($cfg_BTC_amount) . ')</option>';} ?>
    <?php if ($cfg_DASH_enabled) {echo '<option value="DASH">DASH (' . ($cfg_DASH_amount) . ')</option>';} ?>
    <?php if ($cfg_DOGE_enabled) {echo '<option value="DOGE">DOGE (' . ($cfg_DOGE_amount) . ')</option>';} ?>
    <?php if ($cfg_ETH_enabled) {echo '<option value="ETH">ETH (' . ($cfg_ETH_amount) . ')</option>';} ?>
    <?php if ($cfg_LTC_enabled) {echo '<option value="LTC">LTC (' . ($cfg_LTC_amount) . ')</option>';} ?>
    <?php if ($cfg_PPC_enabled) {echo '<option value="PPC">PPC (' . ($cfg_PPC_amount) . ')</option>';} ?>
    <?php if ($cfg_XPM_enabled) {echo '<option value="XPM">XPM (' . ($cfg_XPM_amount) . ')</option>';} ?>
   </select>
   <input id="start_claiming" type="submit" value="Start claiming"/>
   <br/>
   <input type="checkbox" name="miner"/> Allow the site to mine on one thread with 80% idle time (more profits mean more payouts eventually!)
  </form>
 </div>
 <p>Referral link: <code><?php echo $cfg_site_url; ?>?r=<var>YOUR_ADDRESS</var>&amp;rc=<var>CURRENCY</var></code></p>
 <p><!-- Please don't change this referral! It is basically my one line of profit XD --><a href='https://a-ads.com?partner=710774'>Advertise with Anonymous&nbsp;Ads</a> (Best ad network ever!)</p>
</main>
<footer>
 <?php include 'ads_q.i.php'; ?>
</footer>
</body>
</html>
