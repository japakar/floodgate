<?php
  $cfg_MOTD = '<p>This is a new faucet!</p>';

  $cfg_refresh_time = 60 * 5; // Payout time in seconds.
  $cfg_real_refresh_time = 60; // Refresh time of the claim page.

  /* Enable and disable currencies. */
  $cfg_BCH_enabled  = false;
  $cfg_BLK_enabled  = true;
  $cfg_BTC_enabled  = false;
  $cfg_DASH_enabled = false;
  $cfg_DOGE_enabled = true;
  $cfg_ETH_enabled  = false;
  $cfg_LTC_enabled  = false;
  $cfg_PPC_enabled  = true;
  $cfg_XPM_enabled  = true;

  /* For each of these variables, the faucet pays out (amount * $cfg_refresh_time) satoshi every $cfg_refresh_time seconds. */
  $cfg_BCH_amount  = null;
  $cfg_BLK_amount  = (150 / 60);
  $cfg_BTC_amount  = null;
  $cfg_DASH_amount = null;
  $cfg_DOGE_amount = (10000 / 60);
  $cfg_ETH_amount  = null;
  $cfg_LTC_amount  = null;
  $cfg_PPC_amount  = (50 / 60);
  $cfg_XPM_amount  = (200 / 60);

  $cfg_fh_api_key = 'XXXREDACTEDXXXXXX01136debfef7e8c'; // You should know what this is already.
  $cfg_set_mining = false; // Set this to true once your faucet is registered under the "PTP" and "Mining" categories on faucethub.

  $cfg_use_captcha = true; // Set this to false to disable the CAPTCHA
  if ($cfg_use_captcha) {
    $cfg_captcha_difficulty = 1; // must be an integer greater than or equal to 1
    $cfg_coinhive_captcha_site = 'XXXREDACTEDXXXXXXvyMypW3XVx9gRmy';
    $cfg_coinhive_captcha_secret = 'XXXREDACTEDXXXXXXeb4DjM4RJBARy3n';

    $cfg_cookie_key = 'some_secret_string';
    function captcha_done($ban_if_invalid) {
      /* $BAN_IF_INVALID IS NOT IMPLEMENTED YET */
      global $cfg_cookie_key;
      global $cfg_fh_username;

      $done = ($_COOKIE[$cfg_fh_username . '_captcha_key'] == $cfg_cookie_key);
      return $done;
    }
  }

  $cfg_fh_username = '0xC9'; // Your FaucetHUB username.
  $cfg_site_name = 'A copy of 0xC9&#700;s Floodgate v2.12.0'; // The faucet name.
  $cfg_site_url = 'http://faucet.0xc9.net'; // The base URL of the faucet.
?>
