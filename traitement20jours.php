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

//$debug = FALSE;


$oApiBano = new apiBano();
$oApiInsee = new apiInsee();
$oConnectPG = new connectPostreSql();
$oUtil = new Util();
$oGeosireneTraitement = new geosireneTraitement();

date_default_timezone_set("Europe/Paris");
ini_set('max_execution_time', 0);
ini_set('memory_limit', '-1');

date_default_timezone_set('UTC');

$sDateFin = strftime("%Y-%m-%d", mktime(0, 0, 0, date('m'), date('d') - 1, date('Y')));
$sDateDebut = strftime("%Y-%m-%d", mktime(0, 0, 0, date('m'), date('d') - 16, date('Y')));

echo 'date début = '.$sDateDebut.' date fin = '.$sDateFin.'<br>';

$numfic = 16;
//VIDE LE STOCK DES CREATIONS
//$oConnectPG->cleanStock();


// VIDE LA TABLE       
$oConnectPG->trucateGeosireneTmp();


// ON RECUPERE LE JETON POUR L'API INSEE
$resultJSON = $oApiInsee->getJetonInsee();
//echo($resultJSON['access_token']);

while (strtotime($sDateDebut) <= strtotime($sDateFin)) {

    unlink(FILE_RESULT_POUR_BANO);
    unlink(FILE_SORTIE_BANO);

    echo "*********************DATE => " . $sDateDebut . "**************************<br>";

    // INSERT EN TABLE TEMPORAIRE + CREE LE FICHIER GEOCODE
    $oGeosireneTraitement->etape1($resultJSON, $sDateDebut);


    $oGeosireneTraitement->etape2($sDateDebut, $numfic);

    //EXPORTE LA DERNIERE JOURNEE
     //$oGeosireneTraitement->exportTableGeosirene();
     //$oGeosireneTraitement->exportTableGeosireneHisto($numfic, $sDateDebut);

    //PROCEDURE
    $oGeosireneTraitement->procImportTraitement();

    echo "*********************DATE => " . $sDateDebut . "* NUM FIC = " . $numfic . "*************************<br>";
    $sDateDebut = date("Y-m-d", strtotime("+1 day", strtotime($sDateDebut)));

    $numfic=$numfic-1;
}