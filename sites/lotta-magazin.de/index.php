<?php
error_reporting(E_ERROR | E_PARSE);
$maxfetch=5;
function logheader($term,$msg) {
    if (php_sapi_name() == "cli") {
        // In cli-mode
        fwrite(STDERR,$term." : ".$msg."\n");
    } else {
        // Not in cli-mode
        header("X-".$term.": ".$msg);
    }
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

if(isset($_SERVER['DOCUMENT_ROOT']) && !empty($_SERVER['DOCUMENT_ROOT'] && $_SERVER['DOCUMENT_ROOT']!="/" ) ) {
    if(dirname($_SERVER['DOCUMENT_ROOT']) != "/" ) {
        $cache_path=dirname($_SERVER['DOCUMENT_ROOT'])."/.cache/";
        if (!file_exists($cache_path)) { 
                mkdir($cache_path, 0777, true); 
                if (!file_exists($cache_path)) { 
                 $cache_path=dirname( dirname(__FILE__) )."/.cache/";
                }
        }
    } else { $cache_path=dirname( dirname(__FILE__) )."/.cache/"; }
} else { 
    $cache_path=dirname( dirname(__FILE__) )."/.cache/"; 
}

if (!file_exists($cache_path)) { 
    mkdir($cache_path, 0777, true); 
} 

// Create a new DOMDocument object
$doc = new DOMDocument();
// Load the RSS file into the object
//$doc->load('https://lotta-magazin.de/rss.xml');
$rawxml=fgc_ttl("https://lotta-magazin.de/rss.xml",3600,$cache_path);
//$dom->loadHTML($rawhtml);
$doc->loadXML($rawxml);

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
    $cache_file = "cache/" . md5($node->getElementsByTagName('link')->item(0)->nodeValue).".json";
    if(file_exists($cache_file)) {
        //we have a cached json
        $string = file_get_contents($cache_file); 
        $itemRSS = json_decode($string, true);
    } else {
    $mydesc="";
    $mydate="";
    libxml_use_internal_errors(true);
    $dom = new DOMDocument;
    //$dom->loadHTMLFile($node->getElementsByTagName('link')->item(0)->nodeValue);
    //$rawhtml=file_get_contents($node->getElementsByTagName('link')->item(0)->nodeValue);
    if (!file_exists("cache/". md5($node->getElementsByTagName('link')->item(0)->nodeValue))) { 
        $fetched=$fetched+1;
    }
    $rawhtml=mb_convert_encoding(fgc_ttl($node->getElementsByTagName('link')->item(0)->nodeValue,3600,$cache_path), 'HTML-ENTITIES', "UTF-8"); ;
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
    ////}
    //if($mydesc=="") {
    //    $classname="article-container";
    //    libxml_use_internal_errors(true);
    //    $utfhtml=mb_convert_encoding($rawhtml, 'HTML-ENTITIES', "UTF-8");     
    //    $dom->loadHTML(mb_encode_numericentity($utfhtml, [0x80, 0x10FFFF, 0, ~0], 'UTF-8'));
    //    //$dom->loadHTMLFile($node->getElementsByTagName('link')->item(0)->nodeValue);
    //    //$dom->loadHTML($rawhtml);
    //    libxml_use_internal_errors(false);
    //    $xpath = new DOMXPath($dom);
    //    $div = $xpath->query("//*[contains(@class, '$classname')]");
    //    //$div = $div->item(0);
    //    $div=$div->item(0);
    //    //echo $dom->saveXML($div);
    //    $newdom = new DOMDocument;
    //    $newhtml=$dom->saveXML($div);
    //    $newdom->loadHTML(mb_substr($dom->saveXML($div), 6, -7, "UTF-8"));
    //    $xpath = new DOMXPath($newdom);
    //    $removeclass="column-right";
    //    $hideclasses=array("header__firstrow","navbar-item",'column-right',"is-sidebar-meta",'u-hide-tablet');
    //    foreach($hideclasses as $removeclass) {
    //    foreach($xpath->query("//*[contains(@class, '$removeclass')]") as $e ) {
    //        // Delete this node
    //        $e->parentNode->removeChild($e);
    //    }
    //    }
    //    //$hello=$newdom->documentElement->firstChild;
    //    //$hello->remove();
    //    //$newhtml=mb_substr($newdom->saveXML(), 6, -7, "UTF-8");
    //    $newhtml=$newdom->saveXML();
    //    $newdom->loadHTML($newhtml);
    //    $body = $newdom->documentElement->lastChild;
    //    $mydesc=$newdom->saveHTML($body);
    //}
    if($mydesc=="") {
    $classname="article-container";
    libxml_use_internal_errors(true);
    $dom = new DOMDocument;
    //$dom->loadHTMLFile('https://lotta-magazin.de/ausgabe/92/haftstrafen-fur-familie-frankenbach/');
    //$rawhtml=file_get_contents('https://lotta-magazin.de/ausgabe/92/haftstrafen-fur-familie-frankenbach/');
    $dom->loadHTML(mb_encode_numericentity($rawhtml, [0x80, 0x10FFFF, 0, ~0], 'UTF-8'));
    
    libxml_use_internal_errors(false);
    $xpath = new DOMXPath($dom);
    $hideclasses=array("header__firstrow","navbar-item",'column-right',"is-sidebar-meta",'u-hide-tablet');
    foreach($hideclasses as $removeclass) {
        foreach($xpath->query("//*[contains(@class, '$removeclass')]") as $e ) {
            // Delete this node
            $e->parentNode->removeChild($e);
        }
        }
    //$par = $dom->getElementsByTagName('picture')->item(0);
    $sndline="";
    $sentimgs=array();

    foreach($dom->getElementsByTagName('picture') as $par) {
        $longString = $par->$srcset;
        $pics = explode(",", $longString);
        $imgurl=$pics[0];
        if(!in_array($imgurl,$sentimgs)){
        $sndline=$sndline.$dom->saveXML($par);
        array_push($sentimgs,$imgurl);
        }
    }
    $par = $dom->getElementsByTagName('title')->item(0);
    $sndline=$sndline."<br><h1>".$par->textContent."</h1><br>";
    //$classname="article-meta";
    $classname="column-left";
    $div = $xpath->query("//*[contains(@class, '$classname')]")->item(0);
    $mydesc=$sendline=$sndline.$dom->saveXML($div)." <br>";
    }
    $mydesc=str_replace('="/static','="https://lotta-magazin.de/static',$mydesc);
    $mydesc=str_replace(',/static',',https://lotta-magazin.de/static',$mydesc);
    $mydesc=str_replace('</body>','',$mydesc);
    $mydesc=str_replace('<body>','',$mydesc);

	$itemRSS = array (
		'title' => $node->getElementsByTagName('title')->item(0)->nodeValue,
		'desc' => $mydesc,
		'link' => $node->getElementsByTagName('link')->item(0)->nodeValue,
		'date' => $mydate
	);
    file_put_contents("cache/".md5($node->getElementsByTagName('link')->item(0)->nodeValue).".json", json_encode($itemRSS));
    }
	array_push($arrFeeds, $itemRSS);
  }
}
// Output
//print_r($arrFeeds);

//header( "Content-type: text/xml");
header('Content-Type: application/rss+xml; charset=UTF-8');
 
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
  <title>".htmlspecialchars($title)."</title>\r\n
  <link>".htmlspecialchars($link)."</link>\r\n
  <pubDate>$pdate</pubDate>\r\n
  <description><![CDATA[$description]]></description>
  </item>\r\n";
}

echo "</channel>\r\n</rss>";