<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
ini_set('memory_limit', '-1');
set_time_limit(0);

//require '../vendor/autoload.php';
//require_once 'C:\Users\sleco\vendor\autoload.php';
//use JsonMachine\JsonMachine;

class geosireneTraitement {

    public function etape1($resultJSON, $sDateFormat) {

        $oApiBano = new apiBano();
        $oApiInsee = new apiInsee();
        $oConnectPG = new connectPostreSql();
        $oUtil = new Util();
        $continueScrapDate = true;

        if (isset($resultJSON)) {

            $sCurseur = "";
            $sCurseurSuivant = "*";
            $now1 = new \DateTime();
            $rowScrap = 0;
            
            // NETTOYAGE TABLE TMP
            $oConnectPG->trucateTmp();

            while ($continueScrapDate) {

                //ON chope les données de l'API INSEE
                $retDate = $oApiInsee->getInfosFromDate($resultJSON->access_token, $sDateFormat, $sCurseurSuivant, "4000");

                if ($retDate['code'] == 200) {

                    Util::logMajGeosirene("Données INSEE OK  ");
                    $retJson = json_decode($retDate["response"]);




                    if ($retJson && $retJson->etablissements) {

                        $etab = $retJson->etablissements;
                        $rowScrap += count($etab);

                        for ($i = 0; $i < count($retJson->etablissements); $i++) {
                            // INSERT TABLE TMP
                            $oConnectPG->insertTmpStock($retJson->etablissements[$i]);
                            // AJOUT POUR ABO
                            $oConnectPG->ajoutFichierPourBanoLight($retJson->etablissements[$i]);
                        }


                        $sCurseurSuivant = $retJson->header->curseurSuivant;
                    } else {
                        //C'est la fin de cette date
                        $continueScrapDate = false;
                    }
                } else if ($retDate['code'] == 404) {

                    Util::logMajGeosirene("Données INSEE NOK 404 code = " . $retDate['code']);
                    $oConnectPG->sendMailIncident();
                    echo('<error> CODE:' . $retDate['code'] . ' - REPONSE vide pour le : ' . $sDateFormat . ' cursor: ' . $sCurseurSuivant . ' --- Pause 30 secondes</error>');

                    $continueScrapDate = false;
                } else if ($retDate['code'] == 429) {
                    $oConnectPG->sendMailIncident();
                    Util::logMajGeosirene("Données INSEE NOK 429 code = " . $retDate['code']);
                    echo('<error> CODE:' . $retDate['code'] . ' - Erreur TIMEOUT pour le : ' . $sDateFormat . ' cursor: ' . $sCurseurSuivant . ' --- Pause 30 secondes</error>');
                    sleep(30);
                } else {
                    $oConnectPG->sendMailIncident();
                    Util::logMajGeosirene("Données INSEE NOK autre code = " . $retDate['code']);
                    echo('<error> CODE:' . $retDate['code'] . ' - Erreur AUTRE pour le : ' . $sDateFormat . ' cursor: ' . $sCurseurSuivant . ' ---</error>');
                    sleep(10);
                    //var_dump($retJson);
                }

                sleep(5);
            }
        } else {
            die('PAS DE JETON INSEE');
        }
    }

    public function etape1JsonMachine($resultJSON, $sDateFormat) {


        ini_set('memory_limit', '-1');
        $oApiInsee = new apiInsee();
        $oConnectPG = new connectPostreSql();
        $oGeocube = new ConnectGeocube();
        $iCountIncident = 0;
        $continueScrapDate = true;

        if (isset($resultJSON)) {
            $sCurseurSuivant = "*";
            $_GLOBAL['curseur'] = $sCurseurSuivant;
            $rowScrap = 0;

            // NETTOYAGE TABLE TMP
            $oConnectPG->trucateTmp();

            while ($continueScrapDate) {

                if ($iCountIncident > 9) {
                    $oConnectPG->sendMailIncident();
                    $this->sendMailPbInsee($sDateFormat);
                    die("***************************PROBLEME INSEE*************************************");
                }
                unset($retDate);
                //ON chope les données de l'API INSEE
                $retDate = $oApiInsee->getInfosFromDate($resultJSON->access_token, $sDateFormat, $_GLOBAL['curseur'], 40000);

                if ($retDate['code'] == 200) {

                    Util::logMajGeosirene("Données INSEE OK  ");


                    gc_enable();

                    //$retJson = json_decode($retDate["response"]);
                    $retJson = \JsonMachine\JsonMachine::fromString($retDate["response"]);



                    $memory = memory_get_usage(true);

                    echo "*********************MEMORY " . $memory . "\n";


                    if ($retJson) {




                        foreach ($retJson as $name => $data) {


                            $aEtab = array();
                            $aHeaders = array();

                            if ($name == 'header') {
                                $aHeaders = $data;

                                if ($aHeaders['curseurSuivant']) {
                                    $_GLOBAL['curseur'] = $aHeaders['curseurSuivant'];
                                    echo "-----------------------------CHANGE CURSEUR  " . $aHeaders['curseurSuivant'] . "\n";
                                }
                            }
                            if ($name == 'etablissements') {

                                $rowScrap += count($aEtab);
                            }

                            $aEtab = $data;
//                                echo '<pre>';
//                                var_dump($aHeaders);
//                                echo '</pre>';
                            //die();

                            if ($aEtab) {

                                for ($mm = 0; $mm < count($aEtab); $mm++) {
                                    $oConnectPG->insertTmpStockArray($aEtab[$mm], $sDateFormat);
                                }
                            }


                            $aTmp = $oConnectPG->getTmpStock();

                            if (count($aTmp) == $aHeaders['total']) {
                                $continueScrapDate = false;
                            }
//                            else {
//                                echo "-----------------------------CHANGE CURSEUR  ".$aHeaders['curseurSuivant']."\n";
//                                $_GLOBAL['curseur'] = $aHeaders['curseurSuivant'];
//                                
//                            }
                        }
                    } else {
                        //C'est la fin de cette date
                        $continueScrapDate = false;
                    }

                    gc_collect_cycles();
                }

                sleep(5);
            }
        } else {
            die('PAS DE JETON INSEE');
        }
    }

