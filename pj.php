<?php

require __DIR__ . '/vendor/autoload.php';
include 'classes/apiInsee.php';
include 'classes/apiBano.php';
include 'classes/connectPostgreSql.php';
include 'classes/geosireneTraitement.php';
include 'classes/ConnectGeocube.php';
include 'classes/ApiPJ.php';
include 'classes/Vpn.php';
include 'classes/WebScrap.php';


error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);


$oApiBano = new apiBano();
$oApiInsee = new apiInsee();
$oConnectPG = new connectPostreSql();
$oUtil = new Util();
$oGeosireneTraitement = new geosireneTraitement();
$oGeocube = new ConnectGeocube();
$oWebScrap = new WebScrap();

date_default_timezone_set("Europe/Paris");
ini_set('max_execution_time', 0);
ini_set('memory_limit', '-1');
date_default_timezone_set('UTC');



$oPj = new ApiPJ();
$oVpn = new Vpn();


$oConnectPG->createTableComptables();
$oVpn->vpnConnect();
//$sFile = "C:\\Users\\sleco\\Documents\\pj_comptables\\comptables.csv";
$sFile = "comptables.csv";
if (file_exists($sFile)) {
    echo 'file exist \n';
} else {
    die('file nt exist');
}
if ($file = @fopen($sFile, "r")) {


    $counter = 0;

    while (!feof($file)) {

        $counter++;

        //siret,adresse,intitule
        $aTabBano = fgetcsv($file, '', ",");
        $siret = $aTabBano[0];
        $adresse_old = $aTabBano[1];
        $nom_old = $aTabBano[2];

        $adresse = str_replace(" ", "+", $adresse_old);
        $nom = str_replace(" ", "+", $nom_old);

        if (strlen($adresse) > 0) {
            echo "SIREt =  $siret \n";
            // si déjà présent on ne le prend pas
            $aRes = $oConnectPG->searchComptables($siret);


            if (count($aRes) > 0) {
                echo "DEJA PRESENT\n";
            } else {

                echo "https://www.pagesjaunes.fr/annuaire/chercherlespros?quoiqui=" . $nom . "&ou=" . $adresse . "\n";

                $sResult = $oWebScrap->curl("https://www.pagesjaunes.fr/annuaire/chercherlespros?quoiqui=" . $nom . "&ou=" . $adresse);

                $responsehttp = $sResult['http_code'];
                
                while ($responsehttp!= 200) {
                    echo "SIREt =  $siret \n";
                    
                    sleep(3);
                    echo "/////////////////// ERREUR 403//////////////////////////////\n";
                    $oVpn->vpnDisconnect();
                    $oVpn->vpnConnect();
                    $sResult = $oWebScrap->curl("https://www.pagesjaunes.fr/annuaire/chercherlespros?quoiqui=" . $nom . "&ou=" . $adresse);
                    $responsehttp = $sResult['http_code'];
                }

                echo "---------------------> RESPONSE HTTP = ".$responsehttp."-----------------------------------------\n";

                $dom = new DOMDocument();

                @$dom->loadHTML($sResult['content']);

                $sTelephone = "";
                $icount = 0;
                foreach ($dom->getElementsByTagName('article') as $article) {


                    $crawl = new \Symfony\Component\DomCrawler\Crawler($article);


                    $numTag = $crawl->filter(".bi-contact .num");

                    foreach ($numTag as $bi_contact) {
                        $title = $bi_contact->getAttribute('title');
                        $numPhoneId = str_replace(" ", "", $title);

                        if (preg_match("^((\+)0|0|0)[1-9](\d{2}){4}^", trim($numPhoneId))) {

                            $sTelephone = $numPhoneId;
                            echo "siret = " . $siret . "\n";
                            echo "telephone = " . $sTelephone . "\n";
                            $oConnectPG->insertComptables($siret, $sTelephone);
                            break;
                        }
                    }



                    $icount++;
                    $idArt = $article->getAttribute('id');


                    $numPhoneId = substr($idArt, strlen('bi-bloc-'), 10);

                    //echo "numphoneid = " . $numPhoneId . "\n";

                    if (preg_match("^((\+)0|0|0)[1-9](\d{2}){4}^", $numPhoneId)) {
                        $sTelephone = $numPhoneId;
                        echo "siret = " . $siret . "\n";
                        echo "telephone = " . $sTelephone . "\n";
                        $oConnectPG->insertComptables($siret, $sTelephone);
                        break;
                    } 
                }

                if ($icount == 0) {
                    $icount2 = 0;
                    foreach ($dom->getElementsByTagName("strong") as $span) {

                        $classe = $span->getAttribute('class');
                        if ($classe == "num") {

                            $icount2++;
                            $numPhoneId = str_replace(" ", "", $span->getAttribute('title'));

                            if (preg_match("^((\+)0|0|0)[1-9](\d{2}){4}^", $numPhoneId)) {
                                $sTelephone = $numPhoneId;
                                echo "siret = " . $siret . "\n";
                                echo "telephone = " . $sTelephone . "\n";
                                $oConnectPG->insertComptables($siret, $sTelephone);
                                break;
                            }
                        }
                    }

                    if ($icount2 == 0) {
                        echo "****************PAS DE BALISE STRONG********************\n";
                    }
                    echo "****************PAS DE BALISE ARTICLE********************\n";
                }
                sleep(3);
            }
        }
    }
    echo "***********************************TRAITEMENT TERMINE********************************************\n";
}




