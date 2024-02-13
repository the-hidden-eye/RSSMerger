<?php
$time_start = microtime(true);
$runtime_log=array();

$cache_path="../.cache/";
if(isset($_SERVER['DOCUMENT_ROOT'] )) {
    $cache_path=$_SERVER['DOCUMENT_ROOT']."../.cache/";
}

if (!file_exists($cache_path)) { 
    mkdir($cache_path, 0777, true); 
} 

error_reporting(E_ERROR | E_PARSE);
$maxfetch=23;
if(isset($_GET['maxfetch']) && is_int($_GET['maxfetch'])) {
    // id index exists
    $feedtarget=$_GET['maxfetch'];

}
$item_cache_misss=0;
$item_cache_hit=0;
$feed_cache_miss=0;
$feed_cache_hit=0;
function xmlencode($input) {

return str_replace(
    ['<', '>','&'],
    ['&lt;', '&gt;' , '&amp;',],
    html_entity_decode($input)
);  
}

function fgc_ttl($url,$cachetime) {
    $cache_path="../.cache/";
    if(isset($_SERVER['DOCUMENT_ROOT'] )) {
        $cache_path=$_SERVER['DOCUMENT_ROOT']."../.cache/";
    }
    $cache_file = $cache_path . md5($url).".cache";
    if (!file_exists($cache_path)) { 
        mkdir($cache_path, 0777, true); 
    } 
    if (file_exists($cache_file)) {
        if(time() - filemtime($cache_file) > $cachetime) {
            $cache = file_get_contents($url);
            file_put_contents($cache_file, $cache);
        } else {
            $cache = file_get_contents($cache_file);
        }
    } else {
        $cache = file_get_contents($url);
        file_put_contents($cache_file, $cache);
    }
    return $cache;
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
$rawxml=fgc_ttl($feedtarget,3600);
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
foreach ($doc->getElementsByTagName('item') as $node) {
  if($fetched < $maxfetch ) {
    $item_cache_file = $cache_path . md5($node->getElementsByTagName('link')->item(0)->nodeValue).".json";
    if(file_exists($item_cache_file)) {
        //we have a cached json
        $string = file_get_contents($item_cache_file); 
        $itemRSS = json_decode($string, true);
        $item_cache_hit=$item_cache_hit+1;
    } else {
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
    $rawhtml=mb_convert_encoding(fgc_ttl($node->getElementsByTagName('link')->item(0)->nodeValue,86400), 'HTML-ENTITIES', "UTF-8");
    //$dom->loadHTML($rawhtml);
    $dom->loadXML(mb_encode_numericentity($rawhtml, [0x80, 0x10FFFF, 0, ~0], 'UTF-8'));
    libxml_use_internal_errors(false);
    $mydate=$node->getElementsByTagName('pubDate')->item(0)->nodeValue;
    $rawaddxml="";
    foreach(["guid","enclosure","content","creator","modified"] as $term){
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
    //echo $rawaddxml;

    //$par = $node->getElementsByTagName('guid')->item(0);
    //echo $par->saveXML();
    //print($mydate);
    

    if($mydesc=="") {
    $classname="article-container";
    libxml_use_internal_errors(true);
    $dom = new DOMDocument;
    //$dom->loadHTMLFile('https://lotta-magazin.de/ausgabe/92/haftstrafen-fur-familie-frankenbach/');
    //$rawhtml=file_get_contents('https://lotta-magazin.de/ausgabe/92/haftstrafen-fur-familie-frankenbach/');
    $dom->loadHTML(mb_encode_numericentity($rawhtml, [0x80, 0x10FFFF, 0, ~0], 'UTF-8'));
    
    libxml_use_internal_errors(false);
    $xpath = new DOMXPath($dom);
    $hideclasses=array("dm-paywall-wrapper","ad_teaser_1","ad_teaser_2","ad_teaser_3","header__firstrow","navbar-item",'column-right',"is-sidebar-meta",'u-hide-tablet',"trc_rbox_container","dm-taboola","dm-article-action-bar","kk_is_end");
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
        //echo $sndline;
    }
    //heading
    $par = $dom->getElementsByTagName('title')->item(0);
    $sndline=$sndline."<br>".$par->textContent."<h1><br>";
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
        'addxml' => $rawaddxml
	);
    file_put_contents($item_cache_file, json_encode($itemRSS));
    }
	array_push($arrFeeds, $itemRSS);
  }
}
// Output
//print_r($arrFeeds);
$runtime_log["4process"]= (microtime(true) - $time_start)/1000;
$feedtitle=xmlencode($feedtitle);
header( "Content-type: text/xml; charset=UTF-8");
header( "X-Items-Fetched: ".$fetched);
header( "X-Items-Cached: ".$item_cache_hit);
if($cache_path==$_SERVER['DOCUMENT_ROOT']."../.cache/") {
    header( "X-Items-Cachepath: webroot");
} else {
    header( "X-Items-Cachepath: default");
}
header( "X-Feed-Target: ".$feedtarget);
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
echo "<?xml version='1.0' encoding='UTF-8'?>\r\n
<rss version='2.0'>\r\n
<channel>\r\n
<title>$feedtitle</title>\r\n
<link>$feedlink</link>\r\n
<description>$feeddesc</description>";
if($feedlang=="") {
    echo "<language>$feedlang</language>";
} else {
    echo "<language>en-us</language>";
}
if(!($feedgene=="")) {
    echo "<generator>$feedgene</generator>";
}

foreach($arrFeeds as $sendarr) {
  $title=$sendarr["title"];
  $link=$sendarr["link"];
  $description=$sendarr["desc"];
  $pdate=$sendarr["date"];
  echo "<item>\n
  <title>".xmlencode($title)."</title>\r\n
  <link>".htmlspecialchars($link)."</link>\r\n
  <pubDate>$pdate</pubDate>\r\n
  <description><![CDATA[$description]]></description>\r\n
  ".xmlencode($rawaddxml)."
</item>\r\n";
}
echo "</channel>\r\n</rss>";
