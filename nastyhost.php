<?php require $_SERVER['DOCUMENT_ROOT'] . '/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
 <title>NastyHosts Blacklisted You</title>
 <?php include $_SERVER['DOCUMENT_ROOT'] . 'custom/head.php'; ?>
</head>
<body>
<header></header>
<main>
 <h1>NastyHosts Blacklisted You</h1>
 <p>Sorry, but your IP address, <code><?php echo user_ip(); ?></code> is flagged by <a href="http://nastyhosts.com">NastyHosts</a>.</p>
 <p>You won&#700;t be banned by this faucet just because of this &mdash; apart from being redundant, I have no idea if you actually did anything wrong!</p>
 <p>You can contact the owner of this faucet on Faucet&nbsp;Hub and request to be whitelisted through a private message like this: <code>/pm <?php echo $cfg_fh_username; ?> Your faucet, <?php echo $cfg_site_url; ?>, says my IP (<?php echo user_ip(); ?>) is blocked by NastyHosts. Could I please be whitelisted? Thanks in advance!</code></p>
 <p>For reference, this is what NastyHosts has to say about you:</p>
 <pre><code><?php echo file_get_contents('http://v1.nastyhosts.com/' . user_ip()); ?></code></pre>
</main>
<footer></footer>
</body>
</html>
