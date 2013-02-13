<?php
// Version/Time-stamp: "2012-03-12 17:01:04 zimmel"
// Daniel Zimmel
// Dateiname: make-csv.php (basiert auf make-bibtex.php)
//
// utf8 funzt nicht zuverlaessig als header() -> daher als workaround utf8_decode() in myget() verwendet
//header("Content-Type: text/plain");
header("Charset: utf-8");
//header("Content-Type: application/octet-stream"); 
//header("Content-Disposition: attachment; filename=\"aleph-export.csv\";"); 

$oldSetting = libxml_use_internal_errors( true ); 
libxml_clear_errors(); 

$doc = new DOMDocument(); 

$doc->strictErrorChecking = false;
$doc->validateOnParse = false;

if (!empty($_GET['set_num'])) {
	$set_num = $_GET['set_num']; 

	// leading zeroes auffuellen, falls noetig (auskommentiert)
	/* function number_pad($number,$n) { */
	/* 	return str_pad((int) $number,$n,"0",STR_PAD_LEFT); */
	/* } */
	/* $set_num = number_pad($set_num,6); */


//$treffer = trim($_GET['treffer']);
// output beschraenkt auf 100
$path = "http://aleph.mpg.de/X?op=present&set_entry=000000001-000000100&set_number=".$set_num."&base=rdg01";

$doc->load($path);

$xpath = new DOMXPath( $doc ); 

$data = $xpath->query( "//record" );

// fehlende XML-Felder abfangen und Xpath-Anfrage:
function myget ($query,$xpath) {
  $result=array();

  foreach ($xpath->query($query) as $item) {
    if (!empty($item->nodeValue)) { $result[]=trim($item->nodeValue);} 
  }
	
  switch (sizeof($result)) {
	case 0: return ""; break; // falls Feld fehlt
	case 1: return utf8_decode($result[0]); break; // einfaches Feld (String) + uft8_decode(), da utf8 als header() nicht richtig funzt (s.o.)
	default: return utf8_decode($result[0]); // mehrfach vorkommende Felder (Array) = nie als Array ausgeben, immer nur den ersten Eintrag
  }
}
 
$alles = array();

foreach ( $data as $item ) {

	$newDom = new DOMDocument;
	$newDom->appendChild($newDom->importNode($item,true));
 

	$xpath = new DOMXPath( $newDom ); 

  $mab540 = myget("//varfield[@id='540'][1]/subfield[@label='a']",$xpath); // [1] first element
  $mab542 = myget("//varfield[@id='542'][1]/subfield[@label='a']",$xpath); // [1] first element
  $mab544 = myget("//varfield[@id='544']/subfield[@label='a']",$xpath);
  $cat_check = myget("//varfield[@id='CAT']/subfield[@label='a']",$xpath);

	$barcode = myget("//varfield[@id='Z30']/subfield[@label='5']",$xpath);
	$sysno = myget("//doc_number",$xpath);
	$alles[] = array(

												'mab540' => $mab540,
												'barcode' => $barcode,
												'sysno' => $sysno,
												'cat_check' => $cat_check,
      
		);

} 
}
?>

<html> <head>
<title>SRU-Suche nach Subject Headings (Beta)</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" media="all" type="text/css" href="make-csv.css" />
</head>

<body>
<div id="box">
<div id="boxlinks">
	<h1>PHP-Skript, um via SRU nach bibliographischen Daten suchen</h1>
	
	Die Suchergebnisse werden zus&auml;tzlich zur Anzeige in einem Aleph-Sequential-File gespeichert.<br/>

	<h1>1a. ISBN-Liste erzeugen aus Aleph-Suchset: (<a href="http://wiki/index.php?title=BiblioGBVSubjectHeadings">Hilfe</a>)</h1>
	<p>
	Wichtiger Hinweis: das Suchset ist immer auf 100 Titel beschr&auml;nkt!<br/>
	</p>
	<p>
<form action="sru.php" method="get">
<em>Aleph-Set-No. eingeben</em>: <input type="text" name="set_num"></input>
<!--
<div class="radio">
	<em>Ausgabe-Format w&auml;hlen</em>: 
<br/>
<input type="radio" value="isbn" name="makecsv" disabled>ISBN 1, ISBN 2, ...</input> 
<br/>
<input type="radio" value="barcode-isbn" name="makecsv" disabled>Barcode|ISBN, Barcode|ISBN, ...</input>
<br/>
<input type="radio" value="sysno-isbn" name="makecsv" checked="checked">SysNo|ISBN, SysNo|ISBN, ...</input>
</div>
-->
<input type="hidden" value="sysno-isbn" name="makecsv"></input>
<input type="submit" value="ISBN-Liste aus Aleph-Set erzeugen!" /></input>
</form>
</p>
	<h1>1b. ISBN-Liste erzeugen aus Eingabedatei: (<a href="http://wiki/index.php?title=BiblioGBVSubjectHeadings">Hilfe</a>)</h1>
