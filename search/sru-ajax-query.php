<?php
// Version/Time-stamp: "2012-03-12 17:54:35 zimmel"
// Daniel Zimmel, Martin Pollet
// Dateiname: sru-ajax-query.php 
// Abfragen via SRU-Schnittstelle, asynchron via AJAX/Prototype

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

if ($srubase == "gbv-subj") {
echo "<h1>Abfrage GBV: Subject Headings</h1> ";
} else if ($srubase == "gbv-toc") {
echo "<h1>Abfrage GBV: Table of Contents</h1> ";
} else if ($srubase == "loc") {
echo "<h1>Abfrage LoC: Subject Headings</h1> ";
}
echo '<h2 style="background-color: yellow; padding: 10px;"><a href="sru.php">neue Suche</a></h2>';
//echo "<h2>Anmerkung: bei mehreren Titels&auml;tzen wird nur der erste ausgewertet</h2>";
//print_r($nummern);
//echo "<br/><br/>";

echo "<html>\n<head>\n";
echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'."\n";
echo '<script type="text/javascript" src="prototype.js?"></script>'."\n";
echo '<link rel="stylesheet" media="all" type="text/css" href="sru.css" />'."\n";
echo '</head>'."\n";


if ($srubase == "gbv-subj") {
?>
<!-- Ajax-Abfrage Aleph-X, s.u. script + notation-span -->
<body onload="for (var x = 1; x <= max; x++){ 
new Ajax.Updater(
{success: 'ResponseContainer-'+x,
 failure: 'Failure'},
 'sru-gbv-subj.inc.php',
 {method:'get', parameters: { isbn: isbns[x], sysno: sysnos[x], set_num: set_num}});
} ">
<?php
		} elseif ($srubase == "gbv-toc") {
?>
<!-- Ajax-Abfrage Aleph-X, s.u. script + notation-span -->
<body onload="for (var x = 1; x <= max; x++){ 
new Ajax.Updater(
{success: 'ResponseContainer-'+x,
 failure: 'Failure'},
 'sru-gbv-toc.inc.php',
 {method:'get', parameters: { isbn: isbns[x], sysno: sysnos[x], set_num: set_num}});
} ">
<?php
		} elseif ($srubase == "gbv-stw" && $csvinput != "aufsatz") {
?>
<!-- Ajax-Abfrage Aleph-X, s.u. script + notation-span -->
<body onload="for (var x = 1; x <= max; x++){ 
new Ajax.Updater(
{success: 'ResponseContainer-'+x,
 failure: 'Failure'},
 'sru-gbv-stw.inc.php',
 {method:'get', parameters: { isbn: isbns[x], sysno: sysnos[x], set_num: set_num}});
} ">
<?php
		} elseif ($srubase == "gbv-stw" && $csvinput == "aufsatz") {
?>
<!-- Ajax-Abfrage Aleph-X, s.u. script + notation-span -->
<body onload="for (var x = 1; x <= max; x++){ 
new Ajax.Updater(
{success: 'ResponseContainer-'+x,
 failure: 'Failure'},
 'sru-gbv-stw.inc.php',
 {method:'get', parameters: { autor: autor[x], titel: titel[x], sysno: sysnos[x], set_num: set_num}});
} ">
<script>
autor = new Array();
titel = new Array();
</script>
<?php
		} elseif ($srubase == "gbv-swd") {
?>
<!-- Ajax-Abfrage Aleph-X, s.u. script + notation-span -->
<body onload="for (var x = 1; x <= max; x++){ 
new Ajax.Updater(
{success: 'ResponseContainer-'+x,
 failure: 'Failure'},
 'sru-gbv-swd.inc.php',
 {method:'get', parameters: { isbn: isbns[x], sysno: sysnos[x], set_num: set_num}});
} ">
<?php
		} elseif ($srubase == "gbv-zss-swd") {
?>
<!-- Ajax-Abfrage Aleph-X, s.u. script + notation-span -->
<body onload="for (var x = 1; x <= max; x++){ 
new Ajax.Updater(
{success: 'ResponseContainer-'+x,
 failure: 'Failure'},
 'sru-gbv-zss-swd.inc.php',
 {method:'get', parameters: { isbn: isbns[x], sysno: sysnos[x], set_num: set_num}});
} ">
<?php
		} elseif ($srubase == "gbv-sprachencode") {
?>
<!-- Ajax-Abfrage Aleph-X, s.u. script + notation-span -->
<body onload="for (var x = 1; x <= max; x++){ 
new Ajax.Updater(
{success: 'ResponseContainer-'+x,
 failure: 'Failure'},
 'sru-gbv-sprachencode.inc.php',
 {method:'get', parameters: { isbn: isbns[x], sysno: sysnos[x], set_num: set_num}});
} ">
<?php
		} elseif ($srubase == "loc") {
?>
<!-- Ajax-Abfrage Aleph-X, s.u. script + notation-span -->
<body onload="for (var x = 1; x <= max; x++){ 
new Ajax.Updater(
{success: 'ResponseContainer-'+x,
 failure: 'Failure'},
 'sru-loc.inc.php',
 {method:'get', parameters: { isbn: isbns[x], sysno: sysnos[x], set_num: set_num}});
} ">
<?php
					}



