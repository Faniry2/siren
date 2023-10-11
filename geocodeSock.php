<?php

include 'classes/apiInsee.php';
include 'classes/apiBano.php';
include 'classes/connectPostgreSql.php';
include 'classes/ConnectGeocube.php';
include 'classes/geosireneTraitement.php';
include 'classes/BddBilan.php';

//$debug = FALSE;


$oApiBano = new apiBano();
$oApiInsee = new apiInsee();
$oConnectPG = new connectPostreSql();
$oGeocube = new ConnectGeocube();
$oUtil = new Util();
$oGeosireneTraitement = new geosireneTraitement();
$oBddBilan = new BddBilan();

date_default_timezone_set("Europe/Paris");
ini_set('max_execution_time', 0);
ini_set('memory_limit', '-1');

date_default_timezone_set('UTC');

$iOffset = 0;
$bContinue = TRUE;


while ($bContinue) {



    $aStockBPEMD = $oConnectPG->getGeosireneBpmed($iOffset);

    for ($t = 0; $t < count($aStockBPEMD); $t++) {

        //$aBilan = $oConnectPG->getBilanBySiren($aStockBPEMD[$t]['siren']);
        //if (count($aBilan) > 0) {
        //print_r($aBilan);
        //echo "\n\ncount bilan = " . count($aBilan) . "\n";
        echo "\n\siren = " . $aStockBPEMD[$t]['siren'] . "\n";




        $aBilanLiasse = $oBddBilan->getBilanLiasseBySiren($aStockBPEMD[$t]['siren']);
        //$aBilanLiasse = $oBddBilan->getBilanLiasseBySiren($aStockBPEMD[$t]['siren']);

        if (count($aBilanLiasse) > 0) {




            for ($o = 0; $o < count($aBilanLiasse); $o++) {


                //print_r($aBilanLiasse);
                echo "------------------------bilan liasse " . $o . "\n";
                echo "\n\ncount liasse = " . count($aBilanLiasse) . "\n";




                $aIntituleLiasse = $oBddBilan->getIntituleLiasse($aBilanLiasse[$o]['liasse_code']);

                for ($p = 0; $p < count($aIntituleLiasse); $p++) {



                    //print_r($aIntituleLiasse);
                    echo "------------------------intitule liasse " . $p . "\n";
                    echo "\n\n inti liasse \n\n";
                    echo "\n\ncount intitule liasse = " . count($aIntituleLiasse) . "\n";


                    $aInfosLiasse = $oBddBilan->getInfosLiasse($aBilanLiasse[$o]['page_numero'], $aBilanLiasse[$o]['liasse_code']);
                    //print_r($aInfosLiasse);
                    echo "\n\n infos liasse \n\n";
                    echo "\n\ncount info liasse = " . count($aInfosLiasse) . "\n\n";


                    $sLigne = "";
                    if ($aBilanLiasse[$o]['liasse_code'] == 'FJ') {

                        $sLigne = $aBilanLiasse[$o]['siren'] . ", " . $aBilanLiasse[$o]['date_cloture_exercice'] . ", " . $aBilanLiasse[$o]['page_numero'] . ", " . $aBilanLiasse[$o]['liasse_code'] . ", " . $aIntituleLiasse[$p]['intitule_liasse'] . ",";
                        $sLigne .= $aIntituleLiasse[$p]['sous_type'] . ", " . Util::getTypeBilan($aIntituleLiasse[$p]['code_type_bilan']);
                       
                        if (strlen($aBilanLiasse[$o]['m3'])==0) {
                            $sLigne .= ",  " . $aInfosLiasse[0]['m4'] . ", " . $aBilanLiasse[$o]['m4'] . ";";
                        } else {
                            $sLigne .= ",  " . $aInfosLiasse[0]['m3'] . ", " . $aBilanLiasse[$o]['m3'] . ";";
                        }
                        
                    } else {

                        $sLigne = $aBilanLiasse[$o]['siren'] . ", " . $aBilanLiasse[$o]['date_cloture_exercice'] . ", " . $aBilanLiasse[$o]['page_numero'] . ", " . $aBilanLiasse[$o]['liasse_code'] . ", " . $aIntituleLiasse[$p]['intitule_liasse'] . ",";
                        $sLigne .= $aIntituleLiasse[$p]['sous_type'] . ", " . Util::getTypeBilan($aIntituleLiasse[$p]['code_type_bilan']);
                        $sLigne .= ", " . $aInfosLiasse[0]['m1'] . ", " . $aBilanLiasse[$o]['m1'] . ";";
                    }





                    $sFile = "C:\\Users\\sleco\\Documents\\BPMED\\bilan_ca_BPMED.csv";
                    $fp = fopen($sFile, 'a+');
                    if ($fp) {
                        fwrite($fp, $sLigne);
                        fwrite($fp, "\n");
                    }
                }
                
            }
        }
       
    }
    /* if ($iOffset>1000) {
      die();
      } */

    $iOffset = $iOffset + 5000;
    if (count($aStockBPEMD) == 0) {
        $bContinue = false;
        echo "********************************FIN TRAITEMENT ****************************************************** \n";
    }
}












