<?php
// Version/Time-stamp: "2014-01-15 17:18:14 zimmel"
// Daniel Zimmel, Martin Pollet
// Dateiname: sru-ajax-query.php 
// Abfragen via SRU-Schnittstelle, asynchron via AJAX/jQuery
// ! Update 2014: neu implementiert in jQuery (statt Prototype)
header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
$oldSetting = libxml_use_internal_errors( true ); 
libxml_clear_errors(); 

$doc = new DOMDocument(); 

$doc->strictErrorChecking = false;
$doc->validateOnParse = false;

// Form einlesen:
$forminput = ereg_replace("[\r\n-]","",$_POST["nummern"]); // Zeilenumbrueche loeschen, Bindestriche loeschen 
$csvinput = $_POST["csv"];
$set_num = $_POST["set_num"]; 
$srubase = $_POST["srubase"];
$output = $_POST["output"];

if ($csvinput == "isbn") {
// Start Variante 1: Nur ISBN einlesen
// nur test:
//$nummern = array("0-521-86218-3", "9781845429362", "9781845420895","9781847204028", "978-0-19-927973-9","0-415-77126-9","9783540793489");
// zur Benutzung folgende Zeile einkommentieren:
$nummern = explode(",",$forminput); // Trenner: Komma
// Ende Variante 1: Nur ISBN einlesen

//echo "debugging:".$forminput;

} else if ($csvinput == "barcode-isbn" || $csvinput == "sysno-isbn") {

// Start Variante 2: Barcode oder Sysno als Key einlesen
//$value = 'barcode1|0-521-86218-3,barcode2|9781845429362,barcode3|9781845420895,barcode4|9781847204028';
$value=$forminput;
	$temp = explode (',',$value);
	foreach ($temp as $pair) {
		list ($k,$v) = explode ('|',$pair);
		$nummern[$k] = $v;
	}
//print_r($nummern);
// Ende Variante 2: Barcode als Key einlesen

} else if ($csvinput == "aufsatz") {
$value=$forminput;
	$nummern = explode ('##',$value);
}

echo "<html>\n<head>\n";
echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'."\n";
echo '<script type="text/javascript" src="jquery-2.0.3.min.js"></script>'."\n";
echo '<link rel="stylesheet" media="all" type="text/css" href="sru.css" />'."\n";
echo '</head>'."\n";

echo '<body>'."\n";

if ($srubase == "gbv-subj") {
echo "<h1>Abfrage GBV: Subject Headings</h1> ";
} else if ($srubase == "gbv-toc") {
echo "<h1>Abfrage GBV: Table of Contents</h1> ";
} else if ($srubase == "loc") {
echo "<h1>Abfrage LoC: Subject Headings</h1> ";
}
echo '<h2 style="background-color: yellow; padding: 10px;"><a href="sru.php">neue Suche</a></h2>';

$count = 1;

foreach ($nummern as $key => $item){

	echo '<div class="myrow">';

		if ($csvinput == "isbn") {
			echo 'ISBN/ISSN = <strong>'.$key.'</strong>: '.$item;
		} else if ($csvinput == "barcode-isbn") {
			echo '<strong>Barcode: '.$key.'</strong>: <span class="visible">'.$item;
		} else if ($csvinput == "sysno-isbn") {
			if ($output != "nohtml") {
					echo '<strong>Aleph-SYS: <span class="mysysno">'.trim($key).'</span></strong>: <span class="visible"><span class="myisbn">'.$item.'</span>';
			} else {
					echo '<div style="display:none"><strong>Aleph-SYS: <span class="mysysno">'.trim($key).'</span></strong>: <span class="visible"><span class="myisbn">'.$item.'</span></div>';
			} 
			$sysno=trim($key);
		} else { echo "aufsatz";}

		if ($csvinput != "aufsatz") {
			// zur Sicherheit ISBN-Laenge pruefen (Fehlerreduzierung) / muss auch ISSN erlauben
			$item_trim = trim($item);
			if (strlen($item_trim) >= 8 && strlen($item_trim) <= 13) { 
				
				$isbn=$item_trim;
	
			} else { $isbn=""; }
		} else { // if Aufsatzdaten:

			$aufsatzdaten = explode ('#',$item);//print_r($aufsatzdaten);
			$sysno = $aufsatzdaten[0];
			$author = preg_replace("/,\s.+/","",$aufsatzdaten[1]);
			$title = preg_replace("/[\"\'\?:;&\$]/","",$aufsatzdaten[2]);

			// setup for Ajax only, invisible:
			echo '<div style="display:none"><span class="myauthor">'.$author.'</span><span class="mytitle">'.$title.'</span></div>';
		}

		if ($output != "nohtml") {
?>
<span id="ResponseContainer-<?php print $count; ?>" class="Ajax"><img src="ajax-loader-3.gif"/></span>
<!-- Ende Ajax -->
<?php
		echo "</span>"; // end-span ISBN-Check
		echo "</div><hr/>";
		}
		$count++;
} // end foreach
//echo $set_num;
	if ($output == "nohtml") { 
echo '<span id="ajaxHinweis">Datei wird geschrieben...</span>';
}

echo '<h2 style="background-color: yellow; padding: 10px;"><a href="sru.php">neue Suche</a></h2>';

?>

<!-- Ajax-Abfrage Aleph-X -->
<script type="text/javascript">

		$(document).ready(function() {

				// not very secure, but we know what we do (internal only!)
				queryURL = 'sru-<?php print $srubase;?>.inc.php';
						$(".myrow").each(function() { 
										var myisbn = $(this).find(".myisbn:first").text(); 
										var mysysno = $(this).find(".mysysno:first").text();
										var mysetnum = $(this).find(".mysetnum:first").text();
										// Aufsatzdaten only, else leave out (thanks jQuery!)
										var myauthor = $(this).find(".myauthor:first").text();
										var mytitle = $(this).find(".mytitle:first").text();
										var currentPos = $(this);
										$.ajax({
											url: queryURL,
											type: "get",
										  dataType: "html",
										  data: {"isbn" : myisbn, "sysno": mysysno, "author": myauthor, "title": mytitle, "set_num": "dummy"},
										  success: function(returnData) {
													$(currentPos).find(".Ajax").append(returnData);
													$(currentPos).find(".Ajax img").remove();
												}, 
										  error: function(e){ alert(e);
												}
											});
							});
			});
		
$(document).ajaxStop(function() {$('#ajaxHinweis').text("OK, alles erledigt");$('#ajaxHinweis').attr("class", "ajaxStop");});
				
</script>

<?php

echo '</body>'."\n".'</html>';
 
libxml_clear_errors(); 
libxml_use_internal_errors( $oldSetting ); 

?>
