<?php
$ban_reason = 'Visited honeypot.';
$ip = getenv('HTTP_CLIENT_IP')?:getenv('REMOTE_ADDR')?:'';
if ($ip == '') {die('Error detecting IP.');}

$fp = fopen('.htaccess', 'a') or die('Error.');
fwrite($fp, "\n# " . $ban_reason . "\n");
fwrite($fp, 'deny from ' . $ip . "\n");
fclose($fp);

echo 'Congragulations, you are banned!';
?>
