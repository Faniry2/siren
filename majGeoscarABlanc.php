

<?php

error_reporting(E_ALL & ~E_NOTICE);
require_once 'classes/apiInsee.php';
require_once 'classes/apiBano.php';
require_once 'classes/connectPostgreSql.php';
require_once 'classes/geosireneTraitement.php';
require_once 'classes/ConnectGeocube.php';
require_once 'classes/remplirDenoGeoscar.php';
require_once 'SearchPj.php';

$oApiBano = new apiBano();
$oApiInsee = new apiInsee();
$oConnectPG = new connectPostreSql();
$oUtil = new Util();
$oGeosireneTraitement = new geosireneTraitement();
$oGeocube = new ConnectGeocube();
$oSearchPj = new SearchPj();
$oDenoGeoscar = new RemplirDenoGeoscar();

date_default_timezone_set("Europe/Paris");
ini_set('max_execution_time', 0);
ini_set('memory_limit', '-1');
date_default_timezone_set('UTC');

$bDeuxJours = true;
$oTestBano = $oApiBano->testBanoOK();

if (!$oTestBano->features) {
    $oConnectPG->sendMailIncidentBano();
    die();
}




$sDateFormat = strftime("%Y-%m-%d", mktime(0, 0, 0, date('m'), date('d') - 1, date('Y')));


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





/* * *********************************ETAPE 1*********************************************************** */


/* * *********************************ETAPE 2*********************************************************** */

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
        
        
         echo "--------------------ACTICITE = " . $aTmpStock[$i]['activiteprincipaleetablissement'] . "\n";
        $sActivite = str_replace(".", "", $aTmpStock[$i]['activiteprincipaleetablissement']);
        echo "--------------------ACTICITE APRES = " . $sActivite . "\n";



        /*         * ***************DENOMINATION****************************** */

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



        /*         * ***************DENOMINATION****************************** */

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

                //$oGeosireneTraitement->insertDebug($aTmpStock[$i]['siret'], 'FALSE', 'TRUE', $sDateFormat);

                $sEtatAdministratif = $aTmpStock[$i]['etatadministratifetablissement'];
                echo "etatadministratifetablissement = " . $sEtatAdministratif . "<br>";
                if ($sEtatAdministratif == 'F') {

                    echo "MODIFICATION FERME \n";
                    //Util::logMajGeosirene("MODIFICATION FERME " . $aTmpStock[$i]['siret']);
                    //echo "--------ETAB FERME <br>";
                    // on la supprime du stock where siret = $aTmpStock[$i]['siret']
                    //$oConnectPG->deleteEtabFermeInStock($aTmpStock[$i]['siret']);
                    // on l'insert dans la table sirene_etablissement_ferme
                    $aStockFerme = $oConnectPG->getStockFermeBySiret($aTmpStock[$i]['siret']);
                    if (!$aStockFerme) {
                        
                        echo "INSERT FERME \n";
                        //$oConnectPG->insertStockFermes($aTmpStock[$i], $denominationGeoscar);
                    }
                } else {
                    $inbUdpate++;
                    
                    echo "MODIFICATION OUVERT \n";
                    //Util::logMajGeosirene("MODIFICATION " . $aTmpStock[$i]['siret']);
                    // on update
                    //$oConnectPG->updateStock($aTmpStock[$i], $sDateFormat, $bCreation, $denominationGeoscar);
                }
            } else {
                echo "PRESENT STOCK SANS MODIF\n";

            }
        } else {


            $sEtatAdministratif = $aTmpStock[$i]['etatadministratifetablissement'];
            echo "etatadministratifetablissement = " . $sEtatAdministratif . "<br>";
            if ($sEtatAdministratif == 'F') {

                $bCreation = 'FALSE';
                Util::logMajGeosirene("CREATION FERME --" . $aTmpStock[$i]['siret']);
                //$oConnectPG->deleteEtabFermeInStock($aTmpStock[$i]['siret']);
                // on l'insert dans la table sirene_etablissement_ferme
                $aStockFerme = $oConnectPG->getStockFermeBySiret($aTmpStock[$i]['siret']);
                if (!$aStockFerme) {
                    
                    echo "INSERT FERME \n";
                    //$oConnectPG->insertStockFermes($aTmpStock[$i], $denominationGeoscar);
                }
            } else if ($aTmpStock[$i]['nomenclatureactiviteprincipaleetablissement'] === "NAFRev2") {

                $bCreation = 'TRUE';

               echo "INSERT OUVERT \n";
                
                $inbCreation++;

            }
        }

    }

    if (!$aTmpStock) {
        $continue = FALSE;
    } else {
        $off = $off + 5000;
    }
}



echo "******************************************FIN TRAIMEMENT************************************************\n";




