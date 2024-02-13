<?php
$time_start = microtime(true);
$runtime_log=array();

function logheader($term,$msg) {
    if (php_sapi_name() == "cli") {
        // In cli-mode
        fwrite(STDERR,$term." : ".$msg."\n");
    } else {
        // Not in cli-mode
        header("X-".$term.": ".$msg);
    }
}


if (php_sapi_name() == "cli") {
    // In cli-mode
    $maxfetch=999;
} else {
    // Not in cli-mode
    $maxfetch=15;
    error_reporting(E_ERROR | E_PARSE);
}

if(isset($_GET['maxfetch']) && is_int($_GET['maxfetch'])) {
    // id index exists
    $maxfetch=$_GET['maxfetch'];
}


$item_cache_misss=0;
$item_cache_hit=0;
$feed_cache_miss=0;
$feed_cache_hit=0;
$fetched=0;

function xmlencode($input) {
return str_replace(
    ['<', '>','&'],
    ['&lt;', '&gt;' , '&amp;',],
    html_entity_decode($input)
);  
}
function xmlencode_notags($input) {
    return str_replace(
        ['&'],
        ['&amp;',],
        html_entity_decode($input)
    );  
    }


function fgc_ttl($url,$cachetime,$cachepath) {
    $sum=md5($url);
    $cache_path="./".$cachepath;
    if (!file_exists($cache_path)) { 
        mkdir($cache_path, 0777, true); 
    }
    $cache_file = $cache_path .'/'. $sum.".cache" ;
    $cache_data = $cache_path .'/'. $sum.".cache.data" ;
    $hdrmsg="";
    //$hdrmsg=$cache_file;
    if (!file_exists($cache_path)) { 
        mkdir($cache_path, 0777, true); 
    }
    $filefound="no";
    if (file_exists($cache_file)) { $filefound="yes" ; }
    $hdrmsg=$hdrmsg." fgc_found: ".$filefound;
    if (file_exists($cache_file)) {
        $parsedfile=json_decode(file_get_contents($cache_file), true);
        $timediff=(microtime(true) - $parsedfile["time"]) /1000 ;
        $hdrmsg=$hdrmsg." found_fgc_json:".$filefound;
        $hdrmsg=$hdrmsg." time :".$timediff. " of ".$cachetime. "TTL ";
        if(  $timediff  > $cachetime  ) {
        //if(time() - filemtime($cache_file) > $cachetime) {
        //$hdrmsg=$hdrmsg." expired :".$cache_file . $timediff ." / ".$cachetime;
        $hdrmsg=$hdrmsg." found_fgc_expired :". $timediff ." / ".$cachetime;
            $cache=file_get_contents($url);
            //$cacheobj["fgc"]=base64_encode($cache) ;file_put_contents($cache_file, json_encode($cacheobj));
            file_put_contents($cache_data, $cache);
        } else {
            //$hdrmsg=$hdrmsg." cached :".$cache_file;
            $hdrmsg=$hdrmsg." fgc_cached ";
            $cache = file_get_contents($cache_data);
            //$cache=base64_decode($parsedfile["fgc"]);
        }
    } else {
        $cache=file_get_contents($url);
        $cacheobj=array();$cacheobj["time"]=microtime(true) ;
        //$cacheobj["fgc"]=base64_encode($cache) ;file_put_contents($cache_file, json_encode($cacheobj));
        file_put_contents($cache_data, $cache);
        //$hdrmsg=$hdrmsg." fetched :".$cache_file;
        $hdrmsg=$hdrmsg." fetched ";
    }
    logheader("FGC-".$sum, $hdrmsg);

    return $cache;
}
if(isset($_SERVER['DOCUMENT_ROOT']) && !empty($_SERVER['DOCUMENT_ROOT']) ) {
    $cache_path=$_SERVER['DOCUMENT_ROOT']."./.cache/";
    if (!file_exists($cache_path)) { 
        mkdir($cache_path, 0777, true); 
        if (!file_exists($cache_path)) { 
            $cache_path="../.cache/";
        }
    }
} else { $cache_path="../.cache/"; }