    public function etape1Bis($resultJSON, $sDateFormat) {


        ini_set('memory_limit', '-1');


        $oApiInsee = new apiInsee();
        $oConnectPG = new connectPostreSql();
        $oGeocube = new ConnectGeocube();
        $iCountIncident = 0;
        $continueScrapDate = true;

        if (isset($resultJSON)) {
            $sCurseurSuivant = "*";

            $rowScrap = 0;

            // NETTOYAGE TABLE TMP
            $oConnectPG->trucateTmp();

            while ($continueScrapDate) {

                if ($iCountIncident > 9) {


                    $oConnectPG->sendMailIncident();
                    //$this->sendMailPbInsee($sDateFormat);


//                    $tab_jours = array(7, 1, 2, 3, 4, 5, 6);
//                    $inumJour = $tab_jours[date('w', mktime(0, 0, 0, date('m'), date('d'), date('Y')))];
//                    if ($inumJour == 1) {
//
//                        $sDateJour = strftime("%d-%m-%Y", mktime(0, 0, 0, date('m'), date('d'), date('Y')));
//                        $sDateSemDern = strftime("%d-%m-%Y", mktime(0, 0, 0, date('m'), date('d') - 8, date('Y')));
//                        $this->sendMailAlerteHebdo($sDateFormat, $sDateJour, $sDateSemDern);
//                    }

                    die("***************************PROBLEME INSEE*************************************");
                }

                //ON chope les données de l'API INSEE
                $retDate = $oApiInsee->getInfosFromDate($sDateFormat, $sCurseurSuivant, "4000");
                //var_dump($retDate);
                if ($retDate['code'] == 200) {

                    //Util::logMajGeosirene("Données INSEE OK  ");

                    $retJson = json_decode($retDate["response"]);

                    //Util::logMajGeosirene("Nombre établissements INSEE " . $retJson->header->total);

                    if ($retJson && $retJson->etablissements) {

                        $etab = $retJson->etablissements;
                        $rowScrap += count($etab);

                        for ($i = 0; $i < count($retJson->etablissements); $i++) {
                            // INSERT TABLE TMP
                            $oConnectPG->insertTmpStock($retJson->etablissements[$i], $sDateFormat);
                            //Util::logMajGeosirene("INSEE " . $retJson->etablissements[$i]->siret);
                        }
                        $aTmp = $oConnectPG->getTmpStock();

                        if (count($aTmp) == $retJson->header->total) {
                            $continueScrapDate = false;
                        } else {
                            $sCurseurSuivant = $retJson->header->curseurSuivant;
                        }
                    } else {
                        //C'est la fin de cette date
                        $continueScrapDate = false;
                    }
                } else if ($retDate['code'] == 404) {
                    $iCountIncident ++;
                    //Util::logMajGeosirene("Données INSEE NOK 404 code = " . $retDate['code']);

                    echo('<error> CODE:' . $retDate['code'] . ' - REPONSE vide pour le : ' . $sDateFormat . ' cursor: ' . $sCurseurSuivant . ' --- Pause 30 secondes</error>');

                    $continueScrapDate = true;
                    sleep(10);
                } else if ($retDate['code'] == 429) {
                    $iCountIncident ++;
                    //$oConnectPG->sendMailIncident();
                    //Util::logMajGeosirene("Données INSEE NOK 429 code = " . $retDate['code']);
                    echo('<error> CODE:' . $retDate['code'] . ' - Erreur TIMEOUT pour le : ' . $sDateFormat . ' cursor: ' . $sCurseurSuivant . ' --- Pause 30 secondes</error>');
                    $continueScrapDate = true;
                    sleep(30);
                } else {
                    $iCountIncident ++;
                    //$oConnectPG->sendMailIncident();
                    //Util::logMajGeosirene("Données INSEE NOK autre code = " . $retDate['code']);
                    echo('<error> CODE:' . $retDate['code'] . ' - Erreur AUTRE pour le : ' . $sDateFormat . ' cursor: ' . $sCurseurSuivant . ' ---</error>');
                    $continueScrapDate = true;
                    sleep(10);
                    //var_dump($retJson);
                }

                sleep(5);
            }
        } else {
            die('PAS DE JETON INSEE');
        }
    }

