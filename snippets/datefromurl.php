<?php






$classname="article-meta";
libxml_use_internal_errors(true);
$dom = new DOMDocument;
$dom->loadHTMLFile('https://lotta-magazin.de/ausgabe/92/haftstrafen-fur-familie-frankenbach/');
libxml_use_internal_errors(false);
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
$returndate= date("D, d M Y H:i:s T", strtotime($returndate));
echo $returndate;