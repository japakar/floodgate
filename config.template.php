<?php
  $cfg_MOTD = '<p>This is a brand new floodgate!</p>'; // You can put a message here.
  $cfg_cookie_key = '(any string you feel like)'; // Change this every 24 hours or so.

  $cfg_refresh_time = 60 * 5; // Payout time in seconds.
  $cfg_real_refresh_time = 40; // Refresh time of the claim page.

  /* Enable and disable currencies. */
  $cfg_BCH_enabled  = false;
  $cfg_BLK_enabled  = true;
  $cfg_BTC_enabled  = false;
  $cfg_DASH_enabled = true;
  $cfg_DOGE_enabled = true;
  $cfg_ETH_enabled  = false;
  $cfg_LTC_enabled  = false;
  $cfg_PPC_enabled  = true;
  $cfg_XPM_enabled  = true;

  /* For each of these variables, the faucet pays out (amount * $cfg_refresh_time) satoshi every $cfg_refresh_time seconds. */
  $cfg_BCH_amount  = null;
  $cfg_BLK_amount  = 150 / 60;
  $cfg_BTC_amount  = null;
  $cfg_DASH_amount = 0.1;
  $cfg_DOGE_amount = 10000 / 60;
  $cfg_ETH_amount  = null;
  $cfg_LTC_amount  = null;
  $cfg_PPC_amount  = 500 / 60;
  $cfg_XPM_amount  = 500 / 60;

  $cfg_fh_api_key = 'XXXREDACTEDXXXXXXXX1035f39e15022'; // You should know what this is already.
  $cfg_coinhive_site = 'XXXREDACTEDXXXXXXXXjtgQLIczpglkz';
  $cfg_coinhive_secret = 'XXXREDACTEDXXXXXXXXGiyiRBH7HTi6C';

  $cfg_fh_username = '0xc9'; // Your FaucetHUB username.
  $cfg_site_name = 'Copy of 0xC9&#700;s Floodgate v2.10.0 / Movin&#700; on up!'; // The faucet name.
  $cfg_site_url = 'http://faucet.0xc9.net'; // The base URL of the faucet.
?>