    public function etape2($sDateFormat, $numfic) {

        $oApiBano = new apiBano();
        $oApiInsee = new apiInsee();
        $oConnectPG = new connectPostreSql();
        $oUtil = new Util();

        $aTmpStock = $oConnectPG->getTmpStock();


        $inbCreation = 0;

        $inbUdpate = 0;


        foreach ($aTmpStock as $key => $value) {

            $bCreation = 'TRUE';

            $aArrayModifs = array();
            echo "-------------------------------------------" . $aTmpStock[$key]['siret'] . "----\n\n";

            echo "---------------------------KEY =>----------------" . $key . "----\n\n";

            // ON REGARDE SI ON A DEJA L ETAB DANS LE STOCK
            $aStock = $oConnectPG->getStocksBySiret($aTmpStock[$key]['siret']);
            echo "-------------------------getStocksBySiret---------------------\n\n";

            if (count($aStock) > 0) {

                $bCreation = 'FALSE';
                // COMPARAISON DES MODIFICATIONS
                $aArrayModifs = $oUtil->compareModifsEtablissement($aTmpStock[$key], $aStock);
                if (count($aArrayModifs) > 0) {
                    echo "-------------------------compareModifsEtablissement---------------------\n\n";
                    $inbUdpate++;
                    $this->insertDebug($aTmpStock[$key]['siret'], 'FALSE', 'TRUE', $sDateFormat);
                    // UPDATE STOCK
                    $oConnectPG->updateStock($aTmpStock[$key], $sDateFormat);
                }
            } else {
                $bCreation = 'TRUE';
            }


            // ON INSERT LES MODIFS DE LA JOURNEE
            $oConnectPG->insertGeosirene($aTmpStock[$key], $aArrayModifs, $numfic, $sDateFormat, $bCreation);

            // GESTION DE GEOSIRENE STOCK
            $aSerachGeoStock = $oConnectPG->searchGeosireneStock($aTmpStock[$key]['siret']);
            if (count($aSerachGeoStock) == 0) {
                $oConnectPG->insertGeosireneStock($aTmpStock[$key], $aArrayModifs, $numfic, $sDateFormat, $bCreation);
            } else {
                $oConnectPG->updateGeosireneStock($aTmpStock[$key]);
            }

            echo "-------------------------insertGeosirene---------------------\n\n";


            if ($bCreation == 'TRUE') {
                if ($aTmpStock[$key]['nomenclatureactiviteprincipaleetablissement'] === "NAFRev2") {
                    $inbCreation++;
                    $this->insertDebug($aTmpStock[$key]['siret'], 'TRUE', 'FALSE', $sDateFormat);
                    $oConnectPG->insertStock($aTmpStock[$key], $sDateFormat);
                    echo "-------------------------insertStock---------------------\n\n";
                }
            }
            Util::logMajGeosirene("Traitement ligne  = " . $key . " SIRET = " . $aTmpStock[$key]['siret']);
        }
        $oApiBano->createFichierGeocode();
        // ON UPDATE AVEC LES INFOS DE BANO
        $oConnectPG->updateGeosireneBano($numfic);
        $oConnectPG->updateGeosireneBanoStock();

        $this->repareNomEtablissement();
        echo "----------------FIN---------updateGeosireneBano---------------------\n\n";

        $oConnectPG->sendMailRecapMajgeosirene($inbCreation, $inbUdpate);

        /**/
    }

    public function repareNomEtablissement() {

        $oConnectPG = new connectPostreSql();
        $aEtab = $oConnectPG->getEtablissementsOuvertsSansNom();

        for ($i = 0; $i < count($aEtab); $i++) {

            $sSiren = $aEtab[$i]['siren'];
            echo "SIREN = " . $sSiren . "\n";

            $aUL = $oConnectPG->getUlBiSIren($sSiren);

            if (count($aUL) > 0) {
                echo "UPDATE = " . $sSiren . "\n";
                if (strlen($aUL[0]['denominationunitelegale']) > 0) {

                    $oConnectPG->updateDenominationEtab($aUL[0]['denominationunitelegale'], $sSiren);
                } else if (strlen($aUL[0]['nomunitelegale']) > 0) {

                    $oConnectPG->updateDenominationEtab($aUL[0]['nomunitelegale'], $sSiren);
                }
            } else {
                echo "PAS TROUVE \n";
            }
        }

        $oConnectPG->sendMailMajNomEtablissement();
    }

    public function repareNomEtablissementNumFic($numfic) {

        $oApiSocCom = new ApiSocCom();
        $oConnectPG = new connectPostreSql();
        $aEtab = $oConnectPG->getEtablissementsOuvertsSansNomNumFic($numfic);

        for ($i = 0; $i < count($aEtab); $i++) {

            $sSiren = $aEtab[$i]['siren'];
            echo "SIREN = " . $sSiren . "\n";

            $aUL = $oConnectPG->getUlBiSIren($sSiren);

            if (count($aUL) > 0) {
                echo "DENOMINATION------" . $aUL[0]['denominationunitelegale'] . "\n";
                echo "NOM UL------" . $aUL[0]['nomunitelegale'] . "\n";



                if (strlen($aUL[0]['denominationunitelegale']) > 0) {

                    echo "UPDATE denominationunitelegale = " . $sSiren . "\n";

                    $oConnectPG->updateDenominationEtab($aUL[0]['denominationunitelegale'], $sSiren);
                    $oConnectPG->updateDenominationEtabStock($aUL[0]['denominationunitelegale'], $sSiren);
                } else if (strlen($aUL[0]['nomunitelegale']) > 0) {

                    echo "UPDATE nomunitelegale = " . $sSiren . "\n";
                    $oConnectPG->updateDenominationEtab($aUL[0]['prenom1unitelegale'] . '' . $aUL[0]['nomunitelegale'], $sSiren);
                    $oConnectPG->updateDenominationEtabStock($aUL[0]['prenom1unitelegale'] . '' . $aUL[0]['nomunitelegale'], $sSiren);
                }
            } else {


                $aUlCesse = $oConnectPG->getUlCesseeBiSIren($sSiren);

                if (count($aUlCesse) > 0) {

                    if (strlen($aUlCesse[0]['denominationunitelegale']) > 0) {

                        echo "UPDATE denominationunitelegale = " . $sSiren . "\n";

                        $oConnectPG->updateDenominationEtab($aUlCesse[0]['denominationunitelegale'], $sSiren);
                        $oConnectPG->updateDenominationEtabStock($aUlCesse[0]['denominationunitelegale'], $sSiren);
                    } else if (strlen($aUlCesse[0]['nomunitelegale']) > 0) {

                        echo "UPDATE nomunitelegale = " . $sSiren . "\n";
                        $oConnectPG->updateDenominationEtab($aUlCesse[0]['prenom1unitelegale'] . '' . $aUlCesse[0]['nomunitelegale'], $sSiren);
                        $oConnectPG->updateDenominationEtabStock($aUlCesse[0]['prenom1unitelegale'] . '' . $aUlCesse[0]['nomunitelegale'], $sSiren);
                    }
                } else {

                    $sTabSoc = $oApiSocCom->getInfos($sSiren);
                    echo "SOC.COM = " . $sSiren . "\n";
                    $aExp = explode(";", $sTabSoc);
//                echo "<pre>";
//                var_dump($aExp);
//                echo "</pre>";


                    $sDenominationSoc = str_replace("Fermé", "", $aExp[31]);
                    $sDenominationSoc = str_replace('"', "", $sDenominationSoc);
//                echo "<pre>";
//                var_dump($sDenominationSoc);
//                echo "</pre>";
                    $oConnectPG->updateDenominationEtab($sDenominationSoc, $sSiren);
                    $oConnectPG->updateDenominationEtabStock($sDenominationSoc, $sSiren);
                }
            }
        }

        //$oConnectPG->sendMailMajNomEtablissement();
    }

