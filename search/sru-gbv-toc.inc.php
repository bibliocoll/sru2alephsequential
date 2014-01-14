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
$datafield009 = $xpath->query( "//m:datafield[@tag='009Q' or @tag='009P']");
// Check, etwas unbeholfen geschrieben, aber klappt wenigstens (sonst Fehler, wenn 009 fehlt, man muesste schoner das myget-Array auseinanderklamuesern:
$datafield009_check = myget( "//m:datafield[@tag='009Q' or @tag='009P']",$xpath); 
$pica = array();
 
foreach ( $record as $item ) {
	$newDom = new DOMDocument;
	$newDom->appendChild($newDom->importNode($item,true));
 
	$xpath = new DOMXPath( $newDom ); 

	$xpath->registerNamespace("m","info:srw/schema/5/picaXML-v1.0");

	$pica004 = myget("//m:datafield[@tag='004A']/m:subfield[@code='A' or @code='0']",$xpath);
	$pica011 = myget("//m:datafield[@tag='011@']/m:subfield[@code='a']",$xpath);

	$datafield009 = $xpath->query( "//m:datafield[@tag='009Q' or @tag='009P']");


$url_array =array();	
	if (!empty($datafield009_check)) { // hier der unbeholfene Check s.o.
	foreach ($datafield009 as $data) {
		$newDom = new DOMDocument;
		$newDom->appendChild($newDom->importNode($data,true));
		
		$xpath = new DOMXPath( $newDom ); 
		$xpath->registerNamespace("m","info:srw/schema/5/picaXML-v1.0");
		$pica009_sub_y = myget("m:subfield[@code='y']",$xpath);
		if ($pica009_sub_y == "Inhaltsverzeichnis") { 
		$pica009 = myget("m:subfield[@code='a']",$xpath);
				} else {$pica009 = "";}
		$url_array[] = array($pica009_sub_y => $pica009);
		
	}
	}  else {$url_array = ""; }	
	$pica[] = array( 'isbn' => $pica004, 'url' => $url_array, 'year' => $pica011 );

}
							
//print_r($url_array);
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

	if (!empty($item['url'])) {
		//	print_r($item['url']);

		echo "(".$item['year'].") ";

				foreach ($item['url'] as $url) {
				  if (!empty($url['Inhaltsverzeichnis'])){
					global $alephseq;	
					global $urlmatch;
					echo '<div class="gefunden">';
					
						// schlechte URL, gute URL:
						if (preg_match("/bowker/i", $url['Inhaltsverzeichnis']) || preg_match("/dandelon/i", $url['Inhaltsverzeichnis']) || preg_match("/ohiolink/i", $url['Inhaltsverzeichnis']) || preg_match("/bvb/i", $url['Inhaltsverzeichnis'])) { 
							$urlmatch = "bad"; 
              $badtext = "Nicht erlaubte URL, wurde nicht in Datei geschrieben!";
						} else { 
              $urlmatch = "good"; 
              $badtext = "OK";
							$alephseq .= $_GET['sysno']." 078   L \$\$aGBV-ToC\r\n"; // Abrufzeichen	
							$alephseq .= $_GET['sysno']." 655e  L \$\$3Inhaltsverzeichnis\$\$u".$url['Inhaltsverzeichnis']."\r\n";
            }
						
						echo ' <span class="'.$urlmatch.'">';
				    echo '  [<a href="'.$url['Inhaltsverzeichnis'].'">'.$url['Inhaltsverzeichnis'].'</a>] ';
						echo ' </span>';
            echo '<br/><strong><em><span class="badtext">'.$badtext.'</span></em></strong>';

					}
				}
	} else { echo '<span style="color:red"> kein Inhaltsverzeichnis gefunden!</span>';}

	$titelcounter++;

}

}
$file ="data/gbv/toc/gbv-toc-alle.txt";
$fp = fopen($file, "a") or die("Couldn't open $file for writing!");
fwrite($fp,$alephseq) or die(" es wurde nichts in Datei geschrieben!"); 
fclose($fp); 

?>