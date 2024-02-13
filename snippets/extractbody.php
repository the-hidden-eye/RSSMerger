<?php

$classname="article-container";
libxml_use_internal_errors(true);
$dom = new DOMDocument;
//$dom->loadHTMLFile('https://lotta-magazin.de/ausgabe/92/haftstrafen-fur-familie-frankenbach/');
$rawhtml=file_get_contents('https://lotta-magazin.de/ausgabe/92/haftstrafen-fur-familie-frankenbach/');
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
echo  $newdom->saveHTML($body);

//$par = $dom->getElementsByTagName('p')->item(0);
//$returndate=$dom->saveXML($par);
//$returndate=str_replace('<p>','',$returndate);
//$returndate=str_replace('</p>','',$returndate);
//$returndate= date("D, d M Y H:i:s T", strtotime($returndate));
//echo $returndate;