    public function exportTableGeosirene() {

        //unlink(FILE_TO_SEND_GEOSIRENE);
        //$numfic=1;
        $oConnectPG = new connectPostreSql();

        $sQuery = "copy (select siren, nic,siret,statutdiffusionetablissement,	datecreationetablissement,	
                            trancheeffectifsetablissement,	
                            anneeeffectifsetablissement,	
                            activiteprincipaleregistremetiersetablissement,	
                            datederniertraitementetablissement,	
                            etablissementsiege,	
                            nombreperiodesetablissement,	
                            complementadresseetablissement,	
                            numerovoieetablissement,	
                            indicerepetitionetablissement,	
                            typevoieetablissement,	
                            libellevoieetablissement,	
                            codepostaletablissement	,
                            libellecommuneetablissement,	
                            libellecommuneetrangeretablissement,
                            distributionspecialeetablissement,	
                            codecommuneetablissement,	
                            codecedexetablissement,	
                            libellecedexetablissement,	
                            codepaysetrangeretablissement,	
                            libellepaysetrangeretablissement,	
                            complementadresse2etablissement,	
                            numerovoie2etablissement,	
                            indicerepetition2etablissement,	
                            typevoie2etablissement,	
                            libellevoie2etablissement,	
                            codepostal2etablissement,	
                            libellecommune2etablissement,	
                            libellecommuneetranger2etablissement,	
                            distributionspeciale2etablissement,	
                            codecommune2etablissement,	
                            codecedex2etablissement,	
                            libellecedex2etablissement,	
                            codepaysetranger2etablissement,	
                            libellepaysetranger2etablissement,	
                            datedebut,	
                            etatadministratifetablissement,	
                            enseigne1etablissement,	
                            enseigne2etablissement,	
                            enseigne3etablissement,	
                            denominationusuelleetablissement,	
                            activiteprincipaleetablissement,	
                            nomenclatureactiviteprincipaleetablissement,	
                            caractereemployeuretablissement,
                            entree_champ_diffusion_commerciale,	
                            changement_activiteprincipaleetablissement,	
                            demenagement,	
                            changement_etat_administratif,	
                            modification_tranche_nb_salaries,	
                            adresse,	
                            latitude,	
                            longitude,	
                            result_label,	
                            result_score,	
                            result_type,	
                            result_id,	
                            result_housenumber,	
                            result_name,	
                            result_street,	
                            result_postcode,	
                            result_city,	
                            result_context,	
                            result_citycode,	
                            num_fic,
                            date_integration
                            from poi.geosirene
                            where num_fic=1)
                            to 'E:\maj_geosirene\geosirene.txt' delimiter E'\t' csv header;";

