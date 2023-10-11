<?php

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

$bContinue = TRUE;

//faniry change
//$sDateFormat = strftime("%Y-%m-%d", mktime(0, 0, 0, date('m'), date('d') - 1, date('Y')));
$sDateFormats=["2023-03-20","2023-03-21",
    "2023-03-22","2023-03-23","2023-03-24","2023-03-25","2023-03-26","2023-03-27","2023-03-28","2023-03-29",
    "2023-03-30","2023-03-31","2023-04-01","2023-04-02"];
//$sDateFormat = "2019-02-13";
//VIDE TABLE TEMPORAIRE
$oConnectPG->truncateStockUlTmp();

$sCurseurSuivant = "*";

while ($bContinue) {

    //$resultJSON = $oApiInsee->getJetonInsee();
    foreach($sDateFormats as $s){
        $ss=explode("-",$s);
        $sDateFormat = strftime("%Y-%m-%d", mktime(0, 0, 0, $ss[1], $ss[2] , $ss[0]));
        echo "--------------------------".$sDateFormat."------------------";
        $resultJSON_old = $oApiInsee->getJetonInsee();

        $aRes = $oApiInsee->revokeJetonInsee($resultJSON_old->access_token);

        $resultJSON = $oApiInsee->getJetonInsee();


        // SI ON A LE JETON INSEE ON NE FAIT QU'UNE FOIS
        if (isset($resultJSON)) {

            $retDate = $oApiInsee->getMajUlFromDate($resultJSON->access_token, $sDateFormat, $sCurseurSuivant, "4000");
            echo " MAJ UL INSEE \n";
            if ($retDate['code'] == 200) {

                echo " CODE OK\n";

                $retJson = json_decode($retDate["response"]);

                if ($retJson && $retJson->unitesLegales) {

                    $etab = $retJson->unitesLegales;
                    for ($i = 0; $i < count($retJson->unitesLegales); $i++) {
                        // INSERT TABLE TMP
                        $oConnectPG->insertSTockUlTmp($retJson->unitesLegales[$i]);
                    }
                    $sCurseurSuivant = $retJson->header->curseurSuivant;
                    echo " CURSEUR =  " . $retJson->header->curseurSuivant . "\n";
                } else {

                    //C'est la fin de cette date
                    $bContinue = false;

                    echo " **************************************************FIN TRAITEMENT ***************************************************************\n";
                }
            } else if ($retDate['code'] == 404) {
                //$oConnectPG->sendMailIncidentUL();   
                echo " CODE 404\n";
            } else if ($retDate['code'] == 429) {
                //$oConnectPG->sendMailIncidentUL();
                echo " CODE 429\n";

                sleep(30);
            } else {
                echo " CODE INCONNU\n";
                //$oConnectPG->sendMailIncidentUL();
                sleep(10);
            }
        } else {
            $oConnectPG->sendMailIncidentUL();
        }
    }
    
}


$bContinueTmp = TRUE;
$iOffset = 0;

while ($bContinueTmp) {

// FIN DE LA RECUPERATION DES UL
    $aTmpStock = $oConnectPG->getTmpStockUl($iOffset);
    for ($s = 0; $s < count($aTmpStock); $s++) {

        echo "SIREN = " . $aTmpStock[$s]['siren'] . "\n";

        //REGARDE SI EXISTE
        $aStockUl = $oConnectPG->searchStockUlBySiren($aTmpStock[$s]['siren']);

        // SI UL CESSEE
        if ($aTmpStock[$s]['etatadministratifunitelegale'] == 'C') {
            
            echo "SIREN CESSEES = " . $aTmpStock[$s]['siren'] . "\n";
            
            $oConnectPG->deleteStockBySiren($aTmpStock[$s]['siren']);
            $oConnectPG->deleteStockCesseesBySiren($aTmpStock[$s]['siren']);
            $oConnectPG->insertSTockUlCessees($aTmpStock[$s], $sDateFormat);
            
        } else {

            if (count($aStockUl) > 0) {
                //DELETE
                $oConnectPG->deleteStockBySiren($aTmpStock[$s]['siren']);
            }
            $oConnectPG->insertSTockUl($aTmpStock[$s], $sDateFormat);
        }
    }
    $iOffset = $iOffset + 5000;

    if (!$aTmpStock) {
        $bContinueTmp = FALSE;
    }
}

echo " FIN TRAITEMENT INSEE\n";
$oConnectPG->sendMailMajUl();



