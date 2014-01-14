<?php
// UPDATE SEPTEMBER 2011: neuer GBV-Dienst (sru.gbv.de) //
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
$x = "http://sru.gbv.de/gvk?version=1.1&operation=searchRetrieve&query=pica.isb%3D%22".trim($_GET['isbn'])."&maximumRecords=10&recordSchema=picaxml";

	$neuDom = new DOMDocument;

  $neuDom->load($x);
	$xpath = new DOMXPath( $neuDom ); 


$xpath->registerNamespace("m","info:srw/schema/5/picaXML-v1.0");

$record = $xpath->query( "//m:record" );
$datafield009 = $xpath->query( "//m:datafield[@tag='044A' or @tag='044A']");
// Check, etwas unbeholfen geschrieben, aber klappt wenigstens (sonst Fehler, wenn 009 fehlt, man muesste schoner das myget-Array auseinanderklamuesern:
$datafield009_check = myget( "//m:datafield[@tag='044A' or @tag='044A']",$xpath); 
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

	$datafield009 = $xpath->query( "//m:datafield[@tag='044A' or @tag='044A']");


$url_array =array();	
	if (!empty($datafield009_check)) { // hier der unbeholfene Check s.o.
	foreach ($datafield009 as $data) {
		$newDom = new DOMDocument;
		$newDom->appendChild($newDom->importNode($data,true));
		
		$xpath = new DOMXPath( $newDom ); 
 		$xpath->registerNamespace("m","info:srw/schema/5/picaXML-v1.0");
		$pica044Aa = myget("m:subfield[@code='a']",$xpath);
		$pica044Ax = myget("m:subfield[@code='x']",$xpath);
		$pica044Az = myget("m:subfield[@code='z']",$xpath);
		$pica044Av = myget("m:subfield[@code='v']",$xpath);
		$url_array[] = array("subject_a" => $pica044Aa,
												 "subject_x" => $pica044Ax,
												 "subject_z" => $pica044Az,
												 "subject_v" => $pica044Av);
		
	}
	}  else {$url_array = ""; }	
	$pica[] = array( 'titel' => $pica021A, 'isbn' => $pica004, 'url' => $url_array, 'year' => $pica011, 'ppn' => $pica_ppn );

}
							
//print_r($url_array);
//print_r($pica)."<BR>";	
//echo $pica['url']['Inhaltsverzeichnis'];			

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

	if (!empty($item['url'])) {
		//	print_r($item['url']);
		$urlmatch = "good";
		echo '<span class="'.$urlmatch.'">Subject Headings gefunden!';
		echo ' </span>';

		echo '<div class="gbvmatch">';
		echo '<em>'.$item['titel'].'</em> ('.$item['year'].') ';
		echo '<br/>';


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
						if (!preg_match("/^Electronic books$/", $url['subject_a'])) {
							echo 'MAB 740s_a: <strong>'.$url['subject_a'].'</strong>';
							//$alephseq .= "000029109 740    L \$\$a".$url['subject_a'];
							$alephseq .= $_GET['sysno']." 078   L \$\$aGBV-SH\r\n"; // Abrufzeichen
							$alephseq .= $_GET['sysno']." 740s  L \$\$a".$url['subject_a'];
						} else {
							echo 'Schlagwort ist <strong>Electronic books</strong>, verworfen!';
						}
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
						if (!empty($url['subject_z'])) { 
							if (!is_array($url['subject_z'])) {
								echo '<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;_z: <strong>' .$url['subject_z'].'</strong>';
								$alephseq .= "\$\$z".$url['subject_z'];
							} else { // if array, falls mehrere UF z
								foreach ($url['subject_z'] as $z) {
									echo '<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;_z: <strong>' .$z.'</strong>';
									$alephseq .= "\$\$z".$z;
								}
							}
						}
						if (!empty($url['subject_v'])) { 
							if (!is_array($url['subject_v'])) {
								echo '<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;_v: <strong>' .$url['subject_v'].'</strong>';
								$alephseq .= "\$\$v".$url['subject_v'];
							} else { // if array, falls mehrere UF v
								foreach ($url['subject_v'] as $v) {
									echo '<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;_v: <strong>' .$v.'</strong>';
									$alephseq .= "\$\$v".$v;
								}
							}
						}
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
		echo '[<a target="top" href="http://gso.gbv.de/DB=2.1/PPNSET?PPN='.$item["ppn"].'">Link zum GBV-Titelsatz</a>]';
		echo '</div>';
	} else { echo '<span style="color:red"> keine Subject Headings gefunden!</span>';}

	$titelcounter++;

}

}

// in Datei schreiben:
$file = "data/gbv/subject/subject-headings.txt";
// in Datei mit fortlaufend schreiben:
//$file = "data/gvk-subjects-kumulierend.txt";
$fp = fopen($file, "a") or die("Couldn't open $file for writing!");
fwrite($fp,$alephseq) or die(" es wurde nichts in Datei geschrieben!"); 

fclose($fp); 

?>