?>

<script>
isbns = new Array();
sysnos = new Array();
var set_num;
var max;

// globales Ajax-Element (Testen)
//Ajax.Responders.register({
		//  onCreate: function(){
    //alert('a request has been initialized!');
		//}, 
//onComplete: function(){
//			alert('a request completed');
//  }
//});

</script>
<!-- Ende Ajax -->

<?php

$count = 1;

foreach ($nummern as $key => $item){

	echo "<div>";

		if ($csvinput == "isbn") {
			echo "ISBN/ISSN = <strong>".$key."</strong>: ".$item;
		} else if ($csvinput == "barcode-isbn") {
			echo '<strong>Barcode: '.$key.'</strong>: <span class="visible">'.$item;
		} else if ($csvinput == "sysno-isbn") {
			if ($output != "nohtml") {
				echo '<strong>Aleph-SYS: '.$key.'</strong>: <span class="visible">'.$item;
			}
			$sysno=trim($key);
		} else { echo "aufsatz";}

		if ($csvinput != "aufsatz") {
			// zur Sicherheit ISBN-Laenge pruefen (Fehlerreduzierung) / muss auch ISSN erlauben
			$item_trim = trim($item);
			if (strlen($item_trim) >= 8 && strlen($item_trim) <= 13) { 
				
				$isbn=$item_trim;
	
			} else { $isbn=""; }
		} else {

			$aufsatzdaten = explode ('#',$item);//print_r($aufsatzdaten);
			$sysno = $aufsatzdaten[0];
			$autor = preg_replace("/,\s.+/","",$aufsatzdaten[1]);
			$titel = preg_replace("/[\"\'\?:;&\$]/","",$aufsatzdaten[2]);
		}

	if ($csvinput != "aufsatz") {
	?>
<!-- Ajax-Abfrage Aleph-X, s.o. -->
<script> 
    isbns[<?php print $count; ?>]="<?php print $isbn; ?>";
		sysnos[<?php print $count; ?>]="<?php print $sysno; ?>";
		//set_num="<?php print $set_num; ?>";
		max=<?php print $count; ?>;
		//max=2;
</script>
<?php
		} else if ($csvinput == "aufsatz") {
	?>
<!-- Ajax-Abfrage Aleph-X, s.o. -->
<script> 

    autor[<?php print $count; ?>]="<?php print $autor; ?>";
    titel[<?php print $count; ?>]="<?php print $titel; ?>";
		sysnos[<?php print $count; ?>]="<?php print $sysno; ?>";
		//set_num="<?php print $set_num; ?>";
		max=<?php print $count; ?>;
		//max=2;
</script>
<?php
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
	if ($output == "nohtml") { echo "Datei wird geschrieben...";}

echo '<h2 style="background-color: yellow; padding: 10px;"><a href="sru.php">neue Suche</a></h2>';

echo '</body>'."\n".'</html>';
 
libxml_clear_errors(); 
libxml_use_internal_errors( $oldSetting ); 

?>
