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
// Bsp. f. Aufsaetze/Preprints: http://sru.gbv.de/gvk?version=1.1&operation=searchRetrieve&query=pica.per=gizatulina%20and%20pica.tit%3DInformational%20smallness%20and%20the%20scope%20for%20limiting%20information%20rents&maximumRecords=10&recordSchema=picaxml
$xtitel = urlencode($_GET['title']);
$xautor = urlencode($_GET['author']);

if ($_GET['autor'] == TRUE) {
	//	$x = "http://sru.gbv.de/gvk?version=1.1&operation=searchRetrieve&query=pica.per=gizatulina%20and%20pica.tit%3DInformational%20smallness%20and%20the%20scope%20for%20limiting%20information%20rents&maximumRecords=10&recordSchema=picaxml";
		$x = "http://sru.gbv.de/gvk?version=1.1&operation=searchRetrieve&query=pica.per=".$xautor."%20and%20pica.tit%3D".$xtitel."&maximumRecords=10&recordSchema=picaxml";
} else {
$x = "http://sru.gbv.de/gvk?version=1.1&operation=searchRetrieve&query=pica.isb%3D%22".trim($_GET['isbn'])."&maximumRecords=10&recordSchema=picaxml";
}

	$neuDom = new DOMDocument;

  $neuDom->load($x);
	$xpath = new DOMXPath( $neuDom ); 

$xpath->registerNamespace("m","info:srw/schema/5/picaXML-v1.0");

$record = $xpath->query( "//m:record" );
$datafield009 = $xpath->query( "//m:datafield[@tag='045D']");
// Check, etwas unbeholfen geschrieben, aber klappt wenigstens (sonst Fehler, wenn 009 fehlt, man muesste schoner das myget-Array auseinanderklamuesern:
$datafield009_check = myget( "//m:datafield[@tag='045D']",$xpath); 
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

	$datafield009 = $xpath->query( "//m:datafield[@tag='045D']");


$stw_array =array();	
	if (!empty($datafield009_check)) { // check 
	foreach ($datafield009 as $data) {
		$newDom = new DOMDocument;
		$newDom->appendChild($newDom->importNode($data,true));
		
		$xpath = new DOMXPath( $newDom );
 
		$xpath->registerNamespace("m","info:srw/schema/5/picaXML-v1.0");

		$pica045Da = myget("m:subfield[@code='a']",$xpath);
		$pica045D0 = myget("m:subfield[@code='0']",$xpath);
		// falls ID gefunden, dann ein Bindestrich nach 5 Zeichen f. korrekte Linked-Data-Verlinkung, z.B.:
		// http://zbw.eu/stw/versions/latest/descriptor/19734-2/about
		/* Update 2012-06: nicht mehr notwendig, Daten kommen jetzt korrekt mit Bindestrich */
/* 		if (!empty($pica045D0)) { */
/* 			$pica045D0 = substr($pica045D0,0,5)."-".substr($pica045D0,5); */
/* 		} */
		$stw_array[] = array("pica045Da" => $pica045Da, // STW-Schlagwort
												 "pica045D0" => $pica045D0); // STW-ID
		
	}
	}  else {$stw_array = ""; }	

	// stw_array wird mit in pica-array geschrieben: 
	$pica[] = array( 'titel' => $pica021A, 'isbn' => $pica004, 'stw' => $stw_array, 'ppn' => $pica_ppn );

}
							
// debugging:
//print_r($pica)."<br/>";	
//echo $pica['stw']."<br/>";			

if (empty($pica)) {
	echo "<em>kein Treffer im GBV</em>";
} else {
	$no_records = count($pica);
	echo "<br/>" .$no_records . " Titel gefunden:";

	$titelcounter = 1;

foreach ( $pica as $item ) {
	//	print "<br>";print_r($item); print "<br>";

	if (!empty($item['stw'])) {
		$urlmatch = "good";
		echo '<span class="'.$urlmatch.'">STW gefunden!';
		echo ' </span>';

		echo '<div class="gbvmatch">';
		echo '<em>'.$item['titel'].'</em> ('.$item['stw'].') ';
		echo '<br/>';

		$alephseq .= $_GET['sysno']." 078   L \$\$aGBV-STW\r\n"; // Abrufzeichen


				foreach ($item['stw'] as $stw) {
					global $alephseq;	
					echo '<div class="gefunden">';
					if (!is_array($stw['pica045Da'])) {
						if (!empty($stw['pica045Da'])) {
						echo 'MAB STW: <strong>'.$stw['pica045Da'].'</strong>';
						//$alephseq .= "000029109 740    L \$\$a".$stw['subject_a'];
						$alephseq .= $_GET['sysno']." 711   L \$\$a".$stw['pica045Da'];
						}
						if (!empty($stw['pica045D0'])) { 
								echo '<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;_9: <strong>' .$stw['pica045D0'].'</strong>';
								$alephseq .= "\$\$9".$stw['pica045D0']."\$\$xSTW";}
						$alephseq .= "\r\n";
					
					} else { // if array: falls mehrere Unterfelder a in einem STW-Feld
						echo 'MAB STWs_a: <strong>'.$stw['pica045Da'][0].'</strong>'; // erstes UF ausgeben (ist immer 'a')
						$alephseq .= $_GET['sysno']." 711   L \$\$a".$stw['pica045Da'][0]; // erstes UF in Datei schreiben
						array_shift($stw['pica045Da']); // erstes UF rausschmeissen
						foreach ($stw['pica045Da'] as $mysubject) { 
							echo ' / <strong>'.$mysubject.'</strong>';
							$alephseq .= "\$\$a".$mysubject;
						} 
						if (!empty($stw['pica045D0'])) { echo '<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;_9: <strong>' .$stw['pica045D0'].'</strong>';
							$alephseq .= "\$\$x".$stw['pica045D0']."\$\$xSTW";}
						$alephseq .= "\r\n";
					}
					echo '</div>';

				}
		echo '[<a target="top" href="http://gso.gbv.de/DB=2.1/PPNSET?PPN='.$item["ppn"].'">Link zum GBV-Titelsatz</a>]';
		echo '</div>';


	} else { echo '<span style="color:red"> kein STW gefunden!</span>';}

	$titelcounter++;

}

}

$file ="data/gbv/stw/gbv-stw-beta.txt";
$fp = fopen($file, "a") or die("Couldn't open $file for writing!");
fwrite($fp,$alephseq) or die(" es wurde nichts in Datei geschrieben!"); 
fclose($fp); 

?>