<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
 <title>Faucet Overloaded</title>
 <link rel="stylesheet" href="/main.css"/>
 <?php include $_SERVER['DOCUMENT_ROOT'] . '/custom/head.php'; ?>
</head>
<body>
<header><?php include $_SERVER['DOCUMENT_ROOT'] . '/custom/navbar.php'; ?></header>
<main>
 <h1>Faucet Overloaded</h1>
 <p>Please wait a few minutes and try again.</p>
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
<footer><?php include $_SERVER['DOCUMENT_ROOT'] . '/custom/ads_q.php'; ?></footer>
</body>
</html>
