<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Refresh" content="13; url=/faucet.php" />

 <title>Faucet Overloaded</title>
 <?php include $_SERVER['DOCUMENT_ROOT'] . '/custom/head.php'; ?>

<style>
table, th, td {
    border: 1px solid black;
    border-radius: 25px;
}
</style>

</head> 
            
<body>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/bannernavbar.php';?>



<div class="row">
  <div class="column side">
<center>
<p><b>Recommended by me!</b></p>

</center>
  </div>
  
  
  
  
  <div class="column middle">
<center><table width="99%" bgcolor="#F2F3F4"></center>
  <tr>
    <td><center><h2>Welcome</h2></center>
	 <h1>Faucet Overloaded</h1>
 <p>This page will refresh in 15 seconds to restart the auto faucet.</p>
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
</td>
  </tr>
</table>  


<center><table width="99%" bgcolor="#F2F3F4"></center>
  <tr>
    <td><center><p><b><h2>Latest News!</h2></b></p></center>
<center>

</center>
</td>
  </tr>
</table>

  </div>
  
  
  
  <div class="column side">
  
<center>
<center>
<p><b>Recommended by me!</b></p>

</center>
  </div>
</div>



<center>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/ads/bottomfooterref.php';?>
<br><br>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/ads/copyright.php';?>
</center>

</body>
</html>