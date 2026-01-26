<?php
function adsense($tipo = 'article')
{
    if (LOCALHOST) {
        return '';
    }

    global $amp;

    switch ($tipo) {
        case 'article':
            if ($amp) {
                return '<amp-ad layout="fixed-height" height=200 type="adsense" data-ad-client="ca-pub-6149075307668331" data-ad-slot="1329819206"></amp-ad>';
            } else {
                return '<ins class="adsbygoogle" style="display:block; text-align:center;" data-ad-format="fluid" data-ad-layout="in-article" data-ad-client="ca-pub-6149075307668331" data-ad-slot="1329819206"></ins><script>(adsbygoogle = window.adsbygoogle || []).push({});</script>';
            }

        case 'arranhaceu':
            if ($amp) {
                return '<amp-ad layout="responsive" width=300 height=250 type="adsense" data-ad-client="ca-pub-6149075307668331" data-ad-slot="6429610404"></amp-ad>';
            } else {
                return '<ins class="adsbygoogle" style="display:inline-block;width:300px;height:600px" data-ad-client="ca-pub-6149075307668331" data-ad-slot="6429610404"></ins><script>(adsbygoogle = window.adsbygoogle || []).push({});</script>';
            }

        case 'feed':
            if ($amp) {
                return '<amp-ad layout="fixed-height" height=250 type="adsense" data-ad-client="ca-pub-6149075307668331" data-ad-slot="5760018809"></amp-ad>';
            } else {
                return '<div class="adfeed"><ins class="adsbygoogle" style="display:block" data-ad-format="fluid" data-ad-layout="image-top" data-ad-layout-key="-8h+1w-e8+dh+k6" data-ad-client="ca-pub-6149075307668331" data-ad-slot="5760018809"></ins><script>(adsbygoogle = window.adsbygoogle || []).push({});</script></div>';
            }

        case 'retangulo':
            if ($amp) {
                return '<amp-ad layout="responsive" width=300 height=250 type="adsense" data-ad-client="ca-pub-6149075307668331" data-ad-slot="1296269722"></amp-ad>';
            } else {
                return '<ins class="adsbygoogle" style="display:inline-block;width:300px;height:250px" data-ad-client="ca-pub-6149075307668331" data-ad-slot="1296269722"></ins><script>(adsbygoogle = window.adsbygoogle || []).push({});</script>';
            }
        case 'pagelevel':
            return '';
            /**
            if ($amp) {
                return '';
            } else {
                return '<script>(adsbygoogle = window.adsbygoogle || []).push({google_ad_client: "ca-pub-6149075307668331",enable_page_level_ads: true});</script>';
            }**/
    }
}
