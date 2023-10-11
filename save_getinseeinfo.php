<!doctype html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport"
              content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title>GET INSEE INFOS</title>
    </head>
    <body>


<?php
include 'classes/apiInsee.php';
include 'classes/apiBano.php';
include 'classes/connectPostgreSql.php';
include 'classes/etablissementInsee.php';
var_dump(debug_backtrace());

$oApiBano = new apiBano();
$oApiInsee = new apiInsee();
$oConnectPG = new connectPostreSql();
$oUtil = new Util();

date_default_timezone_set("Europe/Paris");
ini_set('max_execution_time', 0);
ini_set('memory_limit', '-1');

$continueScrapDate = true;



        

if (isset($_GET['token'])) {


    $sDateFormat = "2018-11-21";
    // ["statut"]=> int(200) ["message"]=> string(2) "OK" ["total"]=> int(16331) ["debut"]=> int(0) ["nombre"]=> int(100) 
    $sCurseur = "";
    $sCurseurSuivant = "*";
    $now = new \DateTime();


    $rowScrap = 0;
echo " --------- DEBUT-----------------".$now->format('d-m-Y G:i:s')."<br>";
    while ($continueScrapDate) {


        //ON chope les données de l'API INSEE
        $retDate = $oApiInsee->getInfosFromDate($_GET['token'], $sDateFormat, $sCurseurSuivant, "4000");
        
        $now = new \DateTime();
        echo " --------- APRES INSEE-----------------".$now->format('d-m-Y G:i:s')."<br>";
        echo "COUNT TOTAL = ".count($retDate["response"])."<br><br>";

        if ($retDate['code'] == 200) {

            $retJson = json_decode($retDate["response"]);

                   
            if ($retJson && $retJson->etablissements) {

                echo "<script>console.log( 'resultat' );</script>";
                $etab = $retJson->etablissements;
                $rowScrap += count($etab);

                for ($i = 0; $i < count($retJson->etablissements); $i++) {
                    echo "boucle = " . $i . "";
                    echo "count" . (count($retJson->etablissements[0])) . '<br>';
                    

                    // ON REGARDE SI ON A DEJA L ETAB DANS LE STOCK
                    $aStock = $oConnectPG->getStocksBySiret($retJson->etablissements[$i]->siret);
                    $now = new \DateTime();
                    echo " ---------  APRES getStocksBySiret-----------------".$now->format('d-m-Y G:i:s')."<br>";

                    if (count($aStock) > 0) {
                        // COMPARAISON DES MODIFICATIONS
                        $aArrayModifs = $oUtil->compareModifsEtablissement($retJson->etablissements[$i], $aStock);
                        $now = new \DateTime();
                        echo " ---------  APRES compareModifsEtablissement-----------------".$now->format('d-m-Y G:i:s')."<br>";
                        
                         
                        // S'IL Y A DES MODIFS ON ENVOIE A BANO
                        if (count($aArrayModifs)>0) {
                            $bModif = TRUE;
                            //$oConnectPG->ajoutFichierPourBanoLight($retJson->etablissements[$i]);
                        }
                        $bEstPresent = TRUE;
                        // CHERHCER ENTREE DANS LA DIFF COMMERCIALE
                        //$bDiffComm = $oConnectPG->isDiffusionCommercialeDiff($retJson->etablissements[$i]->siret, $retJson->etablissements[$i]->statutDiffusionEtablissement);
                        //SI OUI ON AJOUTE AU FICHIER POUR BANO ET ON UPDATE
                        echo '------------------UPDATE  = ' . $retJson->etablissements[$i]->siret . "<br>";
                    } else {
                        //ON AJOUTE AU FICHIER POUR BANO
                        echo '------------------INSERT  = ' . $retJson->etablissements[$i]->siret . "<br>";
                        //$oConnectPG->insertStock($resultJSONLastMAJ->etablissements[$i]);
                        // ON NE PREND QUE LES ACTIVITES = NAFRev2 ET PAS LES SIEGES
                        if ($retJson->etablissements[$i]->periodesEtablissement[0]->nomenclatureActivitePrincipaleEtablissement === "NAFRev2") {

                            echo "<script>console.log( 'insertion' );</script>";
                             
                            //echo "DATE = ".$resultJSONLastMAJ->etablissements[$i]->dateDernierTraitementEtablissement.'<br>';
                            $oConnectPG->insertStock($retJson->etablissements[$i]);
                            $now = new \DateTime();
                            echo " ---------  APRES insertStock-----------------".$now->format('d-m-Y G:i:s')."<br>";
                            $bModif = TRUE;
                            //$oConnectPG->ajoutFichierPourBanoLight($retJson->etablissements[$i]);
                           
                        }
                    }

                    // AJOUT POUR ABO
                    $oConnectPG->ajoutFichierPourBanoLight($retJson->etablissements[$i]);
                    echo " ---------  APRES ajoutFichierPourBanoLight-----------------".$now->format('d-m-Y G:i:s')."<br>";
                    
                    // ON INSERT DANS GEOSIRENE
                    $oConnectPG->insertGeosirene($retJson->etablissements[$i], $aArrayModifs);
                    echo " ---------  APRES insertGeosirene-----------------".$now->format('d-m-Y G:i:s')."<br>";
                    
                    
                    $oApiBano->createFichierGeocode();
                    echo " ---------  APRES createFichierGeocode-----------------".$now->format('d-m-Y G:i:s')."<br>";
//                    echo '<pre>';
//                    var_dump($retJson->etablissements[0]);
//                    echo '</pre>';
                   
                    $sCurseurSuivant = $retJson->header->curseurSuivant;
                    // die();
                }
                //if ($bModif) {
//                    $oApiBano->createFichierGeocode();
//                    $oConnectPG->updateGeosireneBano();
              $oConnectPG->updateGeosireneBano();
              echo " ---------  APRES updateGeosireneBano-----------------".$now->format('d-m-Y G:i:s')."<br>";           
                    
                    //}
            } else {
                //C'est la fin de cette date
                $continueScrapDate = false;
            }
        } else if ($retDate['code'] == 404) {

            echo('<error> CODE:' . $retDate['code'] . ' - REPONSE vide pour le : ' . $sDateFormat . ' cursor: ' . $sCurseurSuivant . ' --- Pause 30 secondes</error>');

            $continueScrapDate = false;
        } else if ($retDate['code'] == 429) {

            echo('<error> CODE:' . $retDate['code'] . ' - Erreur TIMEOUT pour le : ' . $sDateFormat . ' cursor: ' . $sCurseurSuivant . ' --- Pause 30 secondes</error>');
            sleep(30);
        } else {

            echo('<error> CODE:' . $retDate['code'] . ' - Erreur AUTRE pour le : ' . $sDateFormat . ' cursor: ' . $sCurseurSuivant . ' ---</error>');
            sleep(10);
            //var_dump($retJson);
        }

        sleep(5);
    }















            /*             * ******************************************************************************************************************************************** 





              //for ($a = strtotime('2018-10-23'); $a < strtotime('2018-11-14'); $a = strtotime(date('Y-m-d', $a) . ' +1 day')) {
              //}
              //$sDateFormat = date('Y-m-d', $a);
              //echo "///////////////////////DATE  = ".$sDateFormat."//////////////////////////////////////////////////<br>";
              $token = $_GET['token'];
              //$siret = $_GET['siret'];
              //ON RECUPERE LA DERNIERE DATE DE MISE A JOUR
              //$resultInfo = $oApiInsee->sendRequestDatemAJ($token);
              //$sDateDernièreMaj = $resultInfo->datesDernieresMisesAJourDesDonnees[0]->dateDerniereMiseADisposition;
              // ON FORMATE LA DATE
              //$sDateFormat = $oApiInsee->getFormatDate($sDateDernièreMaj);
              //echo "date format = ".var_dump($sDateFormat)."<br>";
              // ON RECPERE LES ETABLISSEMENTS MODIFIES
              $sDateFormat = "2018-10-25";
              // ["statut"]=> int(200) ["message"]=> string(2) "OK" ["total"]=> int(16331) ["debut"]=> int(0) ["nombre"]=> int(100)
              $sCurseur = "";
              $sCurseurSuivant = "*";

              //$sFichierSortie = "C:/insee_file/SIRENE/SIRENE_reponse.csv";
              ///do {
              echo "---------------------------------------TIMER avant sendRequestLastMaj  = " . date("H:i:s") . "---------------------------------------<br>";
              $resultJSONLastMAJ = $oApiInsee->sendRequestLastMaj($token, $sDateFormat, $sCurseurSuivant);
              echo "---------------------------------------TIMER apres sendRequestLastMaj = " . date("H:i:s") . "---------------------------------------<br>";

              //                 echo "<pre>";
              //                        var_dump($resultJSONLastMAJ);
              //                        echo "</pre>";
              //                        die();

              if ($resultJSONLastMAJ->header->statut == 200) {

              $sCurseur = $resultJSONLastMAJ->header->curseur;
              $sCurseurSuivant = $resultJSONLastMAJ->header->curseurSuivant;
              $iNbEtabResult = $resultJSONLastMAJ->header->total;
              $iNbEnregDemandes = $resultJSONLastMAJ->header->nombre;
              // On cherche le nombre de boucles à faire
              $iNbLoop = ceil($iNbEtabResult / $iNbEnregDemandes);

              echo "iNbLoop = " . $iNbLoop . "<br>";

              echo "TOTAL  =  " . $iNbEtabResult;
              //file_put_contents($sFichierSortie, $resultJSONLastMAJ , FILE_APPEND  | LOCK_EX);

              echo '------------AVAVNT LOOP CURSEUR = ' . $sCurseur . " ////// CURSEUR SUIVANT = " . $sCurseurSuivant . "<br>";
              //echo "SIRENE  = " . $resultJSONLastMAJ->etablissements[$i]->siret . "<br>";

              $bEstTraite = true;






              for ($p = 0; $p < $iNbLoop; $p++) {

              if (!$bEstTraite) {
              $p--;
              }

              $bEstTraite = false;
              echo "---------------------------------------TIMER = " . date("H:i:s") . "---------------------------------------<br>";

              $res = $oApiInsee->sendRequestLastMaj($token, $sDateFormat, $sCurseurSuivant);

              if ($res != null) {

              $bEstTraite = true;

              echo "****************************************************BOUCLE = " . $p . "*****STATUT OK***************************************************<br>";
              //echo "-----------------APRS COUNT =  " . count($res);
              $sCurseur = $res->header->curseur;
              $sCurseurSuivant = $res->header->curseurSuivant;
              echo '--------------APRS LOOP CURSEUR = ' . $sCurseur . " ////// CURSEUR SUIVANT = " . $sCurseurSuivant . "<br>";
              sleep(3);

              $bModif = FALSE;

              for ($i = 0; $i < count($res->etablissements); $i++) {

              // ON REGARDE SI ON A DEJA L ETAB DANS LE STOCK
              $bEstPresent = $oConnectPG->etablissementPresent($res->etablissements[$i]->siret);

              if ($bEstPresent) {
              // CHERHCER ENTREE DANS LA DIFF COMMERCIALE
              $bDiffComm = $oConnectPG->isDiffusionCommercialeDiff($res->etablissements[$i]->siret, $res->etablissements[$i]->statutDiffusionEtablissement);

              //SI OUI ON AJOUTE AU FICHIER POUR BANO
              if ($bDiffComm) {
              $bModif = TRUE;
              echo '----------------- UPDATE  = ' . $res->etablissements[$i]->siret . "<br>";
              //$oConnectPG->ajoutFichierPourBano($res->etablissements[$i]);
              }
              } else {
              //ON AJOUTE AU FICHIER POUR BANO
              echo '------------------INSERT  = ' . $res->etablissements[$i]->siret . "<br>";
              //$oConnectPG->insertStock($resultJSONLastMAJ->etablissements[$i]);
              // ON NE PREND QUE LES ACTIVITES = NAFRev2 ET PAS LES SIEGES
              if ($res->etablissements[$i]->periodesEtablissement[0]->nomenclatureActivitePrincipaleEtablissement === "NAFRev2") {
              //echo "DATE = ".$resultJSONLastMAJ->etablissements[$i]->dateDernierTraitementEtablissement.'<br>';
              $oConnectPG->insertStock($res->etablissements[$i]);
              $bModif = TRUE;
              //$oConnectPG->ajoutFichierPourBano($res->etablissements[$i]);
              }
              }
              }
              if ($bModif) {
              //$oApiBano->createFichierGeocode();
              }
              } else {
              echo "Aucune réponse";
              //$p=$p;
              }
              }
              }
              //} */
        } else {


            die('Manque des infos');
        }
        ?>



    </body>
</html>