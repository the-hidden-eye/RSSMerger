<?php

$cache_time=1666;
// Starts with util function
function startsWith($haystack, $needle)
{
    return (substr($haystack, 0, strlen($needle)) === $needle);
}

// Append sml elements
function sxml_append(SimpleXMLElement $to, SimpleXMLElement $from)
{
    $toDom = dom_import_simplexml($to);
    $fromDom = dom_import_simplexml($from);
    $toDom->appendChild($toDom->ownerDocument->importNode($fromDom, true));
}
//caching
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

// Handle an RSS feed
function handle_feed($feed,$myttl,$cachepath)
{
    // Check valid url
    if (!filter_var($feed, FILTER_VALIDATE_URL)) {
        header("HTTP/1.1 422 Unprocessable Entity");
        die("The provided feed '" . $feed . "' is malformed. Ensure it is a valid url.");
    }

    // Ensure http
    if (startsWith($feed, "https://")) {
        $feed = "http://" . explode("https://", $feed, 2)[1];
    }

    // Get contents
    $sum=md5($feed);
    try {
        //$feed_content = @file_get_contents($feed);
        $feed_content = @fgc_ttl($feed,$myttl,$cachepath);
    } catch (Exception $e) {
        //header("HTTP/1.1 500 Internal Server Error");
        //die("An error occurred whilst fetching the feed '" . $feed . "'.");
        header("X-Soft-Fail-Pre-".$sum.": An error occurred whilst fetching the feed '" . $feed . "'.");
    }
    if (!$feed_content) {
        //header("HTTP/1.1 500 Internal Server Error");
        //die("An invalid response was received when fetching the feed '" . $feed . "'");
        header("X-Soft-Fail-Empty-".$sum.": An error occurred whilst fetching the feed '" . $feed . "'.");
    }

    // Parse
    try {
        $feed_content = simplexml_load_string($feed_content);
    } catch (Exception $e) {
        //header("HTTP/1.1 500 Internal Server Error");
        //die("An error occurred whilst parsing the feed '" . $feed . "'.");
       header("X-Soft-Fail-Final-".$sum.": An error occurred whilst parsing the feed '" . $feed . "'.");

    }

    // Done
    return $feed_content->xpath('/rss//item');
}

$myttl="";
$feedtitle="";
$feeddesc="";
// Get feeds for URI
try {
    
    $feeds = [];
    if (isset($_GET['feed'])) {
        if (is_array($_GET['feed'])) {
            foreach ($_GET['feed'] as $f) {
                $xml=simplexml_load_string(fgc_ttl($f,$cache_time,$cache_path));
                $ltitle = $xml->channel->title;
                $feedtitle=$feedtitle." ".$ltitle;
                $ldescription = $xml->channel->description;
                $feeddesc=$feeddesc." ".$ldescription;
                $feeds[] = handle_feed($f,$cache_time,$cache_path);
            }
        } else {
            $f=$_GET['feed'];
            $feeds[] = handle_feed($f,$cache_time,$cache_path);
            $xml=simplexml_load_string(fgc_ttl($f,$cache_time,$cache_path));
            $ltitle = $xml->channel->title;
            $feedtitle=$feedtitle." ".$ltitle;
            $ldescription = $xml->channel->description;
            $feeddesc=$feeddesc." ".$ldescription;
            $feeds[] = handle_feed($f,$cache_time,$cache_path);
        }
    }
    if (isset($_GET['feeds'])) {
        if (is_array($_GET['feeds'])) {
            foreach ($_GET['feeds'] as $f) {
                $feeds[] = handle_feed($f,$cache_time,$cache_path);
                $xml=simplexml_load_string(fgc_ttl($f,$cache_time,$cache_path));
                $ltitle = $xml->channel->title;
                $feedtitle=$feedtitle." ".$ltitle;
                $ldescription = $xml->channel->description;
                $feeddesc=$feeddesc." ".$ldescription;
                $feeds[] = handle_feed($f,$cache_time,$cache_path);
            }
        } else {
            $feeds[] = handle_feed($_GET['feeds'],$myttl,$cache_path);
        }
    }

    if (!isset($_GET['feed']) && !isset($_GET['feeds'])) {
        header("HTTP/1.1 422 Unprocessable Entity");
        die("Please provide RSS feeds to merge in URL. (Eg. ?feeds[]=https://blog.jetbrains.com/feed/&feeds[]=https://blog.jetbrains.com/idea/feed/)");
    }
} catch (Exception $e) {
    //header("HTTP/1.1 500 Internal Server Error");
    //die("An error occurred whilst parsing feeds from request URL.");
    header("X-Soft-Fail-Final: An error occurred whilst parsing feeds from request URL.");
}

// Export the RSS feed
try {
    // Combine
    $feeds = array_merge(...$feeds);
    
    // Sort
    usort($feeds, function ($x, $y) {
        return strtotime($y->pubDate) - strtotime($x->pubDate);
    });
    $newfeeds=array();
    $sentlinks=array();
    foreach ($feeds as $f) {
        if(!in_array($f->link, $sentlinks)) {
            array_push($sentlinks,$f->link);
            array_push($newfeeds,$f);
        }
    }
    $feeds=$newfeeds;
    // Create RSS
    $root = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . '/';
    
    if($feedtitle=="") { $feedtitle="RSS Merger"; }

    if($feeddesc=="") { $feeddesc="A PHP tool to merge multiple RSS streams into one output."; }
    $rss = new SimpleXMLElement('<rss><channel><title>'.$feedtitle.'</title><description>'.$feeddesc.'</description><link>' . $root . '</link></channel></rss>');
    $rss->addAttribute('version', '2.0');
    //var_dump($feeds);
    $sentlinks=array();
    $senttitles=array();
    foreach ($feeds as $feed) {
        $myurl="";
        //var_dump($feed);
        $myurl=(string) $feed->link ;
        //var_dump($feed->attributes());
        //echo $myurl;
        $mytitle=(string) $feed->link ;
        if(!in_array($myurl,$sentlinks) && !in_array($mytitle,$senttitles)) {
            sxml_append($rss->channel, $feed);
            array_push($sentlinks,$myurl);
            array_push($senttitles,$mytitle);

        }
    }
//    // Display
//    header("Content-type: text/xml");
//    //echo $rss->asXML(); // Ugly print
//    $dom = dom_import_simplexml($rss)->ownerDocument; // Pretty print
//    $dom->formatOutput = true;
//    echo $dom->saveXML();
//    die();
} catch (Exception $e) {
    header("X-Soft-Fail: An error occurred whilst generating final RSS feed. ");
    //die("");
}
?>