<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
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

            // si déjà présent on ne le prend pas
            $aRes = $oConnectPG->searchComptables($siret);


            if (count($aRes) > 0) {
                echo "DEJA PRESENT\n";
            } else {

                //$sResult = $oPj->sendRequest118($nom, $adresse);
                $sResult = $oWebScrap->curl("http://www.118218.fr/recherche?what=" . $nom . "&where=" . $adresse . "&distance=12");

                // si pas de réponse, on switch de vpn
                if (strlen($sResult) == 0) {

                    $oVpn->vpnDisconnect();
                    $oVpn->vpnConnect();

                    $sResult = $oWebScrap->curl("http://www.118218.fr/recherche?what=" . $nom . "&where=" . $adresse . "&distance=12");
                }

                $dom = new DOMDocument();
                @$dom->loadHTML($sResult);

                $sTelephone = "";

                $body = $dom->documentElement->lastChild;

                foreach ($dom->getElementsByTagName('p') as $link) {

                    $sClass = $link->getAttribute('class');
                    if ($sClass == "telephone") {
                        $sTelephone = $link->nodeValue;

                        echo "siret = " . $siret . "\n";
                        echo "telephone = " . $sTelephone . "\n";
                        // On insert et on sort
                        $oConnectPG->insertComptables($siret, $sTelephone);
                        break;
                    } else {

                        $sTelephone = "00 00 00 00 00";
                        echo "siret = " . $siret . "\n";
                        echo "telephone = " . $sTelephone . "\n";

                        $oConnectPG->insertComptables($siret, $sTelephone);
                        break;
                    }
                }
            }
        }
    }
}