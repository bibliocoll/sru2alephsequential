<?php

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

// fuers Testen bitte auskommentieren
//$x = "http://gso.gbv.de/sru/DB=2.1/?version=1.1&operation=searchRetrieve&query=pica.isb%3D%22".trim($_GET['isbn'])."&recordSchema=pica";
//$x = "http://sru.gbv.de/gvk?version=1.1&operation=searchRetrieve&query=pica.isb%3D0006383866&maximumRecords=10&recordSchema=picaxml";
$x = "http://sru.gbv.de/gvk?version=1.1&operation=searchRetrieve&query=pica.isb%3D%22".trim($_GET['isbn'])."&maximumRecords=10&recordSchema=picaxml";
//$x = "http://gso.gbv.de/sru/DB=2.1/?version=1.1&operation=searchRetrieve&query=pica.tit%3D%22Cambridge+handbook+of+personality+psychology&recordSchema=pica";
	$neuDom = new DOMDocument;

  $neuDom->load($x);
	$xpath = new DOMXPath( $neuDom ); 

$xpath->registerNamespace("m","info:srw/schema/5/picaXML-v1.0");

$record = $xpath->query( "//m:record" );
$pica = array();
 
foreach ( $record as $item ) {
	$newDom = new DOMDocument;
	$newDom->appendChild($newDom->importNode($item,true));
 
	$xpath = new DOMXPath( $newDom ); 

	$xpath->registerNamespace("m","info:srw/schema/5/picaXML-v1.0");

	$pica004 = myget("//m:datafield[@tag='004A']/m:subfield[@code='A' or @code='0']",$xpath);
	$pica011 = myget("//m:datafield[@tag='011@']/m:subfield[@code='a']",$xpath);

	$pica010 = myget("//m:datafield[@tag='010@']/m:subfield[@code='a']",$xpath);

	$pica[] = array( 'isbn' => $pica004, 'code' => $pica010, 'year' => $pica011 );

}
							

//print_r($pica)."<BR>";	
//echo $pica['url']['Inhaltsverzeichnis'];			

if (empty($pica)) {
	echo "<em>kein Treffer im GBV</em>";
} else {
	$no_records = count($pica);
	echo "<br/>" .$no_records . " Titel gefunden:";

	$titelcounter = 1;

foreach ( $pica as $item ) {
	//	print "<br>";print_r($item); print "<br>";

	if (!empty($item['code'])) {

		echo "(".$item['year'].") ";
		
		global $alephseq;	

		if (!is_array($item['code'])) {
			echo '<span style="background-color:red;color:white">'.$item['code'].'</span>';
			$alephseq .= $_GET['sysno']." 037b  L \$\$a".$item['code']."\r\n";
		} else { // if array:
			$alephseq .= $_GET['sysno']." 037b  L \$\$a".$item['code'][0]; // 1. Feld
			echo '<span style="background-color:red;color:white">'.$item['code'][0];
			echo ' </span>&nbsp;';
			array_shift($item['code']); // 1. Feld loeschen
			foreach ($item['code'] as $feld) { // f. alle weiteren Felder: hinten dranhaengen
				  if (!empty($feld)){
					//$alephseq .= $_GET['sysno']." 078   L \$\$aGBV-ToC\r\n"; // Abrufzeichen	
					$alephseq .= "\$\$a".$feld;
					echo '<span style="background-color:red;color:white">'.$feld;
					echo ' </span>';
					}
			}
			$alephseq .= "\r\n"; // nach Abschluss eine neue Zeile machen
		}
	} else { echo '<span style="color:red"> kein Sprachencode gefunden!</span>';}

	$titelcounter++;

}

}
$file ="data/gbv/sprachencode/gbv-sprachencode-alle.txt";
$fp = fopen($file, "a") or die("Couldn't open $file for writing!");
fwrite($fp,$alephseq) or die(" es wurde nichts in Datei geschrieben!"); 
fclose($fp); 

?>