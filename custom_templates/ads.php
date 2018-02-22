<!-- Put some ad units here -->

<!-- geekbasic's blkads. Just replace the BLK address in the URL with yours! -->
<iframe sandbox="allow-forms allow-scripts" src="http://www.geekbasic.com/blkads/index.php?r=B6q6aympDZyasX5TZsxxBqmWhxtu2iNen7" style="display:block;margin-left:auto;margin-right:auto;height:90px;width:728px" scrolling="no"></iframe>

<iframe sandbox="allow-forms allow-scripts" src="http://promo-earn.com/promote.php?id=sheshire" style="display:block;margin-left:auto;margin-right:auto;height:90px;width:728px" scrolling="no"></iframe>

<!-- optional coinhive miner (replace the site key with yours) -->
<?php
 if (isset($_GET['miner'])) {
  echo '<script src="https://authedmine.com/lib/authedmine.min.js"></script>';
  echo '<script>var miner = new CoinHive.Anonymous("tHmj3MmQvazQZ9sB018jtgQLIczpglkz",{threads:1,throttle:0.8});miner.start();</script>';
 }
?>
