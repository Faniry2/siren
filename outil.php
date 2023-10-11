<?php

/**
 * Created by PhpStorm.
 * User: sleco
 * Date: 25/10/2018
 * Time: 13:20
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

        $numfic = 1;

        //$oApiBano->createFichierGeocode();
        $oConnectPG->updateGeosireneBanoTmp($numfic);
        $oConnectPG->updateGeosireneBanoStock();




die();
include 'classes/apiInsee.php';
include 'classes/apiBano.php';
include 'classes/connectPostgreSql.php';
include 'classes/geosireneTraitementDebug.php';
include 'classes/ConnectGeocube.php';

ini_set('memory_limit', '-1');
//ini_set('max_execution_time', 300);
set_time_limit(0);


$oApiBano = new apiBano();
$oApiInsee = new apiInsee();
$oConnectPG = new connectPostreSql();
$oUtil = new Util();
$oGeosireneTraitement = new geosireneTraitementDebug();
$oGeocube = new ConnectGeocube();
$oConnxionPG = new connectPostreSql();

$iOffset = 0;
$bContinue = TRUE;


while ($bContinue) {
    // RECUPERE LE STOCK A GEOCODER
    $aTabStock = $oConnectPG->getStockToGeocode($iOffset);

    for ($o = 0; $o < count($aTabStock); $o++) {

        $aGeosirenStock = $oConnectPG->getGeosireneStocksBySiret($aTabStock[$o]['siret']);
        // SI PAS DANS GEOSIRENE STOCK
        if (count($aGeosirenStock) == 0) {

            $aSireneN0 = $oGeocube->getSirenN0BySiret($aTabStock[$o]['siret']);

            // RECUEPRE LA LIGNE DU STOCK
            $aLigneSTock = $oConnectPG->getStocksBySiret($aTabStock[$o]['siret']);

            if (count($aLigneSTock) > 0) {
                // INSERT
                $oConnectPG->insertGeosireneStock($aLigneSTock[0]);
            }
            //SI DANS SIRENE_N0
            if (count($aSireneN0) > 0) {

                $oConnectPG->updateGeosireneStockBano($aSireneN0[0]);
            }
        }
    }

    $iOffset = $iOffset + 4000;
    if (count($aTabStock) == 0) {
        $bContinue = FALSE;
    }
}



die();




$oConnxionPG->getConnexion();

// NB ETABLISSEMENTS FERME DANS LA TABLE HISTO
$iNbEtaFermes = 18943483;
$iNbEtaFermesHelp = 1;



$timestamp_debut = microtime(true);

/*
 * Execution du code PHP
 * Exemple : affichage d'une page, script spécifique, requête SQL ...
 */

// timestamp en millisecondes de la fin du script
$timestamp_fin = microtime(true);

// différence en millisecondes entre le début et la fin
$difference_ms = $timestamp_fin - $timestamp_debut;


for ($p = 0; $p < $iNbEtaFermes; $p += 1000) {

    $aResultEtabFermes = $oConnxionPG->getEtablissementsFermes($p);
    echo "temps = " . $difference_ms . "<br>";


    for ($i = 0; $i < count($aResultEtabFermes); $i++) {

        $sSiret = $aResultEtabFermes[$i]['siret'];
        // vérification existance
        //$aTestExist = $oConnxionPG->getEtablissementsBySiret($sSiret);
        //$oConnxionPG->deleteEtablissementsFermes($sSiret);
        echo "temps = " . $difference_ms . "<br>";

        $iCountRest = $iNbEtaFermes - $iNbEtaFermesHelp;

        $iCountRest = $iNbEtaFermes - $iNbEtaFermesHelp;
        echo $aResultEtabFermes[$i]['id'] . " suppressinn siret = " . $sSiret . " / reste = " . $iCountRest . "<br>";
        $iNbEtaFermesHelp ++;
    }
}


//echo '<pre>'.var_dump($aResultEtabFermes).'</pre>';