        $oConnectPG->queryPDO($sQuery);
    }

    public function exportTableGeosireneHisto($numfic, $sDateFormat) {

        //unlink(FILE_TO_SEND_GEOSIRENE);
        //$numfic=1;
        $oConnectPG = new connectPostreSql();

        $sQuery = "copy (select siren, nic,siret,statutdiffusionetablissement,	datecreationetablissement,	
                            trancheeffectifsetablissement,	
                            anneeeffectifsetablissement,	
                            activiteprincipaleregistremetiersetablissement,	
                            datederniertraitementetablissement,	
                            etablissementsiege,	
                            nombreperiodesetablissement,	
                            complementadresseetablissement,	
                            numerovoieetablissement,	
                            indicerepetitionetablissement,	
                            typevoieetablissement,	
                            libellevoieetablissement,	
                            codepostaletablissement	,
                            libellecommuneetablissement,	
                            libellecommuneetrangeretablissement,
                            distributionspecialeetablissement,	
                            codecommuneetablissement,	
                            codecedexetablissement,	
                            libellecedexetablissement,	
                            codepaysetrangeretablissement,	
                            libellepaysetrangeretablissement,	
                            complementadresse2etablissement,	
                            numerovoie2etablissement,	
                            indicerepetition2etablissement,	
                            typevoie2etablissement,	
                            libellevoie2etablissement,	
                            codepostal2etablissement,	
                            libellecommune2etablissement,	
                            libellecommuneetranger2etablissement,	
                            distributionspeciale2etablissement,	
                            codecommune2etablissement,	
                            codecedex2etablissement,	
                            libellecedex2etablissement,	
                            codepaysetranger2etablissement,	
                            libellepaysetranger2etablissement,	
                            datedebut,	
                            etatadministratifetablissement,	
                            enseigne1etablissement,	
                            enseigne2etablissement,	
                            enseigne3etablissement,	
                            denominationusuelleetablissement,	
                            activiteprincipaleetablissement,	
                            nomenclatureactiviteprincipaleetablissement,	
                            caractereemployeuretablissement,
                            entree_champ_diffusion_commerciale,	
                            changement_activiteprincipaleetablissement,	
                            demenagement,	
                            changement_etat_administratif,	
                            modification_tranche_nb_salaries,	
                            adresse,	
                            latitude,	
                            longitude,	
                            result_label,	
                            result_score,	
                            result_type,	
                            result_id,	
                            result_housenumber,	
                            result_name,	
                            result_street,	
                            result_postcode,	
                            result_city,	
                            result_context,	
                            result_citycode,	
                            num_fic,
                            date_integration
                            from poi.geosirene
                            where num_fic=" . $numfic . ")
                            to 'E:\maj_geosirene\histo\geosirene_numfic" . $numfic . "_" . $sDateFormat . ".txt' delimiter E'\t' csv header;";

        $oConnectPG->queryPDO($sQuery);
    }

    public function procImportTraitement() {
        Util::logMajGeosirene("procImportTraitement");
        $oConnectPG = new connectPostreSql();
        $sQuery = "SELECT * FROM public.bdf_geosirene_import_traitement();";
        $oConnectPG->queryPDO($sQuery);
    }

    public function procbdf_calcul_agregation_poi_iris_diff_geosirene() {
        Util::logMajGeosirene("procbdf_calcul_agregation_poi_iris_diff_geosirene");
        $oConnectPG = new connectPostreSql();
        $sQuery = "select * from bdf_calcul_agregation_poi_iris_diff_geosirene(1);";
        $oConnectPG->queryPDO($sQuery);
        $sQuery2 = "select * from bdf_calcul_agregation_diff_geosirene(11);";
        $oConnectPG->queryPDO($sQuery2);
        $sQuery3 = "select * from bdf_calcul_agregation_diff_geosirene(12);";
        $oConnectPG->queryPDO($sQuery3);
    }

    public function procbdf_calcul_agregation_poi_iris_diff_geosirene_ferme() {
        Util::logMajGeosirene("procbdf_calcul_agregation_poi_iris_diff_geosirene_ferme");
        $oConnectPG = new connectPostreSql();
        $sQuery = "select * from bdf_calcul_agregation_poi_iris_diff_geosirene_ferme(1);";
        $oConnectPG->queryPDO($sQuery);
        $sQuery2 = "select * from bdf_calcul_agregation_diff_geosirene_ferme(11);";
        $oConnectPG->queryPDO($sQuery2);
        $sQuery3 = "select * from bdf_calcul_agregation_diff_geosirene_ferme(12);";
        $oConnectPG->queryPDO($sQuery3);
    }

    public function procbdf_calcul_agregation_update_diff_geosirene() {
        Util::logMajGeosirene("procbdf_calcul_agregation_update_diff_geosirene");
        $oConnectPG = new connectPostreSql();
        $sQuery = "select * from bdf_calcul_agregation_update_diff_geosirene(13);";
        $oConnectPG->queryPDO($sQuery);
        $sQuery2 = "select * from bdf_calcul_agregation_update_diff_geosirene(11);";
        $oConnectPG->queryPDO($sQuery2);
        $sQuery3 = "select * from bdf_calcul_agregation_update_diff_geosirene(12);";
        $oConnectPG->queryPDO($sQuery3);
    }

    public function procbdf_calcul_agregation_update_diff_geosirene_ferme() {
        Util::logMajGeosirene("procbdf_calcul_agregation_update_diff_geosirene_ferme");
        $oConnectPG = new connectPostreSql();
        $sQuery = "select * from bdf_calcul_agregation_update_diff_geosirene_ferme(13);";
        $oConnectPG->queryPDO($sQuery);
        $sQuery2 = "select * from bdf_calcul_agregation_update_diff_geosirene_ferme(11);";
        $oConnectPG->queryPDO($sQuery2);
        $sQuery3 = "select * from bdf_calcul_agregation_update_diff_geosirene_ferme(12);";
        $oConnectPG->queryPDO($sQuery3);
    }

//    public function procImportTraitementProd() {
//        Util::logMajGeosirene("procImportTraitement prod");
//        $oConnectPG = new connectPostreSql();
//        $sQuery = "SELECT * FROM public.bdf_geosirene_import_traitement();";
//        $oConnectPG->queryPDOProd($sQuery);
//    }

    public function procRegenCartes() {
        Util::logMajGeosirene("procRegenCartes");
        $oConnectPG = new connectPostreSql();
        $sQuery = "SELECT * FROM public.bdf_geosirene_regen_cartes();";
        $oConnectPG->queryPDO($sQuery);
    }

    public function procRegenFichier() {
        Util::logMajGeosirene("procRegenCartes");
        $oConnectPG = new connectPostreSql();
        $sQuery = "select * from bdf_geosirene_regen_alerte_fichiers();";
        $oConnectPG->queryPDO($sQuery);
    }

    public function procRegenCartesJour($sDate) {

        $aExp = explode("-", $sDate);

        $sAnnee = substr($aExp[0], 2, 2);
        $sMois = $aExp[1];
        $sJour = $aExp[2];
//        echo "annee = ".$sAnnee.'<br>';
//        echo "mois = ".$sMois.'<br>';
//        echo "jour = ".$sJour.'<br>';
//        die();
        exec('c:\WINDOWS\system32\cmd.exe /c START E:\GS_Data\BatchProc\GenerationMapim\regen_cartes_' . $sJour . '_' . $sMois . '_' . $sAnnee . '.bat');
    }