if (!file_exists($cache_path)) { 
    mkdir($cache_path, 0777, true); 
} 
// Create a new DOMDocument object
$doc = new DOMDocument();
// Load the RSS file into the object
//$doc->load('https://feed.ksta.de/feed/rss/kultur-medien/index.rss');
$feedtarget="https://feed.ksta.de/feed/rss/kultur-medien/index.rss";
if(isset($_GET['feed'])) {
    // id index exists
    $feedtarget=$_GET['feed'];
}
$runtime_log["1init"]= (microtime(true) - $time_start)/1000;
$rawxml=fgc_ttl($feedtarget,3600,$cache_path);
$runtime_log["2load"]= (microtime(true) - $time_start)/1000;
//$dom->loadHTML($rawhtml);
$doc->loadXML($rawxml);
$runtime_log["3parse"]= (microtime(true) - $time_start)/1000;

// Initialize empty array
$arrFeeds = array();
$feedtitle=$doc->getElementsByTagName('title')->item(0)->nodeValue;
$feeddesc=$doc->getElementsByTagName('description')->item(0)->nodeValue;
$feedlink=$doc->getElementsByTagName('link')->item(0)->nodeValue;
$feedgene=$doc->getElementsByTagName('generator')->item(0)->nodeValue;
$feedlang=$doc->getElementsByTagName('language')->item(0)->nodeValue;

// Get a list of all the elements with the name 'item'
$sentlinks=array();

