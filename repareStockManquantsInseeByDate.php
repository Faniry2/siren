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



$sDateFormat = "2019-11-01";
//$sDateLastTraiement = "2019-03-13";


echo "\nTRAIETEMENT EN COURS = " . $sDateFormat . "\n";
//$sDateFormat = strftime("%Y-%m-%d", mktime(0, 0, 0, date('m'), date('d') - 1, date('Y'))); 


/* * ************************************************************************************************************* */
$numfic = 1;

//$sDateFormat = "2019-04-20";
$bNettoyageGeosirene = TRUE;
$bContinue = TRUE;
$bIsBanoOk = false;

/* * ************************************************************************************************************* */



// ON RECUPERE LE JETON POUR L'API INSEE
echo "*********************DATE => " . $sDateFormat . "**************************\n";

while ($bContinue) {

    $resultJSON_old = $oApiInsee->getJetonInsee();

    $aRes = $oApiInsee->revokeJetonInsee($resultJSON_old->access_token);

    $resultJSON = $oApiInsee->getJetonInsee();

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

        $off = 0;
        $continue = true;
        $inbCreation = 0;
        $inbUdpate = 0;



        while ($continue) {

            $aTmpStock = $oConnectPG->getTmpStockOffset($off);


            $bContinueBano = true;
            $iCountPbBano = 0;



            Util::logMajGeosirene("Nombre TMP STOCK " . count($aTmpStock));


            for ($i = 0; $i < count($aTmpStock); $i++) {

                /*                 * ***************DENOMINATION****************************** */

                $denominationGeoscar = "";
                //SI denominationusuelleetablissement is null et enseigne1etablissement is null
                if (!$aTmpStock[$i]['denominationusuelleetablissement'] && !$aTmpStock[$i]['enseigne1etablissement']) {

                    //on cherche dans le stock_ul
                    $tabUl = $oConnectPG->getUlBiSIren($aTmpStock[$i]['siren']);
                    if ($tabUl) {

                        $denominationGeoscar = $tabUl[0]['denominationunitelegale'] ? $tabUl[0]['denominationunitelegale'] : $tabUl[0]['prenom1unitelegale'] . ' ' . $tabUl[0]['nomunitelegale'];

                        //on cherche dans le stock_ul_cesses
                    } else {
                        $tabUlCessee = $oConnectPG->getUlCesseeBiSIren($aTmpStock[$i]['siren']);
                        if ($tabUlCessee) {

                            $denominationGeoscar = $tabUlCessee[0]['denominationunitelegale'] ? $tabUlCessee[0]['denominationunitelegale'] : $tabUlCessee[0]['prenom1unitelegale'] . ' ' . $tabUlCessee[0]['nomunitelegale'];
                        }
                    }
                } else {

                    $denominationGeoscar = $aTmpStock[$i]['denominationusuelleetablissement'] ? $aTmpStock[$i]['denominationusuelleetablissement'] : $aTmpStock[$i]['enseigne1etablissement'];
                }



                /*                 * ***************DENOMINATION****************************** */

                $bCreation = 'TRUE';
                $aArrayModifs = array();
                // ON REGARDE SI ON A DEJA L ETAB DANS LE STOCK
                $aStock = $oConnectPG->getStocksBySiret($aTmpStock[$i]['siret']);
                echo "================================SIRET =======================" . $aTmpStock[$i]['siret'] . "============================\n";
                if (count($aStock) > 0) {

                    $bCreation = 'FALSE';

                    // COMPARAISON DES MODIFICATIONS
                    $aArrayModifs = $oUtil->compareModifsEtablissement($aTmpStock[$i], $aStock[0]);
                    if (count($aArrayModifs) > 0) {

                        $oGeosireneTraitement->insertDebug($aTmpStock[$i]['siret'], 'FALSE', 'TRUE', $sDateFormat);

                        $sEtatAdministratif = $aTmpStock[$i]['etatadministratifetablissement'];
                        //echo "etatadministratifetablissement = " . $sEtatAdministratif . "<br>";
                        if ($sEtatAdministratif == 'F') {

                            Util::logMajGeosirene("MODIFICATION FERME " . $aTmpStock[$i]['siret']);
                            //echo "--------ETAB FERME <br>";
                            // on la supprime du stock where siret = $aTmpStock[$i]['siret']
                            $oConnectPG->deleteEtabFermeInStock($aTmpStock[$i]['siret']);
                            // on l'insert dans la table sirene_etablissement_ferme
                            $aStockFerme = $oConnectPG->getStockFermeBySiret($aTmpStock[$i]['siret']);
                            if (!$aStockFerme) {
                                $oConnectPG->insertStockFermes($aTmpStock[$i], $denominationGeoscar);
                            }
                        } else {
                            $inbUdpate++;
                            Util::logMajGeosirene("MODIFICATION " . $aTmpStock[$i]['siret']);
                            // on update
                            $oConnectPG->updateStock($aTmpStock[$i], $sDateFormat, $bCreation, $denominationGeoscar);
                        }
                    } else {

                        //$inbUdpate++;
                        Util::logMajGeosirene("PRESENT STOCK SANS MODIF  " . $aTmpStock[$i]['siret']);
                    }
                } else {


                    $sEtatAdministratif = $aTmpStock[$i]['etatadministratifetablissement'];
                    if ($sEtatAdministratif == 'F') {

                        $bCreation = 'FALSE';
                        Util::logMajGeosirene("CREATION FERME --" . $aTmpStock[$i]['siret']);
                        $oConnectPG->deleteEtabFermeInStock($aTmpStock[$i]['siret']);
                        // on l'insert dans la table sirene_etablissement_ferme
                        $aStockFerme = $oConnectPG->getStockFermeBySiret($aTmpStock[$i]['siret']);
                        if (!$aStockFerme) {
                            $oConnectPG->insertStockFermes($aTmpStock[$i], $denominationGeoscar);
                        }
                    } else if ($aTmpStock[$i]['nomenclatureactiviteprincipaleetablissement'] === "NAFRev2") {

                        $bCreation = 'TRUE';

                        Util::logMajGeosirene("CREATION " . $aTmpStock[$i]['siret'] . " / " . $aTmpStock[$i]['nomenclatureactiviteprincipaleetablissement']);
                        Util::logMajGeosirene("********* INSERT " . $aTmpStock[$i]['siret'] . " / " . $aTmpStock[$i]['nomenclatureactiviteprincipaleetablissement']);
                        $inbCreation++;
                        $oGeosireneTraitement->insertDebug($aTmpStock[$i]['siret'], 'TRUE', 'FALSE', $sDateFormat);
                        $oConnectPG->insertStock($aTmpStock[$i], $sDateFormat, $bCreation, $denominationGeoscar);
                    }
                }


                // ON UPDATE AVEC LES INFOS DE BANO
                $sAdresse = $oConnectPG->formatAdressePourBanoTableau($aTmpStock[$i]);
                $oResult = $oApiBano->sendRequest($sAdresse);
                $oConnectPG->updateGeosireneBanoFromApi($oResult, $aTmpStock[$i]['siret'], $numfic);

                //echo "----------------FIN-UPDATE BANO---------------------\n\n";
            }

            if (!$aTmpStock) {
                $continue = FALSE;
            } else {
                $off = $off + 5000;
            }
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
        //}
    }
}


