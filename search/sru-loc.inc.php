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
$x = "http://z3950.loc.gov:7090/voyager?version=1.1&operation=searchRetrieve&query=".trim($_GET['isbn'])."&maximumRecords=1&recordSchema=marcxml";
//$x = "http://z3950.loc.gov:7090/voyager?version=1.1&operation=searchRetrieve&query=9780262015455&maximumRecords=2&recordSchema=marcxml";
//$x="http://intern.coll.mpg.de/biblio/sru/subject-seq/voyager.xml";

	$neuDom = new DOMDocument;

  $neuDom->load($x);
	$xpath = new DOMXPath( $neuDom ); 


//$xpath->registerNamespace("zs","http://www.loc.gov/zing/srw");
$xpath->registerNamespace("m","http://www.loc.gov/MARC21/slim");


$record = $xpath->query( "//m:record" );
//debugging
//print_r($record);
$datafield009 = $xpath->query( "//m:datafield[@tag='650']");
// Check, etwas unbeholfen geschrieben, aber klappt wenigstens (sonst Fehler, wenn 009 fehlt, man muesste schoner das myget-Array auseinanderklamuesern:
$datafield009_check = myget( "//m:datafield[@tag='650']",$xpath); 
$locmarc = array();
 
foreach ( $record as $item ) {
	$newDom = new DOMDocument;
	$newDom->appendChild($newDom->importNode($item,true));
 
	$xpath = new DOMXPath( $newDom ); 

	$xpath->registerNamespace("m","http://www.loc.gov/MARC21/slim");

	$marc020 = myget("//m:datafield[@tag='020']/m:subfield[@code='a']",$xpath);
	$marc260c = myget("//m:datafield[@tag='260']/m:subfield[@code='c']",$xpath);
	$marc245a = myget("//m:datafield[@tag='245']/m:subfield[@code='a']",$xpath);
	$loc_sys = myget("//m:controlfield[@tag='001']",$xpath);

	$datafield009 = $xpath->query( "//m:datafield[@tag='650']");


$url_array =array();	
	if (!empty($datafield009_check)) { // hier der unbeholfene Check s.o.
	foreach ($datafield009 as $data) {
		$newDom = new DOMDocument;
		$newDom->appendChild($newDom->importNode($data,true));
		
		$xpath = new DOMXPath( $newDom ); 
		$xpath->registerNamespace("m","http://www.loc.gov/MARC21/slim");
 
		$marc650a = myget("m:subfield[@code='a']",$xpath);
		$marc650x = myget("m:subfield[@code='x']",$xpath);
		$marc650z = myget("m:subfield[@code='z']",$xpath);
		$marc650v = myget("m:subfield[@code='v']",$xpath);
		$url_array[] = array("subject_a" => $marc650a,
												 "subject_x" => $marc650x,
												 "subject_z" => $marc650z,
												 "subject_v" => $marc650v);
		
	}
	}  else {$url_array = ""; }	
	$locmarc[] = array( 'titel' => $marc245a, 'isbn' => $marc020, 'url' => $url_array, 'year' => $marc260c, 'sys' => $loc_sys );

}
						
//debugging	
//print_r($url_array);
//print_r($locmarc)."<BR>";	
//echo $pica['url']['Inhaltsverzeichnis'];			

// in Datei schreiben (Def.):
//$file = "data/aleph-seq.".date("Ymd-G_i").".txt"; 
//$file = "data/aleph-seq.".$_GET('set_num').".txt"; 
//$barcode = $_POST['data'];
//$alephseq;

