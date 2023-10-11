<?php

error_reporting(E_ALL & ~E_NOTICE);
include 'classes/apiInsee.php';
include 'classes/apiBano.php';
include 'classes/connectPostgreSql.php';
include 'classes/geosireneTraitement.php';
include 'classes/ConnectGeocube.php';

$oApiBano = new apiBano();
$oApiInsee = new apiInsee();
$oConnectPG = new connectPostreSql();
$oUtil = new Util();
$oGeosireneTraitement = new geosireneTraitement();
$oGeocube = new ConnectGeocube();

date_default_timezone_set("Europe/Paris");
ini_set('max_execution_time', 0);
ini_set('memory_limit', '-1');
date_default_timezone_set('UTC');

$aGeosireneNonGeoc = $oConnectPG->getGeosireneNonGeoc();

for ($i = 0; $i < count($aGeosireneNonGeoc); $i++) {


    // ON UPDATE AVEC LES INFOS DE BANO
    $sAdresse = $oConnectPG->formatAdressePourBanoTableau($aGeosireneNonGeoc[$i]);
    $oResult = $oApiBano->sendRequest($sAdresse);
    echo "------------------------------------------------".$aGeosireneNonGeoc[$i]['siret']."-----------------------------------\n";
    $oConnectPG->updateGeosireneBanoFromApiSansNumFic($oResult, $aGeosireneNonGeoc[$i]['siret']);
    //$oConnectPG->updateGeosireneBanoFromApiProd($oResult, $aGeosireneNonGeoc[$i]['siret'], 1);
}

