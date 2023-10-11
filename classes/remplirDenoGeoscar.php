<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once 'connectPostgreSql.php';
date_default_timezone_set("Europe/Paris");
ini_set('max_execution_time', 0);
ini_set('memory_limit', '-1');
date_default_timezone_set('UTC');

class RemplirDenoGeoscar {

    public function traitementDeno() {

        $oConnectPG = new connectPostreSql();



        $iOffset = 0;

        $bContinue = TRUE;



        while ($bContinue) {

            $aTabGeoscar = $oConnectPG->getGeoscarSansDenomination($iOffset);
            
        

            for ($i = 0; $i < count($aTabGeoscar); $i++) {


                $denominationGeoscar = "";
                //SI denominationusuelleetablissement is null et enseigne1etablissement is null
                if (!$aTabGeoscar[$i]['denominationusuelleetablissement'] && !$aTabGeoscar[$i]['enseigne1etablissement']) {
                    
                    echo "PAS DE DENO ".$aTabGeoscar[$i]['denominationusuelleetablissement']."" .$aTabGeoscar[$i]['enseigne1etablissement']." \n";

                    //on cherche dans le stock_ul
                    $tabUl = $oConnectPG->getUlBiSIren($aTabGeoscar[$i]['siren']);
                    
                    
                    
                    echo "COUNT STOCK UL ".count($tabUl)."\n";
                    
                    if (count($tabUl)>0) {
                        
                       
                        $denominationGeoscar = $tabUl[0]['denominationunitelegale'] ? $tabUl[0]['denominationunitelegale'] : $tabUl[0]['prenom1unitelegale'] . ' ' . $tabUl[0]['nomunitelegale'];
                        echo "TROUVE STOCK UL $denominationGeoscar\n";
                        //on cherche dans le stock_ul_cesses
                    } else {
                        
                        
                        $tabUlCessee = $oConnectPG->getUlCesseeBiSIren($aTabGeoscar[$i]['siren']);
                        
                         echo "COUNT STOCK UL CC ".count($tabUlCessee)."\n";
                        
                        if (count($tabUlCessee)>0) {
                            
                            echo "TROUVE STOCK UL CESSEES $denominationGeoscar\n";

                            $denominationGeoscar = $tabUlCessee[0]['denominationunitelegale'] ? $tabUlCessee[0]['denominationunitelegale'] : $tabUlCessee[0]['prenom1unitelegale'] . ' ' . $tabUlCessee[0]['nomunitelegale'];
                        }
                    }
                } else {
                    
                    

                    $denominationGeoscar = $aTabGeoscar[$i]['denominationusuelleetablissement'] ? $aTabGeoscar[$i]['denominationusuelleetablissement'] : $aTabGeoscar[$i]['enseigne1etablissement'];
                    
                    
                    
                    echo "TROUVE DANS GEOSCAR  $denominationGeoscar\n";
                }

                echo "DENOMINATION = " . $denominationGeoscar . "\n";
                echo "SIRET = " . $aTabGeoscar[$i]['siret'] . "\n";
                echo "SIREN = " . $aTabGeoscar[$i]['siren'] . "\n";


                $oConnectPG->updateDenominationGeoscar($denominationGeoscar, $aTabGeoscar[$i]['siret']);
                $oConnectPG->updateDenominationGeoscarStock($denominationGeoscar, $aTabGeoscar[$i]['siret']);
            }
            echo "COUNT = " . count($aTabGeoscar) . "\n";
            //$iOffset = $iOffset + 5000;

            if (count($aTabGeoscar) < 5000) {
                $bContinue = FALSE;
                echo "*********************FIN TRAIMEMENT*********************************************\n";
            }
        }
        
        
        $oConnectPG->sendMailFinDenoGeoscar();
    }

}