//    public function procRegenCartesProd() {
//        Util::logMajGeosirene("procRegenCartes prod");
//        $oConnectPG = new connectPostreSql();
//        $sQuery = "SELECT * FROM public. bdf_geosirene_regen_cartes();";
//        $oConnectPG->queryPDOProd($sQuery);
//    }
 
    /**
     * ON REMET LES NUMFIC EN ORDRE ET ON SUPPRIME LES ANCIENS
     */
    public function nettoyageGeosirene() {

        $oConnectPG = new connectPostreSql();

        for ($i = 31; $i >= 2; $i--) {
            $o = $i - 1;
            $sQuery = "UPDATE poi.geosirene SET num_fic = $i WHERE num_fic = $o";
            $oConnectPG->queryPDO($sQuery);
            echo $sQuery . "\n";
        }
        $sQueryDel = "DELETE FROM poi.geosirene WHERE num_fic=31";
        $oConnectPG->queryPDO($sQueryDel);
    }

//    public function nettoyageGeosireneProd() {
//
//        $oConnectPG = new connectPostreSql();
//
//        for ($i = 21; $i >= 2; $i--) {
//            $o = $i - 1;
//            $sQuery = "UPDATE poi.geosirene SET num_fic = $i WHERE num_fic = $o";
//            $oConnectPG->queryPDOProd($sQuery);
//            echo $sQuery . "\n";
//        }
//        $sQueryDel = "DELETE FROM poi.geosirene WHERE num_fic=21";
//        $oConnectPG->queryPDOProd($sQueryDel);
//    }

    public function nettoyageGeosireneTmp() {

        $oConnectPG = new connectPostreSql();

        for ($i = 21; $i >= 2; $i--) {
            $o = $i - 1;
            $sQuery = "UPDATE poi.geosirene_tmp_old SET num_fic = $i WHERE num_fic = $o";
            $oConnectPG->queryPDO($sQuery);
            echo $sQuery . "\n";
        }
        $sQueryDel = "DELETE FROM poi.geosirene_tmp_old WHERE num_fic=21";
        $oConnectPG->queryPDO($sQueryDel);
    }

    public function deleteJours61($sDateFormat) {

        $oConnectPG = new connectPostreSql();
        $cn = $oConnectPG->getConnexion();

        //SUPPRESSION DU JOURS 61
        $sDatePlus60Jours = date('Y-m-d', strtotime($sDateFormat) + (24 * 3600 * 61));
        $sQueryDel = "DELETE FROM alerte_60_jours WHERE date_integration =:date_integration";
        $sql1 = $cn->prepare($sQueryDel);
        $sql1->bindParam(':date_integration', $sDatePlus60Jours, PDO::PARAM_STR);
        $sql1->execute();
    }

    public function insertAlerte60Jours($sDateFormat, $id_user, $iNbCreations, $code_ape) {

        $oConnectPG = new connectPostreSql();
        $cn = $oConnectPG->getConnexion();

        $sQuery = "INSERT INTO public.alerte_60_jours(
	id_user, date_integration, nb_creation, code_ape)
	VALUES (:id_user, :date_integration, :nb_creation, :code_ape);";
        $sql = $cn->prepare($sQuery);
        $sql->bindParam(':id_user', $id_user, PDO::PARAM_INT);
        $sql->bindParam(':date_integration', $sDateFormat, PDO::PARAM_STR);
        $sql->bindParam(':nb_creation', $iNbCreations, PDO::PARAM_INT);
        $sql->bindParam(':code_ape', $code_ape, PDO::PARAM_STR);

        $sql->execute();

        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            die($aErreur[2]);
        }
    }

    public function sendMailAlerteQuotidienne($sDateFormat) {

        // SUPPRIME LE JOUR 61 DU NB D'ALERTES
        $this->deleteJours61($sDateFormat);

        setlocale(LC_TIME, 'fr', 'fr_FR', 'fr_FR.ISO8859-1');
        $sDateFrancais = Util::formatDateFrancais($sDateFormat);

        $oConnectPG = new connectPostreSql();
        $sQuery = "select count(*) as nb, u.id_user, u.code_ape
        from alerte_usr_ape u
        inner join poi.geosirene g 
        ON g.activiteprincipaleetablissement = u.code_ape
        inner join public.alerte_usr_frequence a
        on a.id_user = u.id_user
		inner join public.abo_utilisateurs c ON a.id_user = c.id_cpt_utilisateur
		inner join public.abonnements v on c.id_abonnements  = v.id_abonnements
         WHERE g.creation = true 
        and num_fic = 1
        and a.id_alerte_frequence = 1
         and u.actif = true
		 AND '$sDateFormat' < v.abo_site_fin 
        group by u.id_user, u.code_ape";
        $aResult = $oConnectPG->queryPDOResulset($sQuery);

        if (count($aResult) > 0) {
            for ($i = 0; $i < count($aResult); $i++) {
                // Graph 60 jours
                $this->insertAlerte60Jours($sDateFormat, $aResult[$i]['id_user'], $aResult[$i]['nb'], $aResult[$i]['code_ape']);
            }

           
            $sQuery00 = "select u.id_user , cpt_apidesk
            from alerte_usr_ape u 
            inner join poi.geosirene g 
            ON g.activiteprincipaleetablissement = u.code_ape 
            inner join public.alerte_usr_frequence a on a.id_user = u.id_user 
            inner join public.abo_utilisateurs c ON a.id_user = c.id_cpt_utilisateur 
            inner join public.abonnements v on c.id_abonnements = v.id_abonnements
            inner join public.cpt_utilisateur k On k.id_cpt_utilisateur = c.id_cpt_utilisateur 
            inner join public.comptes o ON k.id_comptes = o.id_comptes
            WHERE g.creation = true and num_fic = 1 AND '$sDateFormat' < v.abo_site_fin 
            and a.id_alerte_frequence = 1 and u.actif = true group by u.id_user, cpt_apidesk";
            
       
            $aResult00 = $oConnectPG->queryPDOResulset($sQuery00);
            
            for ($uu = 0; $uu < count($aResult00); $uu++) {
                
                if ($aResult00[$uu]['cpt_apidesk']) {
                     //mail récap par utilisateur
                $sQuerySendMail = "select public.bdf_alerte_envoi_mail_apidesk(" . $aResult00[$uu]['id_user'] . ", '" . $sDateFrancais . "')";
                $oConnectPG->queryPDO($sQuerySendMail);
                } else {
                     //mail récap par utilisateur
                $sQuerySendMail = "select public.bdf_alerte_envoi_mail(" . $aResult00[$uu]['id_user'] . ", '" . $sDateFrancais . "')";
                $oConnectPG->queryPDO($sQuerySendMail);
                }
               
            }
        }

        // PAS DE CREATION
        $sQuerySansAlerte = "select a.id_user, a.code_ape, cpt_apidesk  
		from alerte_usr_ape a INNER JOIN alerte_usr_frequence f  ON a.id_user=f.id_user 
		inner join public.abo_utilisateurs c ON a.id_user = c.id_cpt_utilisateur
		inner join public.abonnements v on c.id_abonnements  = v.id_abonnements
		inner join public.cpt_utilisateur k On k.id_cpt_utilisateur = c.id_cpt_utilisateur 
            inner join public.comptes o ON k.id_comptes = o.id_comptes
        AND f.id_alerte_frequence =1 
		AND '$sDateFormat'  < v.abo_site_fin 
		AND  a.code_ape NOT IN 
        (SELECT activiteprincipaleetablissement FROM poi.geosirene WHERE num_fic =1 AND creation = true GROUP BY activiteprincipaleetablissement) 
                GROUP BY a.id_user, a.code_ape, cpt_apidesk";
        $aResult2 = $oConnectPG->queryPDOResulset($sQuerySansAlerte);

        if ($aResult2) {
            for ($p = 0; $p < count($aResult2); $p++) {
                
                $aIntituleNaf = $oConnectPG->getIntituleNaf($aResult2[$p]['code_ape']);
                echo $aResult2[$p]['code_ape']."<br>";
                
                if ($aResult2[$p]['cpt_apidesk']) {
                     //print_r($aIntituleNaf);
                $sQuerySendMailssa = "select public.bdf_alerte_envoi_mail_sans_alerte_apidesk(" . $aResult2[$p]['id_user'] . ", '" . $aResult2[$p]['code_ape'] . "', '" . str_replace("'", " ", $aIntituleNaf[0]['intitulenaf']) . "','" . $sDateFrancais . "')";
                $oConnectPG->queryPDO($sQuerySendMailssa);
                } else {
                    
                     //print_r($aIntituleNaf);
                $sQuerySendMailssa = "select public.bdf_alerte_envoi_mail_sans_alerte(" . $aResult2[$p]['id_user'] . ", '" . $aResult2[$p]['code_ape'] . "', '" . str_replace("'", " ", $aIntituleNaf[0]['intitulenaf']) . "','" . $sDateFrancais . "')";
                $oConnectPG->queryPDO($sQuerySendMailssa);
                }
               
            }
        }
    }

    

    public function sendMailPbInsee($sDateFormat) {

        setlocale(LC_TIME, 'fr', 'fr_FR', 'fr_FR.ISO8859-1');
        $sDateFrancais = Util::formatDateFrancais($sDateFormat);

        $oConnectPG = new connectPostreSql();
        $sQuery = "select u.id_user
        from alerte_usr_ape u
        inner join poi.geosirene g 
        ON g.activiteprincipaleetablissement = u.code_ape
        inner join public.alerte_usr_frequence a
        on a.id_user = u.id_user
		inner join public.abo_utilisateurs c ON a.id_user = c.id_cpt_utilisateur
		inner join public.abonnements v on c.id_abonnements  = v.id_abonnements
         WHERE g.creation = true 
        and num_fic = 1
        and a.id_alerte_frequence = 1
         and u.actif = true
		 AND '$sDateFormat' < v.abo_site_fin 
        group by u.id_user, u.code_ape";
        $aResult = $oConnectPG->queryPDOResulset($sQuery);

        if (count($aResult) > 0) {
            for ($i = 0; $i < count($aResult); $i++) {
                $sQuerySendMail = "select public.bdf_alerte_envoi_mail_pb_insee(" . $aResult[$i]['id_user'] . ", '" . $sDateFrancais . "')";
                //echo $sQuerySendMail . "\n";
                $oConnectPG->queryPDO($sQuerySendMail);
            }
        }
    }

    public function sendMailAlerteHebdo($sDateFormat, $sDateJour, $sDateSemDern) {


        setlocale(LC_TIME, 'fr', 'fr_FR', 'fr_FR.ISO8859-1');
        //$sDateFrancais = Util::formatDateFrancais($sDateFormat);

        $oConnectPG = new connectPostreSql();
        $sQuery = "select count(*) as nb,  u.id_user
        from alerte_usr_ape u
        inner join poi.geosirene g 
        ON g.activiteprincipaleetablissement = u.code_ape
        inner join public.alerte_usr_frequence a
        on a.id_user = u.id_user
		inner join public.abo_utilisateurs c ON a.id_user = c.id_cpt_utilisateur
		inner join public.abonnements v on c.id_abonnements  = v.id_abonnements
         WHERE g.creation = true 
        and num_fic between 1 and 7
        and a.id_alerte_frequence = 2
         and u.actif = true
		 AND '$sDateFormat' < v.abo_site_fin 
        group by u.id_user";
        $aResult = $oConnectPG->queryPDOResulset($sQuery);




        if (count($aResult) > 0) {
            for ($i = 0; $i < count($aResult); $i++) {

                if ($aResult[$i]['nb'] == 0) {

                    $sQuerySendMail = "select public.bdf_alerte_envoi_mail_sans_alerte(" . $aResult[$i]['id_user'] . ", '" . $aResult[$i]['code_ape'] . "','" . $sDateSemDern . "', '" . $sDateJour . "')";
                    echo $sQuerySendMail . "\n";
                    $oConnectPG->queryPDO($sQuerySendMail);
                } else {

                    $sQuerySendMail = "select public.bdf_alerte_envoi_mail(" . $aResult[$i]['id_user'] . ", '" . $sDateSemDern . "', '" . $sDateJour . "')";
                    echo $sQuerySendMail . "\n";
                    $oConnectPG->queryPDO($sQuerySendMail);
                }
            }
        }

        // PAS DE CREATION
        /* $sQuerySansAlerte = "select a.* from alerte_usr_ape a, alerte_usr_frequence f  where a.id_user=f.id_user 
          AND f.id_alerte_frequence =2 AND  a.code_ape NOT IN
          (SELECT activiteprincipaleetablissement FROM poi.geosirene WHERE num_fic between 1 and 7 AND creation = true)";
          $aResult2 = $oConnectPG->queryPDOResulset($sQuerySansAlerte);

          if ($aResult2) {
          for ($p = 0; $p < count($aResult2); $p++) {
          $sQuerySendMailssa = "select public.bdf_alerte_envoi_mail_sans_alerte(" . $aResult[$p]['id_user'] . ", '" . $aResult[$p]['code_ape'] . "', '" . $sDateFrancais . "','" . $sDateJour . "', '" . $sDateSemDern . "')";
          $oConnectPG->queryPDO($sQuerySendMailssa);
          }
          } */
    }

    public function agregationPoiIris() {
        Util::logMajGeosirene("agregationPoiIris");
        $oConnectPG = new connectPostreSql();
        $sQuery = "SELECT * FROM public.bdf_calcul_agregation_poi_iris(16,1)";
        $oConnectPG->queryPDO($sQuery);
    }

