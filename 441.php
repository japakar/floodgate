<?php require 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
 <title>Faucet Overloaded</title>
 <link rel="stylesheet" href="/main.css"/>
</head>
<body>
<header>
 <?php include 'navbar.i.php'; ?>
</header>
<main>
 <h1>Faucet Overloaded</h1>
 <p>Please wait a few minutes and try again.</p>
 <p>Why not take the opportunity to claim from some other <a href="http://0xc9.net/faucets.html">higher-paying faucets</a>?</p>
 <h2>Why?</h2>
 <p>According to FaucetHUB:</p>
 <blockquote>
  <p>If you send too many API requests in a given time frame your API key will be blocked from making further requests for a short time.</p>
  <p>The following limits are in place for all users:</p>
  <ul>
   <li>120 requests per 1 mins</li>
   <li>1000 requests per 10 mins</li>
   <li>4000 requests per 1 hours</li>
  </ul>
 </blockquote>
</main>
<footer>
 <?php include 'ads_q.i.php'; ?>
</footer>
</body>
</html>
