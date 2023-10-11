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




/* * ************************************************************************************************************* */
$numfic = 1;

$sDateFormat = "2019-06-24";
$bNettoyageGeosirene = TRUE;
$bContinue = TRUE;
$bIsBanoOk = false;

$iOffset = 0;


while ($bContinue) {
    $aTmpStock = $oConnectPG->getTmpStockOffset($iOffset);
    //VIDE LA TABLE DE DEBUG
    $inbCreation = 0;
    $inbUdpate = 0;
    $bContinueBano = true;
    $iCountPbBano = 0;

    if ($bNettoyageGeosirene) {
        //$oGeosireneTraitement->nettoyageGeosirene();
        Util::logMajGeosirene("Nettoyage geosirene");
    }

    foreach ($aTmpStock as $key => $value) {

        $bCreation = 'TRUE';
        $aArrayModifs = array();
        // ON REGARDE SI ON A DEJA L ETAB DANS LE STOCK
        $aStock = $oConnectPG->getStocksBySiret($aTmpStock[$key]['siret']);
        $aGeosirenePresent = $oConnectPG->getGeosireneEstPresent($aTmpStock[$key]['siret']);


        echo "================================SIRET =======================" . $aTmpStock[$key]['siret'] . "============================\n";

        if (count($aGeosirenePresent) == 0) {
            if (count($aStock) > 0) {

                $bCreation = 'FALSE';
                // COMPARAISON DES MODIFICATIONS
                $aArrayModifs = $oUtil->compareModifsEtablissement($aTmpStock[$key], $aStock);
                if (count($aArrayModifs) > 0) {
                    $inbUdpate++;
                    $oGeosireneTraitement->insertDebug($aTmpStock[$key]['siret'], 'FALSE', 'TRUE', $sDateFormat);

                    $sEtatAdministratif = $aTmpStock[$key]['etatadministratifetablissement'];
                    //echo "etatadministratifetablissement = " . $sEtatAdministratif . "<br>";
                    if ($sEtatAdministratif == 'F') {
                        //echo "--------ETAB FERME <br>";
                        // on la supprime du stock where siret = $aTmpStock[$key]['siret']
                        $oConnectPG->deleteEtabFermeInStock($aTmpStock[$key]['siret']);
                        // on l'insert dans la table sirene_etablissement_ferme
                        $aStockFerme = $oConnectPG->getStockFermeBySiret($aTmpStock[$key]['siret']);
                        if (!$aStockFerme) {
                            $oConnectPG->insertStockFermes($aTmpStock[$key]);
                        }
                    } else {
                        // on update
                        $oConnectPG->updateStock($aTmpStock[$key], $sDateFormat);
                    }
                }
            } else {
                $bCreation = 'TRUE';
                if ($aTmpStock[$key]['nomenclatureactiviteprincipaleetablissement'] === "NAFRev2") {

                    $inbCreation++;
                    $oGeosireneTraitement->insertDebug($aTmpStock[$key]['siret'], 'TRUE', 'FALSE', $sDateFormat);
                    $oConnectPG->insertStock($aTmpStock[$key]);
                }
            }
        }

        // ON INSERT LES MODIFS DE LA JOURNEE
        $oConnectPG->insertGeosirene($aTmpStock[$key], $aArrayModifs, $numfic, $sDateFormat, $bCreation);
        // ON UPDATE AVEC LES INFOS DE BANO
        $sAdresse = $oConnectPG->formatAdressePourBanoTableau($aTmpStock[$key]);
        $oResult = $oApiBano->sendRequest($sAdresse);
        $oConnectPG->updateGeosireneBanoFromApi($oResult, $aTmpStock[$key]['siret'], $numfic);

        //echo "----------------FIN-UPDATE BANO---------------------\n\n";
    }

    $iOffset = $iOffset + 5000;
    if (!$aTmpStock) {
        $bContinue = false;
    }
}

$oConnectPG->sendMailRecapMajgeosirene($inbCreation, $inbUdpate);

/* * *********************************PROCEDURES********************************************************** */

$tab_jours = array(7, 1, 2, 3, 4, 5, 6);
$inumJour = $tab_jours[date('w', mktime(0, 0, 0, date('m'), date('d'), date('Y')))];
// ENVOIE DE MAIL ALERTE QUOTIDIEN      
$oGeosireneTraitement->sendMailAlerteQuotidienne($sDateFormat);
Util::logMajGeosirene("envoi mail alerte quotidienne");
// SI LUNDI ENVOI MAIL HEBDO
if ($inumJour == 1) {

    $sDateJour = strftime("%d-%m-%Y", mktime(0, 0, 0, date('m'), date('d'), date('Y')));
    $sDateSemDern = strftime("%d-%m-%Y", mktime(0, 0, 0, date('m'), date('d') - 8, date('Y')));
    $oGeosireneTraitement->sendMailAlerteHebdo($sDateFormat, $sDateJour, $sDateSemDern);
}

echo "******************************************PROCEDURES************************************************\n";

$oConnectPG->updateStockGeoInsee();
$oConnectPG->updateStockFermeGeoInsee();


//PROCEDURE
$oGeosireneTraitement->procImportTraitement();
$oGeosireneTraitement->procRegenCartes();
$sDateFormatJJ = strftime("%Y-%m-%d", mktime(0, 0, 0, date('m'), date('d'), date('Y')));
$oGeosireneTraitement->procRegenCartesJour($sDateFormatJJ);
$oGeosireneTraitement->agregationPoiIris();
$oGeosireneTraitement->agregationIrisCom();
$oGeosireneTraitement->procbdf_calcul_agregation_poi_iris_diff_geosirene();
$oGeosireneTraitement->procbdf_calcul_agregation_poi_iris_diff_geosirene_ferme();
$oGeosireneTraitement->procbdf_calcul_agregation_update_diff_geosirene();
$oGeosireneTraitement->procbdf_calcul_agregation_update_diff_geosirene_ferme();

Util::logMajGeosirene("Fin traitement");

echo "******************************************FIN TRAIMEMENT************************************************\n";







