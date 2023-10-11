<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of geosireneTraitementDebug
 *
 * @author sleco
 */
class geosireneTraitementDebug {
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

                        $oApiBano->createFichierGeocode();
                        $sCurseurSuivant = $retJson->header->curseurSuivant;
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
        } else {
            die('Manque des infos');
        }
    }

    public function etape2($sDateFormat, $numfic) {

        
        $oConnectPG = new connectPostreSql();
        $oUtil = new Util();

        $aTmpStock = $oConnectPG->getTmpStock();
        //print_r($aTmpStock[0]);
        //$numfic = 1;
        
        $icountInsert = 0;
        $icountUpdate = 0;
        
       echo "TMP STOCK = ".count($aTmpStock)."<br>";

        foreach ($aTmpStock as $key => $value) {
            
            /*if ($key >2000) {
                echo "total update = ".$icountUpdate."<br>";
                echo "total insert = ".$icountInsert."<br>";
                die("FIN 1000");
            }*/
            $bCreation = 'TRUE';

            $aArrayModifs = array();
            echo "-------------------------------------------" . $aTmpStock[$key]['siret'] . "----<br><br>";

            //echo "---------------------------KEY =>----------------" . $key . "----<br><br>";

            // ON REGARDE SI ON A DEJA L ETAB DANS LE STOCK
            $aStock = $oConnectPG->getStocksBySiret($aTmpStock[$key]['siret']);
            //echo "-------------------------getStocksBySiret---------------------<br><br>";

            if (count($aStock) > 0) {

                 $bCreation = 'FALSE';
                // COMPARAISON DES MODIFICATIONS
                $aArrayModifs = $oUtil->compareModifsEtablissement($aTmpStock[$key], $aStock);
                //echo "-------------------------compareModifsEtablissement---------------------<br><br>";
                $icountUpdate++;
                //$this->insertDebug($aTmpStock[$key]['siret'], 'FALSE', 'TRUE', $sDateFormat);
                // UPDATE STOCK
                echo "update stock " . $key . "<br>";
                //$oConnectPG->updateStock($aTmpStock[$key]);
            } else {                
                $bCreation = 'TRUE';                
            }

           
            // ON INSERT LES MODIFS DE LA JOURNEE
            //$oConnectPG->insertGeosirene($aTmpStock[$key], $aArrayModifs, $numfic, $sDateFormat, $bCreation);
            //echo "-------------------------insertGeosirene---------------------<br><br>";

            
            if ($bCreation=='TRUE') {
                if ($aTmpStock[$key]['nomenclatureactiviteprincipaleetablissement'] === "NAFRev2") {
                     $icountInsert ++;
                     //$this->insertDebug($aTmpStock[$key]['siret'], 'TRUE', 'FALSE', $sDateFormat);
                    //$oConnectPG->insertStock($aTmpStock[$key]);
                    echo "-------------------------insertStock----" . $key . "-----------------<br><br>";
                }
            }
        }
        //echo "update geosirene bano <br>";
        // ON UPDATE AVEC LES INFOS DE BANO
        //$oConnectPG->updateGeosireneBano($numfic);
        //echo "----------------FIN---------updateGeosireneBano---------------------<br><br>";
        
        /**/
    
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
                            where num_fic=".$numfic.")
                            to 'E:\maj_geosirene\histo\geosirene_numfic".$numfic."_".$sDateFormat.".txt' delimiter E'\t' csv header;";
        
        $oConnectPG->queryPDO($sQuery);
    }
    
    public function procImportTraitement() {
        echo "procImportTraitement <br>";
        $oConnectPG = new connectPostreSql();  
        $sQuery = "SELECT * FROM public.bdf_geosirene_import_traitement();";
        $oConnectPG->queryPDO($sQuery);
    }
    
     public function agregationPoiIris() {
        
        $oConnectPG = new connectPostreSql();   
        $sQuery = "SELECT * FROM public.bdf_calcul_agregation_poi_iris(16,1)";
        $oConnectPG->queryPDO($sQuery);
    }
    public function agregationIrisCom() {
        
        $oConnectPG = new connectPostreSql();   
        $sQuery = "SELECT * FROM public.bdf_calcul_agregation(13,11,20,1);";
        $oConnectPG->queryPDO($sQuery);
    }

    /**
     * ON REMET LES NUMFIC EN ORDRE ET ON SUPPRIME LES ANCIENS
     */
    public function nettoyageGeosirene() {
        
        $oConnectPG = new connectPostreSql();        
        
       for ($i=21; $i>=2; $i--) {
           $o=$i-1;
            $sQuery = "UPDATE poi.geosirene SET num_fic = $i WHERE num_fic = $o";
            $oConnectPG->queryPDO($sQuery);
            echo $sQuery."<br>";
        }        
        $sQueryDel = "DELETE FROM poi.geosirene WHERE num_fic=21";
        $oConnectPG->queryPDO($sQueryDel);
    }
    
    public function sendMailAlerteQuotidienne() {
        
        $oConnectPG = new connectPostreSql();   
        $sQuery = "select u.id_user
        from alerte_usr_ape u
        inner join poi.geosirene g 
        ON g.activiteprincipaleetablissement = u.code_ape
        inner join public.alerte_usr_frequence a
        on a.id_user = u.id_user
         WHERE g.creation = true 
        and num_fic = 1
        and a.id_alerte_frequence = 1
         and u.actif = true
        group by u.id_user";
        $aResult = $oConnectPG->queryPDOResulset($sQuery);
        print_r($aResult);
        for($i=0; $i<count($aResult); $i++) {
            $sQuerySendMail = "select public.bdf_alerte_envoi_mail(".$aResult[$i]['id_user'].")";
            echo $sQuerySendMail."<br>";
            $oConnectPG->queryPDO($sQuerySendMail);
        }

    }
    
       public function sendMailAlerteHebdo($sDateJour, $sDateSemDern) {
          
        $oConnectPG = new connectPostreSql();   
        $sQuery = "select u.id_user
        from alerte_usr_ape u
        inner join poi.geosirene g 
        ON g.activiteprincipaleetablissement = u.code_ape
        inner join public.alerte_usr_frequence a
        on a.id_user = u.id_user
         WHERE g.creation = true 
        and num_fic = 1
        and a.id_alerte_frequence = 2
         and u.actif = true
        group by u.id_user";
        $aResult = $oConnectPG->queryPDOResulset($sQuery);
        
        for($i=0; $i<count($aResult); $i++) {
            $sQuerySendMail = "select public.bdf_alerte_envoi_mail(".$aResult[$i]['id_user'].", '".$sDateJour."', '".$sDateSemDern."')";
            $oConnectPG->queryPDO($sQuerySendMail);
        }

    }
    
    

}
