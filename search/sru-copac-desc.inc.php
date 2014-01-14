<?php
header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
// Version/Time-stamp: "2014-01-14 18:06:46 zimmel"
// This is not SRU, but MODS/XML
// Ajax-Abfrage

function myget ($query,$xpath) {
  $result=array();

  foreach ($xpath->query($query) as $item) {
    if (!empty($item->nodeValue)) { $result[]=trim($item->nodeValue);} 
  }
	
  switch (sizeof($result)) {
	case 0: return ""; break; // falls Feld fehlt
	case 1: return $result[0]; break; // einfaches Feld (String)
	default: return $result; // mehrfach vorkommende Felder (Array), -> siehe/siehe-auch
  }
}

// 1. query: get the COPAC no.
//$x = "http://copac.ac.uk/search?isn=0446576441&format=XML+-+MODS";
$x = "http://copac.ac.uk/search?isn=".trim($_GET['isbn'])."&format=XML+-+MODS";
$Dom = new DOMDocument;
$Dom->load($x);
$xpath = new DOMXPath( $Dom );
$xpath->registerNamespace("m","http://www.loc.gov/mods/v3");
//$record = $xpath->query( "//m:mods" );
// get only the first one, we are lazy:
$copacNo = myget("//m:mods[1]/m:recordInfo/m:recordIdentifier[@source='copac']",$xpath);
echo "[COPAC-Number: ".$copacNo . "]<br/>";

// 2. query: get Description
$x = "http://copac.ac.uk/search?rn=1&cid=".$copacNo; 
//$x = "TestCopac.htm";
$neuDom = new DOMDocument;

libxml_use_internal_errors(true);
$neuDom->loadHTMLFile($x);
$xpath = new DOMXPath( $neuDom );

$summary = myget("//dt[text()='Summary']/following-sibling::*[1]",$xpath);

if (!empty($summary)) {
		echo $summary;
		$alephseq .= $_GET['sysno']." 078   L \$\$aCOPAC-Summary\r\n"; // Abrufzeichen
		$alephseq .= $_GET['sysno']." 750   L \$\$a".$summary."\r\n";
	} else {
		echo "keine Summary gefunden,";
	}

$file ="data/copac/summary/copac-summary.txt";
$fp = fopen($file, "a") or die("Couldn't open $file for writing!");
fwrite($fp,$alephseq) or die(" es wurde nichts in Datei geschrieben!");
fclose($fp);

?>