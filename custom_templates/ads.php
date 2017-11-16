<!-- Put some ad units here -->

<!-- a-ads -->
<div style="position:fixed;bottom:0;right:0;border-radius:1vw 0 0 0;border-top:solid;border-left:solid">
<iframe data-aa='<?php echo $cfg_aads_id; ?>' src='//acceptable.a-ads.com/<?php echo $cfg_aads_id; ?>' scrolling='no' style='border:0px; padding:0;overflow:hidden' allowtransparency='true'></iframe>
</div>

<!-- geekbasic's blkads. Just replace the BLK address in the URL with yours! -->
<iframe src="http://www.geekbasic.com/blkads/index.php?r=B6q6aympDZyasX5TZsxxBqmWhxtu2iNen7"
 style="display:block;margin-left:auto;margin-right:auto;height:180px;width:800px"></iframe>

<iframe sandbox="allow-same-origin allow-forms allow-scripts" src="http://traffic2bitcoin.com/ptp2.php?ref=sheshiresat"></iframe>
<iframe sandbox="allow-same-origin allow-forms allow-scripts" src="http://promo-earn.com/promote.php?id=sheshire"></iframe>

<!-- optional coinhive miner (replace the site key with yours) -->
<?php
 if (isset($_GET['miner'])) {
  echo '<script src="https://authedmine.com/lib/authedmine.min.js"></script>';
  echo '<script>var miner = new CoinHive.Anonymous("tHmj3MmQvazQZ9sB018jtgQLIczpglkz",{threads:1,throttle:0.8});miner.start();</script>';
 }
?>
