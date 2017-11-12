<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
 <title>IPHub Blacklisted You</title>
 <link rel="stylesheet" href="/main.css"/>
 <?php include 'head.i.php'; ?>
</head>
<body>
<header>
 <?php include 'navbar.i.php'; ?>
</header>
<main>
 <h1>IPHub Blacklisted You</h1>
 <p>Sorry, but your IP address, <code><?php echo user_ip(); ?></code> is flagged by <a href="https://iphub.info">IPHub</a>.</p>
 <p>You won&#700;t be banned by this faucet just because of this &mdash; apart from being redundant, I have no idea if you actually did anything wrong!</p>
 <p>You can contact the owner of this faucet on Faucet&nbsp;Hub and request to be whitelisted through a private message like this: <code>/pm <?php echo $cfg_fh_username; ?> Your faucet, <?php echo $cfg_site_url; ?>, says my IP (<?php echo user_ip(); ?>) is blocked by IPHub. Could I please be whitelisted? Thanks in advance!</code></p>
 <p>For reference, this is what IPHub has to say about you:</p>
 <pre><code><?php
  $context = stream_context_create([
    'http' => [
      'header'  => 'X-Key: ' . $cfg_iphub_key . "\r\n",
      'method'  => 'GET',
    ]
  ]);

  echo file_get_contents('http://v2.api.iphub.info/ip/' . user_ip(), false, $context);
 ?></code></pre>
</main>
<footer>
 <?php include 'ads_q.i.php'; ?>
</footer>
</body>
</html>