//    public function agregationPoiIrisProd() {
//        Util::logMajGeosirene("agregationPoiIris Prod");
//        $oConnectPG = new connectPostreSql();
//        $sQuery = "SELECT * FROM public.bdf_calcul_agregation_poi_iris(16,1)";
//        $oConnectPG->queryPDOProd($sQuery);
//    }

    public function agregationIrisCom() {
        Util::logMajGeosirene("agregationIrisCom");
        $oConnectPG = new connectPostreSql();
        $sQuery = "SELECT * FROM public.bdf_calcul_agregation(13,11,20,1);";
        $oConnectPG->queryPDO($sQuery);
    }

//    public function agregationIrisComProd() {
//        Util::logMajGeosirene("agregationIrisCom prod");
//        $oConnectPG = new connectPostreSql();
//        $sQuery = "SELECT * FROM public.bdf_calcul_agregation(13,11,20,1);";
//        $oConnectPG->queryPDOProd($sQuery);
//    }

    public function truncateDebug() {

        $oConnectPG = new connectPostreSql();
        $sQueryTrucnate = "truncate table poi.debug";
        $oConnectPG->queryPDO($sQueryTrucnate);

        $sQueryVaccum = "VACUUM ANALYZE poi.debug;";
        $oConnectPG->queryPDO($sQueryVaccum);
    }

    public function insertDebug($sSiret, $bCreat, $bModif, $date) {

        $oConnectPG = new connectPostreSql();
        $cn = $oConnectPG->getConnexion();
        $sQuery = "INSERT INTO poi.debug (siret, creation, modif, date) VALUES (:siret, :creation, :modif, :date) ";

        $sql = $cn->prepare($sQuery);
        $sql->bindParam(':siret', $sSiret, PDO::PARAM_STR);
        $sql->bindParam(':creation', $bCreat, PDO::PARAM_STR);
        $sql->bindParam(':modif', $bModif, PDO::PARAM_STR);
        $sql->bindParam(':date', $date, PDO::PARAM_STR);

        $sql->execute();

        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            die($aErreur[2]);
        }
    }

    public function updateNumFicGeosireneRepare($numfic, $date) {

        $sQuery = "UPDATE  poi.geosirene SET num_fic =:num_fic WHERE date_integration =:date_integration ";

        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);
        $sql->bindParam(':num_fic', $numfic, PDO::PARAM_INT);
        $sql->bindParam(':date_integration', $date, PDO::PARAM_STR);

        $sql->execute();

        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            echo $sql->queryString . "\n\n";
            die($aErreur[2]);
        }
    }

}