die();
/* * ****************************************************************************************************************************************************************** */
$iOffset = 0;

$bContinue = TRUE;

$iNbLigne = 0;

while ($bContinue) {

    unlink(FILE_RESULT_POUR_BANO);
    unlink(FILE_SORTIE_BANO);

    $aTabStock = $oConnectPG->getStockToGeocode($iOffset);
    echo "count =" . count($aTabStock) . "\n";
    echo"offset = " . $iOffset . "\n";

    $bInsert = FALSE;
    $bUpdate = FALSE;

    for ($i = 0; $i < count($aTabStock); $i++) {


        $aSireneN0 = $oGeocube->getSirenN0BySiret($aTabStock[$i]['siret']);

        if (count($aSireneN0) == 0) {

            $bInsert = TRUE;
            echo "pas trouvé dans sirene_n0 \n";
            // AJOUT FICHIER POUR BANO
            $oConnectPG->ajoutFichierPourBanoLightStock($aTabStock[$i]);
        } else {
            $bModif = $oUtil->compareModifsEtablissementStock($aSireneN0[0], $aTabStock[$i]);

            if ($bModif) {
                echo "adresse modifiée \n";
                $bUpdate = TRUE;
                // AJOUT FICHIER POUR BANO
                $oConnectPG->ajoutFichierPourBanoLightStock($aTabStock[$i]);
            }
        }

        $sFile = "C:\\Users\\sleco\\Documents\\GEOSIRENE\\log_goecode.csv";
        $fp = fopen($sFile, 'a+');
        if ($fp) {
            fwrite($fp, "offset = " . $iOffset);
            fwrite($fp, "\n");
            fwrite($fp, "siret = " . $aTabStock[$i]['siret']);
            fwrite($fp, "\n");
            fwrite($fp, "count =" . count($aTabStock));
            fwrite($fp, "\n");
            fwrite($fp, "Update =" . $bInsert);
            fwrite($fp, "\n");
            fwrite($fp, "Insert =" . $bUpdate);
            fwrite($fp, "\n");
            fwrite($fp, "ligne =" . $iNbLigne);
            fwrite($fp, "\n");
            fwrite($fp, "continue =" . $bContinue);
            fwrite($fp, "\n");
            fwrite($fp, "*****************************************************");
            fwrite($fp, "\n");
        }

        $iNbLigne++;
    }
    if (count($aSireneN0) > 0) {
        $oApiBano->createFichierGeocode();
        $oConnectPG->updateGeosireneBanoStock();
    }



    $iOffset = $iOffset + 4000;
    if (count($aTabStock) == 0) {
        $bContinue = FALSE;
    }

    /* if ($iOffset > 1600000) {
      $bContinue = FALSE;
      } */
}
/* * ********************************************************************************************************************************************** */
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
