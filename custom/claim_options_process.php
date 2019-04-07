<?php

/* You can make options affect the payout here.
 * All payouts are multiplied by $amount, so
 * adding 0.5 to $amount would increase the payout by 50%.
 * 0.1 is 10% and 0.9 is 90%, etc. */

if (isset($_GET['miner'])) $amount = $amount + 0.05; // add 5% for people who allowed mining

?>