foreach ($doc->getElementsByTagName('item') as $node) {
    $sum=(md5($node->getElementsByTagName('link')->item(0)->nodeValue));
    $mycachepath=$cache_path."./json_out/";
    if (!file_exists($mycachepath)) { 
        mkdir($mycachepath, 0777, true); 
    }
    $item_cache_file = $mycachepath .'/'. $sum.".rss.json";
    if(file_exists($item_cache_file)) {
        logheader("FGC-".$sum,"json-cached: ".$item_cache_file);
        //we have a cached json
        $string = file_get_contents($item_cache_file); 
        $itemRSS = json_decode($string, true);
        $item_cache_hit=$item_cache_hit+1;
        //array_push($arrFeeds, $itemRSS);
    } else {
        if($fetched < $maxfetch ) {
    logheader("FGC-".$sum,"json-new: $fetched / $maxfetch : ".$item_cache_file);
    $mydesc="";
    $mydate="";
    libxml_use_internal_errors(true);
    $dom = new DOMDocument;
    //$dom->loadHTMLFile($node->getElementsByTagName('link')->item(0)->nodeValue);
    //$rawhtml=file_get_contents($node->getElementsByTagName('link')->item(0)->nodeValue);
    if (!file_exists($cache_path. md5($node->getElementsByTagName('link')->item(0)->nodeValue))) { 
        $fetched=$fetched+1;
        $item_cache_hit=$item_cache_miss+1;
    }

    $rawhtml=mb_convert_encoding(fgc_ttl($node->getElementsByTagName('link')->item(0)->nodeValue,200000,$cache_path), 'HTML-ENTITIES', "UTF-8");
    //$dom->loadHTML($rawhtml);
    $dom->loadXML(mb_encode_numericentity($rawhtml, [0x80, 0x10FFFF, 0, ~0], 'UTF-8'));
    libxml_use_internal_errors(false);
    $mydate=$node->getElementsByTagName('pubDate')->item(0)->nodeValue;
    $rawaddxml="";
    $rawencxml="";
    //foreach(["modified","guid","creator"] as $term){
    foreach(["modified","guid","creator"] as $term){
        try {
            $domElement=$node->getElementsByTagName($term)->item(0);
            if(!($domElement->ownerDocument==null)){
                $newsnip=$domElement->ownerDocument->saveXML($domElement);
                $rawaddxml =$rawaddxml."\r\n".$newsnip;
            }
        } catch(Exception $e) {
            //echo "foo";
        }
    }
    //enclosure content
    foreach(["enclosure","content"] as $term){
        try {
            $domElement=$node->getElementsByTagName($term)->item(0);
            if(!($domElement->ownerDocument==null)){
                $newsnip=$domElement->ownerDocument->saveXML($domElement);
                $rawencxml =$rawencxml."\r\n".$newsnip;
                //echo $rawencxml;
            }
        } catch(Exception $e) {
            //echo "foo";
        }
    }
    //echo $rawaddxml;
    //$par = $node->getElementsByTagName('guid')->item(0);
    //echo $par->saveXML();
    //print($mydate);
    if($mydesc=="") {
    $classname="article-container";
    libxml_use_internal_errors(true);
    $dom = new DOMDocument;
    //$dom->loadHTMLFile('https://example.lan/rss.xml');
    //$rawhtml=file_get_contents('https://example.lan/rss.xml');
    $dom->loadHTML(mb_encode_numericentity($rawhtml, [0x80, 0x10FFFF, 0, ~0], 'UTF-8'));
    
    libxml_use_internal_errors(false);
    $xpath = new DOMXPath($dom);
    $hideclasses=array("related-topic","dm-paywall-wrapper","ad_teaser_1","ad_teaser_2","ad_teaser_3","header__firstrow","navbar-item",'column-right',"is-sidebar-meta",'u-hide-tablet',"trc_rbox_container","dm-taboola","dm-article-action-bar","kk_is_end");
    foreach($hideclasses as $removeclass) {
        foreach($xpath->query("//*[contains(@class, '$removeclass')]") as $e ) {
            // Delete this node
            $e->parentNode->removeChild($e);
        }
        }
    //$par = $dom->getElementsByTagName('picture')->item(0);
    $sndline="";
    $sentimgs=array();

    //foreach($dom->getElementsByTagName('picture') as $par) {
    //foreach($dom->getElementsByTagName('picture') as $par) {
    //    $longString = $par->$srcset;
    //    $pics = explode(",", $longString);
    //    $imgurl=$pics[0];
    //    if(!in_array($imgurl,$sentimgs)){
    //    $sndline=$sndline.$dom->saveXML($par);
    //    array_push($sentimgs,$imgurl);
    //    }
    //}

    ///////////////////////////////////
    //intro

    ////$classname="article-meta";
    $classname="dm-article__intro";
    $div =  $xpath->query("//*[contains(@class, '$classname')]")->item(0);
   
    //var_dump($div);
    if(!(null==$div)) {
        $sndline=$sndline.$dom->saveXML($div)." <br>";
     }
    //echo "$sndline";
   // }
   ////////////////////////////////////////////////////////////
    //maincontent 
    $attribute="data-article-content";
    $novalue="";
    //$div=$xpath->query('//div/@data-article-content')->item(0);
    //$div = $xpath->query("//*[@data-article-content='']")->item(0);
    $classname="kk_is_start";
    //$div =  $xpath->query("//*[contains(@class, '$classname')]")->item(0)->parentNode;
    $div=$dom->getElementsByTagName('article')->item(0);
    //var_dump($div);
    if(!(null==$div)) {
        $sndline=$sndline.$dom->saveXML($div)." <br>";
        //echo $dom->saveXML($div)." <br>";
     }
    //echo "$sndline\r\n";
 ///////////////////////// image
     if( !strstr($sndline,"<img") && !strstr($sndline,"<picture")){
        //append picture if not found in body
        $classname="current-image";
        foreach($xpath->query("//*[contains(@class, '$classname')]") as $par) {
            //$newsnip=$domElement->ownerDocument->saveHTML($par);
            $rawsnip=$dom->saveHTML($par);
            //echo "$rawsnip";
            $snipdom = new DOMDocument;
            $snipdom->loadHTML(mb_encode_numericentity($rawsnip, [0x80, 0x10FFFF, 0, ~0], 'UTF-8'));
            foreach($snipdom->getElementsByTagName('img') as $par) {
            $longString = $par->$srcset;
            //echo $longString;
            $singlesrc = $par->$src;
            $pics = explode(",", $longString);
            $imgurl=$pics[0];
            if(!in_array($imgurl,$sentimgs)){
               $sndline=$sndline.$snipdom->saveXML($par);
               array_push($sentimgs,$imgurl);
               }
            }
            if(empty($sentimgs)) {
                foreach($snipdom->getElementsByTagName('picture') as $par) {
                    $longString = $par->$srcset;
                    //echo $longString;
                    $pics = explode(",", $longString);
                    $imgurl=$pics[0];
                    if(!in_array($imgurl,$sentimgs)){
                       $sndline=$sndline.$snipdom->saveXML($par);
                       array_push($sentimgs,$imgurl);
                    }
                }
            }

     }
 

     //echo $sndline;
 }
 //heading
 $par = $dom->getElementsByTagName('title')->item(0);
 $sndline=$sndline."<br>".$par->textContent."<h1><br>";
    $mydesc=$sndline;
    } // end nodesc
    //$mydesc=str_replace('="/static','="https://lotta-magazin.de/static',$mydesc);
    //$mydesc=str_replace(',/static',',https://lotta-magazin.de/static',$mydesc);
    $mydesc=str_replace('</body>','',$mydesc);
    $mydesc=str_replace('<body>','',$mydesc);   
    $mydesc=str_replace('</article>','',$mydesc);
    $mydesc=str_replace('<article>','',$mydesc);
    $mydesc=str_replace('<!---->','',$mydesc);
    $mydesc=str_replace('<!--[-->','',$mydesc);
    $mydesc=str_replace('<!--]-->','',$mydesc);
    //echo "$mydesc\r\n";
	$itemRSS = array (
		'title' => $node->getElementsByTagName('title')->item(0)->nodeValue,
		'desc' => $mydesc,
		'link' => $node->getElementsByTagName('link')->item(0)->nodeValue,
		'date' => $mydate,
        'rawxml' => $rawaddxml,
        'encxml' => $rawencxml
	);
    file_put_contents($item_cache_file, json_encode($itemRSS));
    array_push($arrFeeds, $itemRSS);
    } // end maxfetch
} // end array_feeds

}
// Output
//print_r($arrFeeds);
$runtime_log["4process"]= (microtime(true) - $time_start)/1000;
$feedtitle=xmlencode($feedtitle);
header( "Content-type: text/xml; charset=UTF-8");
logheader("Items-Fetched",$fetched);
logheader("Items-Cached",$item_cache_hit);
if($cache_path==$_SERVER['DOCUMENT_ROOT']."../.cache/") {
    logheader("Items-Cachepath","webroot");
} else {
    logheader("Items-Cachepath","default");
}
logheader("Feed-Target",$feedtarget);

