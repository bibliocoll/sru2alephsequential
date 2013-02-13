<?php
// UPDATE SEPTEMBER 2011: neuer GBV-Dienst (sru.gbv.de) //
// Ajax-Abfrage

/* In Pica+ vorgesehene Felder f. Schlagwortketten: */

/*         Anm. 041A00 bis 041A05 enthalten die 6 Glieder der SWK, 041A10 bis 041A15 die */
/*              der 2. SWK, usw. */
     
/* 					In GBV ueber "occurrence" einsehbar, Bsp: */
/*   <datafield tag="041A"> */
/*     <subfield code="S">s</subfield> */
/*     <subfield code="9">106286757</subfield> */
/*     <subfield code="a">Industriepolitik</subfield> */
/*     <subfield code="S">s</subfield> */
/*     <subfield code="0">40268603</subfield> */
/*   </datafield> */
/*   <datafield tag="041A" occurrence="01"> */
/*     <subfield code="S">s</subfield> */
/*     <subfield code="9">104418532</subfield> */
/*     <subfield code="a">Industrieökonomie</subfield> */
/*     <subfield code="S">s</subfield> */
/*     <subfield code="0">41333111</subfield> */
/*   </datafield> */
/*   <datafield tag="041A" occurrence="10"> */
/*     <subfield code="S">f</subfield> */
/*     <subfield code="a">Fallstudiensammlung</subfield> */
/*   </datafield> */
/*   <datafield tag="041A" occurrence="11"> */
/*     <subfield code="S">s</subfield> */
/*     <subfield code="9">106286757</subfield> */
/*     <subfield code="a">Industriepolitik</subfield> */
/*     <subfield code="S">s</subfield> */
/*     <subfield code="0">40268603</subfield> */
/*   </datafield> */

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
$x = "http://sru.gbv.de/gvk?version=1.1&operation=searchRetrieve&query=pica.isb%3D%22".trim($_GET['isbn'])."&maximumRecords=10&recordSchema=picaxml";

	$neuDom = new DOMDocument;

  $neuDom->load($x);
	$xpath = new DOMXPath( $neuDom ); 


$xpath->registerNamespace("m","info:srw/schema/5/picaXML-v1.0");

$record = $xpath->query( "//m:record" );
$datafield009 = $xpath->query( "//m:datafield[@tag='041A' or @tag='041A']");
// Check, etwas unbeholfen geschrieben, aber klappt wenigstens (sonst Fehler, wenn 009 fehlt, man muesste schoner das myget-Array auseinanderklamuesern:
$datafield009_check = myget( "//m:datafield[@tag='041A' or @tag='041A']",$xpath); 
$pica = array();
 
foreach ( $record as $item ) {
	$newDom = new DOMDocument;
	$newDom->appendChild($newDom->importNode($item,true));
 
	$xpath = new DOMXPath( $newDom ); 

	$xpath->registerNamespace("m","info:srw/schema/5/picaXML-v1.0");

	$pica004 = myget("//m:datafield[@tag='004A']/m:subfield[@code='A' or @code='0']",$xpath);
	$pica011 = myget("//m:datafield[@tag='011@']/m:subfield[@code='a']",$xpath);
	$pica021A = myget("//m:datafield[@tag='021A']/m:subfield[@code='a']",$xpath);
	$pica_ppn = myget("//m:datafield[@tag='003@']/m:subfield[@code='0']",$xpath);

	$datafield009 = $xpath->query( "//m:datafield[@tag='041A' or @tag='041A']");


$swd_array =array();	
	if (!empty($datafield009_check)) { // hier der unbeholfene Check s.o.
	foreach ($datafield009 as $data) {
		$newDom = new DOMDocument;
		$newDom->appendChild($newDom->importNode($data,true));
		
		$xpath = new DOMXPath( $newDom ); 
 		$xpath->registerNamespace("m","info:srw/schema/5/picaXML-v1.0");
		$pica041Aa = myget("m:subfield[@code='a']",$xpath);
		$pica041AS = myget("m:subfield[@code='S'][1]",$xpath);
		$pica041A0 = myget("m:subfield[@code='0']",$xpath);
		// falls ID gefunden, dann ein Bindestrich nach 7 Zeichen f. korrekte Verknuepfung, z.B.:
		if (!empty($pica041A0)) {
			$pica041A0 = substr($pica041A0,0,7)."-".substr($pica041A0,7);
		}
		$occurrence = $data->getAttribute("occurrence"); // zur Unterscheidung von Schlagwortketten, s. oben Bsp. in pica+ 
		$swd_array[] = array("schlagwort" => $pica041Aa,
												 "unterfeld" => $pica041AS,
												 "occurrence" => $occurrence,
												 "swd_id" => $pica041A0);
		
	}
	}  else {$swd_array = ""; }	
	$pica[] = array( 'titel' => $pica021A, 'isbn' => $pica004, 'swd' => $swd_array, 'year' => $pica011, 'ppn' => $pica_ppn );

}
							
//print_r($swd_array);
//print_r($pica)."<BR>";	
//echo $pica['swd']['Inhaltsverzeichnis'];			