if (empty($locmarc)) {
	echo "<em>kein Treffer im LoC Catalog</em>";
} else {
	$no_records = count($locmarc);
	echo "<br/>" .$no_records . " Titel gefunden:";

	$titelcounter = 1;

foreach ( $locmarc as $item ) {
	//	print "<br>";print_r($item); print "<br>";

	echo "<br />" . $titelcounter . " - ";

	if (!empty($item['url'])) {
		//	print_r($item['url']);
		$urlmatch = "good";
		echo '<span class="'.$urlmatch.'">Subject Headings gefunden!';
		echo ' </span>';

		echo '<div class="gbvmatch">';
		echo '<em>'.$item['titel'].'</em> ('.$item['year'].') ';
		echo '<br/>';

		$alephseq .= $_GET['sysno']." 078   L \$\$aLoC-SH\r\n"; // Abrufzeichen
		// ISBN (bei Import mit merge-Routine verwerfen, nur zur Nachnutzung interessant)
		// 1. einfache ISBN in einem Datensatz
		// AUSKOMMENTIERT (ISBN)
		/* if (!is_array($item['isbn'])) { */
		/* $alephseq .= $_GET['sysno']." 540a  L \$\$a".$item['isbn']."\r\n";  */
		/* } else { // 2. mehrere ISBN, durch mehrere Datensaetze */
		/* 	foreach ($item['isbn'] as $isbn) { */
		/* 		if (!is_array($isbn)) { */
		/* 			$alephseq .= $_GET['sysno']." 540a  L \$\$a".$isbn."\r\n";  */
		/* 		} else { // falls mehrere ISBN in einem Datensatz */
		/* 			foreach ($isbn as $i) { */
		/* 			$alephseq .= $_GET['sysno']." 540a  L \$\$a".$i."\r\n";  */
		/* 			} */
		/* 		}		  */
		/* 	} */
		/* } */

				foreach ($item['url'] as $url) {
					global $alephseq;	
					echo '<div class="gefunden">';
					if (!is_array($url['subject_a'])) {
						echo 'MAB 740s_a: <strong>'.$url['subject_a'].'</strong>';
						//$alephseq .= "000029109 740    L \$\$a".$url['subject_a'];
						$alephseq .= $_GET['sysno']." 740s  L \$\$a".$url['subject_a'];
						if (!empty($url['subject_x'])) { 
							if (!is_array($url['subject_x'])) {
								echo '<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;_x: <strong>' .$url['subject_x'].'</strong>';
								$alephseq .= "\$\$x".$url['subject_x'];
							} else { // if array, falls mehrere UF x
								foreach ($url['subject_x'] as $x) {
									echo '<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;_x: <strong>' .$x.'</strong>';
									$alephseq .= "\$\$x".$x;
								}
							}
						}
						if (!empty($url['subject_z'])) { echo '<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;_z: <strong>' .$url['subject_z'].'</strong>';
							$alephseq .= "\$\$z".$url['subject_z'];}
						if (!empty($url['subject_v'])) { echo '<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;_v: <strong>' .$url['subject_v'].'</strong>';
							$alephseq .= "\$\$v".$url['subject_v'];}
						$alephseq .= "\r\n";
					} else { // if array: falls mehrere Unterfelder a in einem 740er-Feld
						echo 'MAB 740s_a: <strong>'.$url['subject_a'][0].'</strong>'; // erstes UF ausgeben (ist immer 'a')
						$alephseq .= $_GET['sysno']." 740s  L \$\$a".$url['subject_a'][0]; // erstes UF in Datei schreiben
						array_shift($url['subject_a']); // erstes UF rausschmeissen
						foreach ($url['subject_a'] as $mysubject) { 
							echo ' / <strong>'.$mysubject.'</strong>';
							$alephseq .= "\$\$a".$mysubject;
						} 
						if (!empty($url['subject_x'])) { echo '<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;_x: <strong>' .$url['subject_x'].'</strong>';
							$alephseq .= "\$\$x".$url['subject_x'];}
						if (!empty($url['subject_z'])) { echo '<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;_z: <strong>' .$url['subject_z'].'</strong>';
							$alephseq .= "\$\$z".$url['subject_z'];}
						if (!empty($url['subject_v'])) { echo '<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;_v: <strong>' .$url['subject_v'].'</strong>';
							$alephseq .= "\$\$v".$url['subject_v'];}
							$alephseq .= "\r\n";
					}
					echo '</div>';

				}
		echo '[<a target="top" href="http://catalog.loc.gov='.$item["sys"].'">Link zum LoC-Titelsatz</a>]';
		echo '</div>';
	} else { echo '<span style="color:red"> keine Subject Headings gefunden!</span>';}

	$titelcounter++;

}

}

// in Datei mit Set-Num. schreiben:
$file = "data/loc/set-num-".$_GET['set_num'].".txt";
// in Datei mit fortlaufend schreiben:
//$file = "data/gvk-subjects-kumulierend.txt";
$fp = fopen($file, "a") or die("Couldn't open $file for writing!");
fwrite($fp,$alephseq) or die(" es wurde nichts in Datei geschrieben!"); 

fclose($fp); 

?>