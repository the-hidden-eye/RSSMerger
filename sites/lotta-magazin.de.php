<?php
error_reporting(E_ERROR | E_PARSE);
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

// Create a new DOMDocument object
$doc = new DOMDocument();

// Load the RSS file into the object
$doc->load('https://lotta-magazin.de/rss.xml');

// Initialize empty array
$arrFeeds = array();
$feedtitle=$doc->getElementsByTagName('title')->item(0)->nodeValue;
$feeddesc=$doc->getElementsByTagName('description')->item(0)->nodeValue;
$feedlink=$doc->getElementsByTagName('link')->item(0)->nodeValue;
$feedgene=$doc->getElementsByTagName('generator')->item(0)->nodeValue;
$feedlang=$doc->getElementsByTagName('language')->item(0)->nodeValue;

// Get a list of all the elements with the name 'item'
foreach ($doc->getElementsByTagName('item') as $node) {
    $mydesc="";
    $mydate="";
    libxml_use_internal_errors(true);
    $dom = new DOMDocument;
    //$dom->loadHTMLFile($node->getElementsByTagName('link')->item(0)->nodeValue);
    //$rawhtml=file_get_contents($node->getElementsByTagName('link')->item(0)->nodeValue);
    $rawhtml=fgc($node->getElementsByTagName('link')->item(0)->nodeValue);
    //$dom->loadHTML($rawhtml);
    $dom->loadHTML(mb_encode_numericentity($rawhtml, [0x80, 0x10FFFF, 0, ~0], 'UTF-8'));
    libxml_use_internal_errors(false);
    try {
        $mydate=$node->getElementsByTagName('pubDate')->item(0)->nodeValue;
       } catch (Exception $e) {
       }
    if($mydate=="") {
        $classname="article-meta";
        $xpath = new DOMXPath($dom);
        $div = $xpath->query("//*[contains(@class, '$classname')]");
        //$div = $div->item(0);
        $div=$div->item(0);
        //echo $dom->saveXML($div);
        $newdom = new DOMDocument;
        $newdom->loadHTML($dom->saveXML($div));
        $par = $dom->getElementsByTagName('p')->item(0);
        $returndate=$dom->saveXML($par);
        $returndate=str_replace('<p>','',$returndate);
        $returndate=str_replace('</p>','',$returndate);
        
        $returndate=date("D, d M Y H:i:s T", strtotime($returndate));
        //echo $returndate;
        $mydate=$returndate;
    }
    
    //print($mydate);
    
    //try {
    // $mydesc=$node->getElementsByTagName('description')->item(0)->nodeValue;
    //} catch (Exception $e) {
    //}
    if($mydesc=="") {
        $classname="article-container";
        libxml_use_internal_errors(true);
        $dom->loadHTML(mb_encode_numericentity($rawhtml, [0x80, 0x10FFFF, 0, ~0], 'UTF-8'));
        libxml_use_internal_errors(false);
        $xpath = new DOMXPath($dom);
        $div = $xpath->query("//*[contains(@class, '$classname')]");
        //$div = $div->item(0);
        $div=$div->item(0);
        //echo $dom->saveXML($div);
        $newdom = new DOMDocument;
        $newdom->loadHTML($dom->saveXML($div));
        $xpath = new DOMXPath($newdom);
        $removeclass="column-right";
        $hideclasses=array('column-right',"is-sidebar-meta",'u-hide-tablet');
        foreach($hideclasses as $removeclass) {
        foreach($xpath->query("//*[contains(@class, '$removeclass')]") as $e ) {
            // Delete this node
            $e->parentNode->removeChild($e);
        }
        }
        $newhtml=$newdom->saveXML();
        $newdom->loadHTML($newhtml);
        $body = $newdom->documentElement->lastChild;
        $mydesc=$newdom->saveHTML($body);
    }
    $mydesc=str_replace('="/static','="https://lotta-magazin.de/static',$mydesc);
    $mydesc=str_replace(',/static','=",https://lotta-magazin.de/static',$mydesc);


	$itemRSS = array (
		'title' => $node->getElementsByTagName('title')->item(0)->nodeValue,
		'desc' => $mydesc,
		'link' => $node->getElementsByTagName('link')->item(0)->nodeValue,
		'date' => $mydate
	);
	array_push($arrFeeds, $itemRSS);
}
// Output
//print_r($arrFeeds);

header( "Content-type: text/xml");
 
echo "<?xml version='1.0' encoding='UTF-8'?>
<rss version='2.0'>
<channel>
<title>$feedtitle</title>
<link>$feedlink</link>
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

  echo "<item>
  <title>$title</title>
  <link>$link</link>
  <description>$description</description>
  </item>";
}

echo "</channel></rss>";