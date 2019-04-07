This is the README work in progress.

For brief installation guide visit http://faucethub.japakar.com/afguide.php

config.php you edit your API's and other information here.  Faucethub api near the top, shortlink/captcha in the middle and your site name and information at the bottom.
You MUST right click once the file is on the server (or however it is done to bring up options for you) and set the permissions to 400 or 600 so people cannot read your APIs from that file.  (CHMOD)
If you do not set the permission you risk people getting your API keys.

441.php is the error out page, you can edit the layout and put ads here.

faucet.php this is the claim page, you can edit this for layout and referrals.  I do not suggest putting ads on auto refreshing pages.

index.php this is the landing page when users get to your page.  I suggest putting ads and referrals here.

bannernavbar.php is the navigation bar.  I left it mostly as mine is so its easy to figure out how to edit.  (Copy and paste sections)

website.css is the css for the site although some specific changes are on the php pages themselves.  

captcha.php change hashing required by coinhive.


If you want to change the short link company
Open config.php scroll down the the short link section, change the API with the new company.  (leave the rest alone)

Open shortlink.php in the LIB folder.
We are editing this section;
  $result = @json_decode(file_get_contents('https://linkrex.net/api?api=' . $cfg_eliwin_key . '&url=' . urlencode($longurl)), true);

Open your shortlink provider and get to the API section, it will have the API and the URL info, copy the section that looks similar to this
'https://linkrex.net/api?api='
and replace the https://linkrex.net/api?api=  with the new sites similar URL coding.  As long as you have changed the API in the config and the shortlink URL in shortlink.php your new company should be working.

Shortlink companies that pay out;
 * http://shortlinks.japakar.com


If you run into errors, half the time it is bad formatting (an extra space or something similar) from a copy/paste in the config.  Keep a backup handy.


AD Companies that have paid out to me;
* http://ads.japakar.com
https://bitmedia.io/?r=japakar
https://bitraffic.com/index.php?rp=86
https://www.bitcoadz.io/?rid=766
https://coinmedia.co/?ref=14651
https://a-ads.com/?partner=698409


Page mining companies;
https://platform.jsecoin.com/?lander=1&utm_source=referral&utm_campaign=aff15812&utm_content=
http://coinhive.com



Thanks!
http://japakar.com
http://findfreecrypto.com