<?php
  /* A message for people to see on the main page */
  $cfg_MOTD = '<p>This is a brand new floodgate!</p>';

  /* Create a new a-ads ad of size "Adaptive", put the Ad Unit ID here. (They'll say Ad Unit #****** on your dashboard) */
  /* You ony need one ad, people! */
  $cfg_aads_id = '701112';

  $cfg_refresh_time = 60 * 10; // Payout time in seconds.
  $cfg_real_refresh_time = $cfg_refresh_time; // Refresh time of the claim page.

  /* Enable and disable currencies. */
  $cfg_BCH_enabled  = false;
  $cfg_BLK_enabled  = true;
  $cfg_BTC_enabled  = false;
  $cfg_BTX_enabled  = false;
  $cfg_DASH_enabled = false;
  $cfg_DOGE_enabled = true;
  $cfg_ETH_enabled  = false;
  $cfg_LTC_enabled  = false;
  $cfg_PPC_enabled  = true;
  $cfg_XPM_enabled  = true;

  $cfg_BCH_amount  = null;
  $cfg_BLK_amount  = intval((200 / 60) * $cfg_refresh_time);
  $cfg_BTC_amount  = null;
  $cfg_BTX_amount  = null;
  $cfg_DASH_amount = null;
  $cfg_DOGE_amount = intval((10050 / 60) * $cfg_refresh_time);
  $cfg_ETH_amount  = null;
  $cfg_LTC_amount  = null;
  $cfg_PPC_amount  = intval((55 / 60) * $cfg_refresh_time);
  $cfg_XPM_amount  = intval((215 / 60) * $cfg_refresh_time);

  /* Make sure that the faucet is set up under the "PTP" and "Mining" categories on Faucet Hub's
   * faucet manager page, or users could get their accounts frozen for claiming too often! */
  $cfg_set_mining = true; // Set this to true once your faucet(s) is/are registered under the "PTP" and "Mining" categories on faucethub.

  /* Faucet Hub API Key(s)
   * You can set them all to the same key if you want.
   * Some people just like to register a different "faucet" for each currency. */
  $cfg_BCH_api_key  = null;
  $cfg_BLK_api_key  = 'XXXREDACTEDXXXXXXXXX4d533ffd9bfc';
  $cfg_BTC_api_key  = null;
  $cfg_BTX_api_key  = null;
  $cfg_DASH_api_key = null;
  $cfg_DOGE_api_key = 'XXXREDACTEDXXXXXXXXX7abd256ba6c8';
  $cfg_ETH_api_key  = null;
  $cfg_LTC_api_key  = null;
  $cfg_PPC_api_key  = 'XXXREDACTEDXXXXXXXXX75a6a7c815ec';
  $cfg_XPM_api_key  = 'XXXREDACTEDXXXXXXXXX75a6a7c815ec';

  /* Set this to true and the faucet will automatically ban some botters and abusers by adding `deny from IP_ADDRESS` lines to /.htaccess */
  /* Leave this disabled unless your server uses .htaccess files and you are fine with an automated script modifying it! */
  $cfg_enable_ban = false;

  /* Should return the user's IP address. Change it if you use CloudFlare. */
  function user_ip() {
    return $_SERVER['REMOTE_ADDR'];
  }

  $cfg_cookie_key = 'DIE BOTS DIE'; // Set this to a secret string that only you know.

  /* The default CAPTCHA is coinhive, and the default shortlink is eliwin;
   * you can change them, but you've got to rewrite captcha.lib.php or shortlink.lib.php yourself. */

  $cfg_use_captcha = true; // Set this to false to disable the CAPTCHA
  if ($cfg_use_captcha) {
    $cfg_coinhive_captcha_site = 'XXXREDACTEDXXXXXXXXXXpW3XVx9gRmy';
    $cfg_coinhive_captcha_secret = 'XXXREDACTEDXXXXXXXXXXjM4RJBARy3n';
  }

  $cfg_use_shortlink = false; // You can also use a shortlink
  if ($cfg_use_shortlink) {
    // You can change the shortlink provider in shortlink.lib.php
    // eliwin is ref-only: https://elibtc.win/ref/sheshiresat
    $cfg_eliwin_key = 'XXXREDACTEDXXXXXXXXXXXXXXXXXX7bd1a9161f1';

    // another good site that pays _very_ well (but doesn't have any captcha) is http://1ink.cc/?ref=16969
    // https://adbilty.me/ref/sheshiresat --- the same as eliwin, but with popups and higher pay
  }

  $cfg_enable_nastyhosts = true; // Whether to check with nastyhosts on the claim page.
  if ($cfg_enable_nastyhosts) {
    $cfg_nastyhost_whitelist = [ // IP addresses that you don't want to check
      'IP address' => 'description (can be anything you want)',
      '8.8.8.8' => 'Generic IP address',
      '127.0.0.1' => 'someone',
    ];
  }

  $cfg_enable_iphub = false; // If you want to use IPHub instead (might as well disable NastyHosts first) (you _can_ use both, if you hate everyone)
  if ($cfg_enable_iphub) {
    $cfg_iphub_key = 'XXXREDACTEDXXXXXXXXXXXXXXXXXXXXXSnB4TDd0c1hTbXpI';
    $cfg_iphub_block_level = 1; // https://iphub.info/api

    $cfg_iphub_whitelist = [ // IP addresses that you don't want to check
      'IP address' => 'description (can be anything you want)',
      '8.8.8.8' => 'Generic IP address',
      '127.0.0.1' => 'someone',
    ];
  }

  /* Google Analytics options. */
  $cfg_enable_google_analytics = false;
  if ($cfg_enable_google_analytics) {
    $cfg_ga_ID = 'UA-XXXXXXXXX-X'; // your tracking ID
    /* Be sure to go to
     *  [Admin > All Web Site Data > View Settings]
     * and set "Exclude URL Query Parameters" to:
     *   r,rc,address,currency,key
    */
  }

  $cfg_fh_username = 'texanarcher'; // Your FaucetHUB username.
  $cfg_site_name = 'A copy of 0xC9&#700;s Floodgate (DEVELOPMENT)'; // The faucet name.
  $cfg_site_url = 'http://faucet.0xc9.net'; // The URL of the faucet.
  $cfg_list_faucet = true; // Whether to include the faucet in the big list of floodgates.

  /* Set this to the version of the faucet source you are using. (see http://semver.org)
   * If you change the source, be sure to add "+mod" (modified) to the version! */
  $cfg__VERSION = '4.2.0+dev';
  //$cfg__VERSION = '4.2.0+mod.dev';
?>
