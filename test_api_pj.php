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

//unlink(FILE_RESULT_POUR_PJ);
$bContine = true;
//$gid = 0;




//$aGeosirene = $oConnectPG->getGeosireneNumFic1($gid);
//$aGeosirene = $oConnectPG->getGeosireneNumFic1();
$aGeosirene = $oConnectPG->getGeosireneNumFic2();

echo "NB ETAB = " . count($aGeosirene) . "\n";


for ($l = 0; $l < count($aGeosirene); $l++) {


    $sNumTel = "";
    $siret = "";
    $adresse = "";
    $nom = "";


    echo ".......................... COUNTER $l ...................................... \n";

//    if ($gid != 0) {
//        $aGeosirene = $oConnectPG->getGeosireneNumFic1($gid);
//    }


    $adresse = $oConnectPG->formatAdressePourBanoTableau($aGeosirene[$l]);


    $nom_old = $aGeosirene[$l]['denomination_geoscar'];
    $siret = $aGeosirene[$l]['siret'];
    // remplace les caracères spéciaux par des +
    $nom = $oConnectPG->formatEnseignePj($nom_old);

    // si nom et adresse non vide
    if (strlen($adresse) > 0 && strlen($nom) > 0) {

        $resultat = $oPj->sendRequest($nom, $adresse, $siret);


        if ($resultat) {

            echo "http://192.168.1.130:8080/REST_API_PJGeosiren/geosiren.com/apipj/search?siret=" . $siret . "&nom=" . $nom . "&adresse=" . $adresse . " \n";
            if ($resultat->m_head->m_status == "200") {

                echo "-----------STATUS 200 \n";

                $sNumTel = $resultat->m_entreprise->m_numero;
                $iScore = $resultat->m_entreprise->m_scoreNomEtablissement;

                echo "***** NOM = " . $nom . " ADRESSE = " . $adresse . " Tel = " . $sNumTel . " siret = " . $siret . "\n";

                $oConnectPG->updateTelGeosirene($aGeosirene[$l]['gid'], $sNumTel, $iScore);
                $oConnectPG->updateTelStock($aGeosirene[$l]['siret'], $sNumTel, $iScore);
                $oConnectPG->insertTablePJ($aGeosirene[$l]['siret'], $sNumTel, $iScore);
                //$oConnectPG->ajoutFichierPagesJaunes($aGeosirene[$l]['siret'], $sNumTel, $iScore);
            } else {

                $gid = $aGeosirene[$l]['gid'];
                echo "-----------STATUS KO GID = $gid \n";
                sleep(10);
            }
        } else {

            //var_dump($resultat);
            echo "======= PAS DE RESULTAT \n";

            //die();
        }
    } else {
        echo "||||||||||| ADRESSE ET NOM VIDE \n";
    }
}
//$oConnectPG->sendFileFTPPagesJaunes();
$oConnectPG->sendMailPagesJaunes();

echo " ////////////////////////////FIN TRAITEMENT/////////////////// \n";
//}
//}