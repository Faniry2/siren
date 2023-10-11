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

$sDateFormat = "2019-11-21";
$bNettoyageGeosirene = TRUE;
$bContinue = TRUE;
$bIsBanoOk = false;

/* * ************************************************************************************************************* */

$oTestBano = $oApiBano->testBanoOK();

if (!$oTestBano->features) {
    $oConnectPG->sendMailIncidentBano();
    die();
}

// ON RECUPERE LE JETON POUR L'API INSEE
echo "*********************DATE => " . $sDateFormat . "**************************\n";

while ($bContinue) {

    $resultJSON_old = $oApiInsee->getJetonInsee();

    $aRes = $oApiInsee->revokeJetonInsee($resultJSON_old->access_token);

    $resultJSON = $oApiInsee->getJetonInsee();
    var_dump($resultJSON);
    //unlink(FILE_RESULT_POUR_BANO);
    //unlink(FILE_SORTIE_BANO);
    //unlink(FILE_LOG_MAJ);

    // SI ON A LE JETON INSEE ON NE FAIT QU'UNE FOIS
    if (isset($resultJSON)) {
        $bContinue = FALSE;
        Util::logMajGeosirene("Jeton INSEE " . $resultJSON->access_token);

        /*         * *********************************ETAPE 1*********************************************************** */


        // INSERT EN TABLE TEMPORAIRE 
        $oGeosireneTraitement->etape1Bis($resultJSON, $sDateFormat);

        /*         * *********************************ETAPE 2*********************************************************** */

        $aTmpStock = $oConnectPG->getTmpStock();
        //VIDE LA TABLE DE DEBUG
        $inbCreation = 0;
        $inbUdpate = 0;
        $bContinueBano = true;
        $iCountPbBano = 0;

        if ($bNettoyageGeosirene) {
            Util::logMajGeosirene("Nettoyage geosirene");
        }

        foreach ($aTmpStock as $key => $value) {


            /*             * ***************DENOMINATION****************************** */

            $denominationGeoscar = "";
            //SI denominationusuelleetablissement is null et enseigne1etablissement is null
            if (!$aTmpStock[$key]['denominationusuelleetablissement'] && !$aTmpStock[$key]['enseigne1etablissement']) {

                //on cherche dans le stock_ul
                $tabUl = $oConnectPG->getUlBiSIren($aTmpStock[$key]['siren']);
                if ($tabUl) {

                    $denominationGeoscar = $tabUl[0]['denominationunitelegale'] ? $tabUl[0]['denominationunitelegale'] : $tabUl[0]['prenom1unitelegale'] . ' ' . $tabUl[0]['nomunitelegale'];

                    //on cherche dans le stock_ul_cesses
                } else {
                    $tabUlCessee = $oConnectPG->getUlCesseeBiSIren($aTmpStock[$key]['siren']);
                    if ($tabUlCessee) {

                        $denominationGeoscar = $tabUlCessee[0]['denominationunitelegale'] ? $tabUlCessee[0]['denominationunitelegale'] : $tabUlCessee[0]['prenom1unitelegale'] . ' ' . $tabUlCessee[0]['nomunitelegale'];
                    }
                }
            } else {

                $denominationGeoscar = $aTmpStock[$key]['denominationusuelleetablissement'] ? $aTmpStock[$key]['denominationusuelleetablissement'] : $aTmpStock[$key]['enseigne1etablissement'];
            }



            /*             * ***************DENOMINATION****************************** */

            $bCreation = 'TRUE';
            $aArrayModifs = array();
            // ON REGARDE SI ON A DEJA L ETAB DANS LE STOCK
            $aStock = $oConnectPG->getStocksBySiret($aTmpStock[$key]['siret']);
            echo "================================SIRET =======================" . $aTmpStock[$key]['siret'] . "============================\n";
            if (count($aStock) > 0) {

                echo "------------- 1\n";

                $bCreation = 'FALSE';
                Util::logMajGeosirene("MODIFICATION " . $aTmpStock[$key]['siret'] . " / " . $aTmpStock[$key]['nomenclatureactiviteprincipaleetablissement']);


                // COMPARAISON DES MODIFICATIONS
                $aArrayModifs = $oUtil->compareModifsEtablissement($aTmpStock[$key], $aStock);
                if (count($aArrayModifs) > 0) {
                    echo "------------- 2\n";
                    $inbUdpate++;
                    $oGeosireneTraitement->insertDebug($aTmpStock[$key]['siret'], 'FALSE', 'TRUE', $sDateFormat);

                    $sEtatAdministratif = $aTmpStock[$key]['etatadministratifetablissement'];
                    //echo "etatadministratifetablissement = " . $sEtatAdministratif . "<br>";
                    if ($sEtatAdministratif == 'F') {
                        echo "------------- 3\n";
                        //echo "--------ETAB FERME <br>";
                        // on la supprime du stock where siret = $aTmpStock[$key]['siret']
                        $oConnectPG->deleteEtabFermeInStock($aTmpStock[$key]['siret']);
                        // on l'insert dans la table sirene_etablissement_ferme
                        $aStockFerme = $oConnectPG->getStockFermeBySiret($aTmpStock[$key]['siret']);
                        if (!$aStockFerme) {
                            echo "------------- 4\n";
                            $oConnectPG->insertStockFermes($aTmpStock[$key], $denominationGeoscar);
                        }
                    } else {
                        echo "------------- 5\n";
                        // on update
                        $oConnectPG->updateStock($aTmpStock[$key], $sDateFormat, $bCreation, $denominationGeoscar);
                    }
                }
            } else {
                $bCreation = 'TRUE';
                if ($aTmpStock[$key]['nomenclatureactiviteprincipaleetablissement'] === "NAFRev2") {

                    Util::logMajGeosirene("CREATION " . $aTmpStock[$key]['siret'] . " / " . $aTmpStock[$key]['nomenclatureactiviteprincipaleetablissement']);
                    Util::logMajGeosirene("********* INSERT " . $aTmpStock[$key]['siret'] . " / " . $aTmpStock[$key]['nomenclatureactiviteprincipaleetablissement']);

                    $inbCreation++;
                    $oGeosireneTraitement->insertDebug($aTmpStock[$key]['siret'], 'TRUE', 'FALSE', $sDateFormat);
                    echo "------------- 6\n";
                    $oConnectPG->insertStock($aTmpStock[$key], $sDateFormat, $bCreation, $denominationGeoscar);
                }
            }
            echo "------------- 7\n";
            // ON INSERT LES MODIFS DE LA JOURNEE
            $oConnectPG->insertGeosirene($aTmpStock[$key], $aArrayModifs, $numfic, $sDateFormat, $bCreation, $denominationGeoscar);


            // ON UPDATE AVEC LES INFOS DE BANO
            $sAdresse = $oConnectPG->formatAdressePourBanoTableau($aTmpStock[$key]);
            $oResult = $oApiBano->sendRequest($sAdresse);
            $oConnectPG->updateGeosireneBanoFromApi($oResult, $aTmpStock[$key]['siret'], $numfic);
        }

        $oConnectPG->sendMailRecapMajgeosirene($inbCreation, $inbUdpate);


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

        $oConnectPG->updateStockGeoInsee($sDateFormat);
        $oConnectPG->updateStockFermeGeoInsee($sDateFormat);
        //PROCEDURE
        $oGeosireneTraitement->procImportTraitement();
        $oGeosireneTraitement->procRegenFichier();
        //$oGeosireneTraitement->procRegenCartes();
        //$sDateFormatJJ = strftime("%Y-%m-%d", mktime(0, 0, 0, date('m'), date('d'), date('Y')));
        //$oGeosireneTraitement->procRegenCartesJour($sDateFormatJJ);
        $oGeosireneTraitement->agregationPoiIris();
        $oGeosireneTraitement->agregationIrisCom();
        $oGeosireneTraitement->procbdf_calcul_agregation_poi_iris_diff_geosirene();
        $oGeosireneTraitement->procbdf_calcul_agregation_poi_iris_diff_geosirene_ferme();
        $oGeosireneTraitement->procbdf_calcul_agregation_update_diff_geosirene();
        $oGeosireneTraitement->procbdf_calcul_agregation_update_diff_geosirene_ferme();

        Util::logMajGeosirene("Fin traitement");

        echo "******************************************FIN TRAIMEMENT************************************************\n";

        $dateNumFic1 = strftime("%Y-%m-%d", mktime(0, 0, 0, date('m'), date('d') - 1, date('Y')));


        for ($i = 1; $i < 31; $i++) {
            echo "i = " . $i . " ";
            $dateNumFic1 = strftime("%Y-%m-%d", mktime(0, 0, 0, date('m'), date('d') - $i, date('Y')));
            echo "  " . $dateNumFic1 . "\n";
            $oConnectPG->updateNumFicGeosireneRepare($i, $dateNumFic1);
        }
    } else {
        echo "PAS DE JETON --------------------------------\n";
    }
}