// in Datei schreiben (Def.):
//$file = "data/aleph-seq.".date("Ymd-G_i").".txt"; 
//$file = "data/aleph-seq.".$_GET('set_num').".txt"; 
//$barcode = $_POST['data'];
//$alephseq;

if (empty($pica)) {
	echo "<em>kein Treffer im GBV</em>";
} else {
	$no_records = count($pica);
	echo "<br/>" .$no_records . " Titel gefunden:";

	$titelcounter = 1;

foreach ( $pica as $item ) {
	//	print "<br>";print_r($item); print "<br>";

	echo "<br />" . $titelcounter . " - ";

	if (!empty($item['swd'])) {
		//	print_r($item['swd']);
		$swdmatch = "good";
		echo '<span class="'.$swdmatch.'">SWD-Schlagworte gefunden!';
		echo ' </span>';

		echo '<div class="gbvmatch">';
		echo '<em>'.$item['titel'].'</em> ('.$item['year'].') ';
		echo '<br/>';

		$alephseq .= $_GET['sysno']." 078   L \$\$aGBV-SWD\r\n"; // Abrufzeichen
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

				foreach ($item['swd'] as $swd) {
					global $alephseq;	
					global $swdidnummern;	
					// Kettenglieder konditional von Wert von "occurrence" (pica+):
					if ($swd['occurrence'] >= "01" && $swd['occurrence'] < "10" || empty($swd['occurrence'])) {
						$kettenglied = "902";
					} elseif ($swd['occurrence'] >= "10" && $swd['occurrence'] < "20") {
						$kettenglied = "907";
					} elseif ($swd['occurrence'] >= "20" && $swd['occurrence'] < "30") {
						$kettenglied = "912";
					} elseif ($swd['occurrence'] >= "30" && $swd['occurrence'] < "40") {
						$kettenglied = "917";
					} elseif ($swd['occurrence'] >= "40" && $swd['occurrence'] < "50") {
						$kettenglied = "922";
					} else {break;}
					echo '<div class="gefunden">';
					if (!is_array($swd['schlagwort']) && !empty($swd['unterfeld'])) {
						echo 'MAB '.$kettenglied.'_'.$swd['unterfeld'].': <strong>'.$swd['schlagwort'].'</strong>';
						//	print($swd['occurrence']);
						//$alephseq .= "000029109 902    L \$\$a".$swd['schlagwort'];
						$alephseq .= $_GET['sysno']." ".$kettenglied."   L \$\$".$swd['unterfeld']."".$swd['schlagwort'];
						if (!empty($swd['swd_id'])) { echo '<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;_9: <strong>' .$swd['swd_id'].'</strong>';
							$alephseq .= "\$\$9".$swd['swd_id'];
							$swdidnummern .= $swd['swd_id'];
					}
						$alephseq .= "\r\n";
						$swdidnummern .= "\r\n";
					} elseif (is_array($swd['schlagwort']) && !empty($swd['unterfeld'])) { // if array: falls mehrere Unterfelder a in einem 902er-Feld
						echo 'MAB '.$kettenglied.'_'.$swd['unterfeld'].': <strong>'.$swd['schlagwort'][0].'</strong>'; // erstes UF ausgeben (ist immer 'a')
						$alephseq .= $_GET['sysno']." ".$kettenglied."   L \$\$".$swd['unterfeld']."".$swd['schlagwort'][0]; // erstes UF in Datei schreiben
						array_shift($swd['schlagwort']); // erstes UF rausschmeissen
						foreach ($swd['schlagwort'] as $mysubject) { 
							echo ' / <strong>'.$mysubject.'</strong>';
							$alephseq .= "\$\$".$swd['unterfeld']."".$mysubject;
						} 
						if (!empty($swd['swd_id'])) { echo '<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;_9: <strong>' .$swd['swd_id'].'</strong>';
							$alephseq .= "\$\$9".$swd['swd_id'];
							$swdidnummern .= $swd['swd_id'];
						}
							$alephseq .= "\r\n";
							$swdidnummern .= "\r\n";
					}
					echo '</div>';

				}
		echo '[<a target="top" href="http://gso.gbv.de/DB=2.1/PPNSET?PPN='.$item["ppn"].'">Link zum GBV-Titelsatz</a>]';
		echo '</div>';
	} else { echo '<span style="color:red"> keine Subject Headings gefunden!</span>';}

	$titelcounter++;

}

}

// in Datei mit Set-Num. schreiben:
$file = "data/gbv/swd/gbv-swd.raw.txt";
// in Datei mit fortlaufend schreiben:
//$file = "data/gvk-subjects-kumulierend.txt";
$fp = fopen($file, "a") or die("Couldn't open $file for writing!");
fwrite($fp,$alephseq) or die(" es wurde nichts in Datei geschrieben!"); 

fclose($fp); 

// zum Normdatenabgleich neues File (SWD-Nummern speichern):
$swdfile = "data/gbv/swd/swd-id-nummern.txt";
$swdfilehandler = fopen($swdfile,"a") or die("Konnte $swdfile nicht oeffnen!");
fwrite($swdfilehandler,$swdidnummern) or die("Konnte nix schreiben!");
fclose($swdfilehandler);

?>