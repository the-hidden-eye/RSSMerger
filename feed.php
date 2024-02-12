<?php 
require_once "class_rssmerge.php";
require_once "class_rssfeed.php";

$rss = new RSSMerger();
//$rss->add("http://daverix.net/blog.rss");
//$rss->add("http://www.laurell.nu/feed.xml");
$sources=$_GET['link'];

//if(!is_array($sources))
foreach(explode("_SPLITRSS_", $pizza) as $sourcelink) {
    $rss->add($sourcelink);
}
$rss->sort();
$xml = new RSSFeed("RSS merger","http://daverix.net/rssm/","a simple rss merge script","http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']);
$xml->setLanguage("en");
$feeds = $rss->getFeeds(99);

sentlinks=[];

foreach($feeds as $f) {
    if(!in_array($f->link, sentlinks)) {
        array_push($sentlinks,$f->link);
        $xml->addItem($f->title,$f->link,$f->description,$f->author,$f->guid,$f->time);
        }
}
$xml->displayXML();
?>