$xfsrc="int";
if(isset($_GET["feed"])) {
    $xfsrc="get";
}
$runtime_log["send"]= (microtime(true) - $time_start)/1000;
$runtimemsg="";
foreach($runtime_log as $key => $val) {
    $runtimemsg=$runtimemsg." ".$key."=".$val."|";
}
header( "X-Feed-Timing: ".$runtimemsg);


//header('Content-Type: application/rss+xml; charset=UTF-8');
echo "<?xml version='1.0' encoding='UTF-8'?>\r\n".'
<rss version="2.0"
  xmlns:access="http://www.bloglines.com/about/specs/fac-1.0"
  xmlns:admin="http://webns.net/mvcb/"
  xmlns:ag="http://purl.org/rss/1.0/modules/aggregation/"
  xmlns:annotate="http://purl.org/rss/1.0/modules/annotate/"
  xmlns:app="http://www.w3.org/2007/app"
  xmlns:atom="http://www.w3.org/2005/Atom"
  xmlns:audio="http://media.tangent.org/rss/1.0/"
  xmlns:blogChannel="http://backend.userland.com/blogChannelModule"
  xmlns:cc="http://web.resource.org/cc/"
  xmlns:cf="http://www.microsoft.com/schemas/rss/core/2005"
  xmlns:company="http://purl.org/rss/1.0/modules/company"
  xmlns:content="http://purl.org/rss/1.0/modules/content/"
  xmlns:conversationsNetwork="http://conversationsnetwork.org/rssNamespace-1.0/"
  xmlns:cp="http://my.theinfo.org/changed/1.0/rss/"
  xmlns:dc="http://purl.org/dc/elements/1.1/"
  xmlns:dcterms="http://purl.org/dc/terms/"
  xmlns:email="http://purl.org/rss/1.0/modules/email/"
  xmlns:ev="http://purl.org/rss/1.0/modules/event/"
  xmlns:feedburner="http://rssnamespace.org/feedburner/ext/1.0"
  xmlns:fh="http://purl.org/syndication/history/1.0"
  xmlns:foaf="http://xmlns.com/foaf/0.1/"
  xmlns:geo="http://www.w3.org/2003/01/geo/wgs84_pos#"
  xmlns:georss="http://www.georss.org/georss"
  xmlns:geourl="http://geourl.org/rss/module/"
  xmlns:g="http://base.google.com/ns/1.0"
  xmlns:gml="http://www.opengis.net/gml"
  xmlns:icbm="http://postneo.com/icbm"
  xmlns:image="http://purl.org/rss/1.0/modules/image/"
  xmlns:indexing="urn:atom-extension:indexing"
  xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd"
  xmlns:kml20="http://earth.google.com/kml/2.0"
  xmlns:kml21="http://earth.google.com/kml/2.1"
  xmlns:kml22="http://www.opengis.net/kml/2.2"
  xmlns:l="http://purl.org/rss/1.0/modules/link/"
  xmlns:mathml="http://www.w3.org/1998/Math/MathML"
  xmlns:media="http://search.yahoo.com/mrss/"
  xmlns:openid="http://openid.net/xmlns/1.0"
  xmlns:opensearch10="http://a9.com/-/spec/opensearchrss/1.0/"
  xmlns:opensearch="http://a9.com/-/spec/opensearch/1.1/"
  xmlns:opml="http://www.opml.org/spec2"
  xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
  xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"
  xmlns:ref="http://purl.org/rss/1.0/modules/reference/"
  xmlns:reqv="http://purl.org/rss/1.0/modules/richequiv/"
  xmlns:rss090="http://my.netscape.com/rdf/simple/0.9/"
  xmlns:rss091="http://purl.org/rss/1.0/modules/rss091#"
  xmlns:rss1="http://purl.org/rss/1.0/"
  xmlns:rss11="http://purl.org/net/rss1.1#"
  xmlns:search="http://purl.org/rss/1.0/modules/search/"
  xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
  xmlns:ss="http://purl.org/rss/1.0/modules/servicestatus/"
  xmlns:str="http://hacks.benhammersley.com/rss/streaming/"
  xmlns:sub="http://purl.org/rss/1.0/modules/subscription/"
  xmlns:svg="http://www.w3.org/2000/svg"
  xmlns:sx="http://feedsync.org/2007/feedsync"
  xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
  xmlns:taxo="http://purl.org/rss/1.0/modules/taxonomy/"
  xmlns:thr="http://purl.org/syndication/thread/1.0"
  xmlns:trackback="http://madskills.com/public/xml/rss/module/trackback/"
  xmlns:wfw="http://wellformedweb.org/CommentAPI/"
  xmlns:wiki="http://purl.org/rss/1.0/modules/wiki/"
  xmlns:xhtml="http://www.w3.org/1999/xhtml"
  xmlns:xlink="http://www.w3.org/1999/xlink"
  xmlns:xrd="xri://$xrd*($v*2.0)"
  xmlns:xrds="xri://$xrds">'."\r\n
<channel>\r\n
<title>$feedtitle</title>\r\n
<link>$feedlink</link>\r\n
<description>$feeddesc</description>";
if(!($feedlang=="")) {
    echo "<language>$feedlang</language>";
} else {
    echo "<language>en-us</language>";
}
if(!($feedgene=="")) {
    echo "<generator>$feedgene</generator>";
}

foreach($arrFeeds as $sendarr) {
  //$sendxml=xmlencode($sendarr["rawxml"])
  $sendxml=$sendarr["rawxml"];
  $sendxml=$sendxml."\r\n".$sendarr["encxml"];
  $title=$sendarr["title"];
  $link=$sendarr["link"];
  $description=$sendarr["desc"];
  $pdate=$sendarr["date"];
  echo "<item>\n
  <title>".xmlencode($title)."</title>\r\n
  <link>".htmlspecialchars($link)."</link>\r\n
  <description><![CDATA[$description]]></description>\r\n
  <pubDate>$pdate</pubDate>\r\n
  ".$sendxml."
</item>\r\n";
}
echo "</channel>\r\n</rss>";