<p>
<form action="sru.php" method="get">
<?php
$dir = "input"; 
$dh = opendir($dir); 
while (($file = readdir($dh)) !== false) {  
	if (!is_dir("$dir/$file")) { 
	    echo "<input type=\"radio\" name=\"filename\" value=\"".$file."\">";
			echo "$file&nbsp;\n";
			echo "[<A HREF=\"input/$file\">Datei ansehen</A>]<BR>\n";
	}
} 
closedir($dh);
?> 
<br/>
<input type="checkbox" name="aufsatz" value="Y">Eingabedatei: Aufs&auml;tze (SYS#Autor#Titel##)! <span style="background-color:red;color:white">funzt nur mit GBV-STW!</span></input>
<br/>
<input type="submit" value="ISBN-Liste aus Datei erzeugen!" /></input>
</form>
</p>
<?php




if (!empty($_GET['set_num'])) {
// Ein paar Security-Checks (Set_No. ist nicht verbunden mit library, d.h. man koennte auch Sets von anderen Bibs abgreifen, was zu Sys-No.-Inkonsistenzen fuehrt!)
if ($alles[0]['cat_check'] != 'RDG') { // steht im ersten Satz ein RDG? (soll als Check hinreichend ausreichen)
echo '<span style="background-color:red;font-weight:bold;padding:5px;margin-left:30%">FEHLER! Feld CAT != RDG. Falsches Set ausgewählt? </span>';
exit;
}

$check = count($alles);
if ($check != 100) { // sind Titel genau 100? (Hunderter_Sets)
echo '<span style="background-color:red;font-weight:bold;padding:5px;margin-left:50%">Check: '.count($alles).' Titel gefunden &gt;&gt;&gt;&gt;</span>';
} else { 
echo '<span style="background-color:#00ff40;font-weight:bold;padding:5px;margin-left:50%">Check: '.count($alles).' Titel gefunden &gt;&gt;&gt;&gt;</span>';
}}
?>

</div>


<div id="boxrechts">

															<h1>2. Daten abfragen (Katalog unten ausw&auml;hlen):</h1>


<form action="http://intern.coll.mpg.de/biblio/sru/search/sru-ajax-query.php" method="post">
<!--<form action="http://addaleph.vweb10-test.gwdg.de/gbv/sru-ajax-test.php" method="post">-->
<textarea name="nummern" cols="38" rows="30">
<?php
if (!empty($_GET['set_num'])) {
foreach ($alles as $item){

/* if (!empty($item['barcode'])) { */
/* 		echo $item['barcode']."|"; */
/* 	} */



/////////////////////////////////////////////////////////
// Pruefung: Barcode oder ohne Barcode? SysNo oder ohne SysNo?

if (empty($_GET['makecsv']) || $_GET['makecsv'] == "isbn") {
	if (!empty($item['mab540'])) {
		echo $item['mab540'];
		if (end($alles) == $item) // wenn letztes Element, kein Komma mehr
			{ echo "";} else { echo ", ";}
	}
} else if ($_GET['makecsv'] == "barcode-isbn") {
	echo $item['barcode']."|".$item['mab540'];
	if (end($alles) == $item) // wenn letztes Element, kein Komma mehr
    { echo "";} else { echo ", ";}
} else if ($_GET['makecsv'] == "sysno-isbn") {
	echo $item['sysno']."|".$item['mab540'];
	if (end($alles) == $item) // wenn letztes Element, kein Komma mehr
    { echo "";} else { echo ", ";}
} else { echo '';}

} // end foreach alles as item
} elseif (!empty($_GET['filename'])) {
$file ="input/".$_GET['filename'];
$fb = fopen($file, "r") or die("Couldn't open $file for writing!");
$forminput = fread($fb,filesize($file));
echo $forminput;
fclose($fb);
}
?>
</textarea>
<?php
											 //if (!empty($_GET['set_num'])) {
if (empty($_GET['makecsv']) && empty($_GET['aufsatz'])) {
	echo '<input type="hidden" value="sysno-isbn" name="csv"></input>'; // Default, z.B. bei Direkteingabe der ISBN per Copy&Paste
} else if (empty($_GET['makecsv']) && ($_GET['aufsatz']) == 'Y') {
	echo '<input type="hidden" value="aufsatz" name="csv"></input>'; // Aufsatz: Titel/Autor (keine ISBN!)
} else if ($_GET['makecsv'] == "isbn") {
	echo '<input type="hidden" value="isbn" name="csv"></input>';
} else if ($_GET['makecsv'] == "barcode-isbn") {
	echo '<input type="hidden" value="barcode-isbn" name="csv"></input>';
} else if ($_GET['makecsv'] == "sysno-isbn") {
	echo '<input type="hidden" value="sysno-isbn" name="csv"></input>';
} else { echo '<input type="hidden" value="sysno-isbn" name="csv"></input>';} // Default (redundant)
// set_num uebergeben
echo '<input type="hidden" value="'.$_GET['set_num'].'" name="set_num"></input>';
?>
<br/><br/>
<input type="radio" value="gbv-subj" name="srubase">GBV: Subject Headings</input>
<br/>
<input type="radio" value="gbv-toc" name="srubase" checked="checked">GBV: Table of Contents</input>
<br/>
	<input type="radio" value="gbv-stw" name="srubase">GBV: STW-Subjects (Standard-Thesaurus Wirtschaft)</input>
<br/>
	<input type="radio" value="gbv-swd" name="srubase">GBV: SWD-Schlagworte</input>
<br/>
	<input type="radio" value="gbv-zss-swd" name="srubase">GBV: SWD-Schlagworte <strong>(Zeitschriften/Eingabe erfordert ISSN!)</strong></input>
<br/>
<input type="radio" value="loc" name="srubase">Library of Congress: Subject Headings</input>
<br/>
	<input type="radio" value="gbv-sprachencode" name="srubase" checked="checked">GBV: Sprachencode (037b)</input>
<br/>
<!--<input type="checkbox" value="nohtml" name="output">keine HTML-Ausgabe</input>-->
<br/><br/><input type="submit" value="Daten abfragen!" /></input>
</form>
</div>
</div>
</body></html>
<?php 
libxml_clear_errors(); 
libxml_use_internal_errors( $oldSetting ); 

?>
