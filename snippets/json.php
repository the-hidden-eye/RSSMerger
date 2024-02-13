<?php
error_reporting(E_ERROR | E_PARSE);
$maxfetch=5;

function fgc($url) {
    $cache_path="cache/";
    $cache_file = $cache_path . md5($url);
    if (!file_exists($cache_path)) { 
        mkdir($cache_path, 0777, true); 
    } 
    if (file_exists($cache_file)) {
        if(time() - filemtime($cache_file) > 864000) {
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
function fgc_ttl($url,$cachetime) {
    $cache_path="cache/";
    $cache_file = $cache_path . md5($url);
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

//$feed->load($_GET['source']);
// Create a new DOMDocument object
$feed = new DOMDocument();

// Load the RSS file into the object
// Load the RSS file into the object
//$doc->load('https://lotta-magazin.de/rss.xml');
$rawxml=fgc_ttl("https://feed.ksta.de/feed/rss/kultur-medien/index.rss",3600);

//$dom->loadHTML($rawhtml);
//$feed->loadXML(mb_encode_numericentity($rawxml, [0x80, 0x10FFFF, 0, ~0], 'UTF-8'));
$feed->loadXML($rawxml);

$json = array();

$json['title'] =  $feed->getElementsByTagName('channel')->item(0)->getElementsByTagName('title')->item(0)->firstChild->nodeValue;
$json['description'] = $feed->getElementsByTagName('channel')->item(0)->getElementsByTagName('description')->item(0)->firstChild->nodeValue;
$json['link'] =  $feed->getElementsByTagName('channel')->item(0)->getElementsByTagName('link')->item(0)->firstChild->nodeValue;


$items = $feed->getElementsByTagName('channel')->item(0)->getElementsByTagName('item');
$json['items'] = array();
$i = 0;
foreach($items as $item) {
   $json['items'][$i]['title'] = $item->getElementsByTagName('title')->item(0)->firstChild->nodeValue;
   $json['items'][$i]['description'] = $item->getElementsByTagName('description')->item(0)->firstChild->nodeValue;
   $json['items'][$i]['pubdate'] = $item->getElementsByTagName('pubDate')->item(0)->firstChild->nodeValue;
   $json['items'][$i]['guid'] = $item->getElementsByTagName('guid')->item(0)->firstChild->nodeValue;
   $json['items'][$i]['link'] = $item->getElementsByTagName('link')->item(0)->firstChild->nodeValue;
   //$json['items'][$i]['url'] = $item->getELementsByTagName('nodeValue')->item(0)->firstChild->getAttribute('url');

    $i++;
}

echo json_encode($json,JSON_PRETTY_PRINT);
foreach($json["items"] as $item ) {
    if($fetched < $maxfetch ) {
        $cache_file = "cache/" . md5($item["title"].".json";
        if(file_exists($cache_file)) {
            //we have a cached json
            $string = file_get_contents($cache_file); 
            $itemRSS = json_decode($string, true);

        } else {
        $mydesc="";
        $mydate="";
        libxml_use_internal_errors(true);
        $dom = new DOMDocument;
        //$dom->loadHTMLFile($item["title"];
        //$rawhtml=file_get_contents($item["title"];
        if (!file_exists("cache/". md5($item["title"])) { 
            $fetched=$fetched+1;
        }
    }
}

//{
//    "title": "Leute: Schuss bei Filmdreh: Baldwin pl\u00e4diert auf \u201enicht schuldig\u201c",
//    "description": "Alec Baldwin weist die Vorw\u00fcrfe erneut zur\u00fcck: In einer Anklage wegen fahrl\u00e4ssiger T\u00f6tung nach einem Schuss-Vorfall bei einem Filmdreh pl\u00e4diert der Schauspieler auf nicht schuldig.",
//    "pubdate": "Thu, 01 Feb 2024 09:59:23 +0100",
//    "guid": "https:\/\/www.ksta.de\/730147",
//    "link": "https:\/\/www.ksta.de\/panorama\/dpa-panorama\/schuss-bei-filmdreh-baldwin-plaediert-auf-nicht-schuldig-730147"
//}