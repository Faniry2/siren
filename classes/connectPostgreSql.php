<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of connectPostreSql
 *
 * @author sleco
 */
require_once 'config.php';
require_once 'util.php';

class connectPostreSql {

    public static $oConnexion;
    public static $oConnexionProd;
    private $iCountChgAdresse = 0;
    private $geosirene= "poi.geosirene";

    public function __constructor(){
        
    }

    function getConnexion() {

        if (!self::$oConnexion) {
            try {
                $dbName = DB_NAME;
                $host = DB_HOST;
                $utilisateur = DB_USER;
                $motDePasse = DB_PASS;
                $port = DB_PORT;
                $dns = 'pgsql:host=' . $host . ';dbname=' . $dbName . ';port=' . $port;
                self::$oConnexion = new PDO($dns, $utilisateur, $motDePasse);
                self::$oConnexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                //echo "connexion geocube OK \n\n";
            } catch (Exception $e) {
                echo DB_NAME . " " . DB_HOST . " " . DB_USER . " " . DB_PASS . "\n";
                echo "Connection à la BDD impossible : ", $e->getMessage();
                die();
            }
        }

        return self::$oConnexion;
    }

    function closeConnexion() {

        self::$oConnexion = null;
    }

//    function getConnexionProd() {
//
//        if (!self::$oConnexionProd) {
//            try {
//                $dbName = DB_NAME;
//                $host = DB_HOST_PROD;
//                $utilisateur = DB_USER_PROD;
//                $motDePasse = DB_PASS_PROD;
//                $port = DB_PORT;
//                $dns = 'pgsql:host=' . $host . ';dbname=' . $dbName . ';port=' . $port;
//                self::$oConnexionProd = new PDO($dns, $utilisateur, $motDePasse);
//                self::$oConnexionProd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
//                //echo "connexion geocube OK \n\n";
//            } catch (Exception $e) {
//                echo "Connection à la BDD impossible : ", $e->getMessage();
//                die();
//            }
//        }
//
//        return self::$oConnexionProd;
//    }

    function getConnexionMysql() {

        if (!self::$oConnexion) {
            try {
                $dbName = DB_NAME_MY;
                $host = DB_HOST_MY;
                $utilisateur = DB_USER_MY;
                $motDePasse = DB_PASS_MY;
                $port = DB_PORT_MY;
                $dns = 'mysql:host=' . $host . ';dbname=' . $dbName . ';port=' . $port;
                self::$oConnexion = new PDO($dns, $utilisateur, $motDePasse);
                self::$oConnexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                //echo "connexion geocube OK \n\n";
            } catch (Exception $e) {
                echo "Connection à la BDD impossible : ", $e->getMessage();
                die();
            }
        }

        return self::$oConnexion;
    }

    public function queryPDO($sQuery) {

        $this->getConnexion();
        $resultset = self::$oConnexion->prepare($sQuery);
        //print_r(self::$oConnexion->errorInfo());
        $resultset->execute();
        $aErreur = $resultset->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            echo $sQuery . "\n";
            die($aErreur[2]);
        }
    }

//    public function queryPDOProd($sQuery) {
//
//        $this->getConnexionProd();
//        $resultset = self::$oConnexionProd->prepare($sQuery);
//        //print_r(self::$oConnexion->errorInfo());
//        $resultset->execute();
//        $aErreur = $resultset->errorInfo();
//        if (strlen($aErreur[2]) > 0) {
//            echo $sQuery . "\n";
//            die($aErreur[2]);
//        }
//    }

    public function queryPDOResulset($sQuery) {

        $this->getConnexion();
        $resultset = self::$oConnexion->prepare($sQuery);
        $resultset->execute();
        $aErreur = $resultset->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            echo $sQuery . "\n";
            die($aErreur[2]);
        } else {
            return $resultset->fetchAll(PDO::FETCH_ASSOC);
        }
    }

//    public function queryPDOResulsetProd($sQuery) {
//
//        $this->getConnexionProd();
//        $resultset = self::$oConnexionProd->prepare($sQuery);
//        $resultset->execute();
//        $aErreur = $resultset->errorInfo();
//        if (strlen($aErreur[2]) > 0) {
//            echo $sQuery . "\n";
//            die($aErreur[2]);
//        } else {
//
//            return $resultset->fetchAll(PDO::FETCH_ASSOC);
//        }
//    }

    public function getEtablissementsFermes($p) {

        $this->getConnexion();
        $sSql = "SELECT siret, id FROM poi.stock_histo_etab WHERE etatadministratifetablissement = 'F' LIMIT 1000 OFFSET " . $p;
        $resultset = self::$oConnexion->prepare($sSql);
        $resultset->execute();
        return $resultset->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @author Faniry Andriamihaingo
     * insert de nouveau siren dans la table liste_siren
     */
    public function getNewSirenInListeSiren(){
        
        //$query="SELECT siren FROM poi.geosirene g WHERE NOT EXISTS(SELECT siren FROM poi.liste_siren ls WHERE ls.siren = g.siren )";
        $query="INSERT INTO poi.liste_siren(siren, ouvert) SELECT siren, ouvert FROM ( select distinct siren, 1 as ouvert from poi.sirene_etablissement_n0_sans_geo  union  select distinct siren, 0 as ouvert from poi.sirene_etab_ferme_sans_geo) where siren not in (select distinct siren from poi.liste_siren)";
        $this->getConnexion();
        $resultset = self::$oConnexion->prepare($query);
        return $resultset->execute();
        //return $resultset->fetchAll(PDO::FETCH_ASSOC);

    }

    /**
     * @author Faniry Andriamihaingo
     */
    public function addNewSirenInListeSiren($siren){
        $sQuery="INSERT INTO poi.liste_siren(siren) VALUES (:siren)";
        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);
        $sql->bindParam(':siren', $siren, PDO::PARAM_STR);
        $sql->execute();
        
    }

    public function deleteEtablissementsFermes($iSiret) {

        $this->getConnexion();
        $sSql = "DELETE FROM poi.stock_etab  WHERE siret = '" . $iSiret . "'";

        $resultset = self::$oConnexion->prepare($sSql);
        $resultset->execute();
    }

    public function getStocksBySiret($sSiret) {

        $sQuery = "SELECT * FROM poi.sirene_etablissement_n0 WHERE siret =:siret";
        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);
        $sql->bindParam(':siret', $sSiret, PDO::PARAM_STR);
        $sql->execute();

        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            echo $sql->queryString . "<br><br>";
            die($aErreur[2]);
        } else {
            return $sql->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    
    
    public function getStocksBySiretFerme($sSiret) {

        $sQuery = "SELECT * FROM poi.sirene_etablissement_ferme WHERE siret =:siret";
        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);
        $sql->bindParam(':siret', $sSiret, PDO::PARAM_STR);
        $sql->execute();

        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            echo $sql->queryString . "<br><br>";
            die($aErreur[2]);
        } else {
            return $sql->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    public function getGeosireneEstPresent($sSiret) {
        return $this->queryPDOResulset("SELECT *  FROM poi.geosirene WHERE siret = '" . $sSiret . "' AND num_fic = 1");
    }

    public function getGeosireneStocksBySiret($sSiret) {

        return $this->queryPDOResulset("SELECT *  FROM poi.geosirene_stock WHERE siret = '" . $sSiret . "' ");
    }

//    public function getGeosireneStocksBySiretProd($sSiret) {
//
//        return $this->queryPDOResulsetProd("SELECT *  FROM poi.sirene_etablissement_n0 WHERE siret = '" . $sSiret . "' ");
//    }
//    public function getStockFermeBySiretProd($sSiret) {
//
//        return $this->queryPDOResulsetProd("SELECT *  FROM poi.sirene_etablissement_ferme WHERE siret = '" . $sSiret . "' ");
//    }
    public function getStockFermeBySiret($sSiret) {

        return $this->queryPDOResulset("SELECT *  FROM poi.sirene_etablissement_ferme WHERE siret = '" . $sSiret . "' ");
    }

    public function etablissementPresent($sSiret) {

        $this->getConnexion();
        $estPresent = FALSE;
        $aResult = $this->getStocksBySiret($sSiret);
        if (count($aResult) == 1) {
            // INSERTION
            $estPresent = TRUE;
        }
        return $estPresent;
    }

    public function updateGeosireneBano($numfic) {

        echo '----------------------------------------------------------updateGeosireneBano \n';

        $sFile = FILE_SORTIE_BANO;
        if (file_exists($sFile)) {
            echo 'file exist \n';
        } else {
            die('file nt exist');
        }
        if (!$file = @fopen($sFile, "r")) {
            die("Echec de l'ouverture du fichier");
        } else {
            $nulLingne = 0;

            while (!feof($file)) {

                $aTabBano = fgetcsv($file, '', ";");

                if ($nulLingne > 0) {

                    if (count($aTabBano) > 0 && $aTabBano[6]) {

                        echo " UPDATE GEOSIRENE BANO => " . $aTabBano[0] . '\n\n';


                        $sQuery = "UPDATE poi.geosirene
                        SET adresse=:adresse,
                        latitude=:latitude, 
                        longitude=:longitude, 
                        result_label=:result_label, 
                        result_score=:result_score, 
                        result_type=:result_type, 
                        result_id=:result_id, 
                        result_housenumber=:result_housenumber, 
                        result_name=:result_name, 
                        result_street=:result_street, 
                        result_postcode=:result_postcode, 
                        result_city=:result_city, 
                        result_context=:result_context, 
                        result_citycode=:result_citycode
                        WHERE num_fic=:num_fic AND 
                        siret =:siret;";

                        $db = $this->getConnexion();
                        $sql = $db->prepare($sQuery);
                        $sql->bindParam(':adresse', $aTabBano[1], PDO::PARAM_STR);
                        $sql->bindParam(':latitude', $aTabBano[2], PDO::PARAM_STR);
                        $sql->bindParam(':longitude', $aTabBano[3], PDO::PARAM_STR);
                        $sql->bindParam(':result_label', $aTabBano[4], PDO::PARAM_STR);
                        $sql->bindParam(':result_score', $aTabBano[5], PDO::PARAM_STR);
                        $sql->bindParam(':result_type', $aTabBano[6], PDO::PARAM_STR);
                        $sql->bindParam(':result_id', $aTabBano[7], PDO::PARAM_STR);
                        $sql->bindParam(':result_housenumber', $aTabBano[8], PDO::PARAM_STR);
                        $sql->bindParam(':result_name', $aTabBano[9], PDO::PARAM_STR);
                        $sql->bindParam(':result_street', $aTabBano[10], PDO::PARAM_STR);
                        $sql->bindParam(':result_postcode', $aTabBano[11], PDO::PARAM_STR);
                        $sql->bindParam(':result_city', $aTabBano[12], PDO::PARAM_STR);
                        $sql->bindParam(':result_context', $aTabBano[13], PDO::PARAM_STR);
                        $sql->bindParam(':result_citycode', $aTabBano[14], PDO::PARAM_STR);
                        $sql->bindParam(':num_fic', $numfic, PDO::PARAM_INT);
                        $sql->bindParam(':siret', $aTabBano[0], PDO::PARAM_STR);

                        $sql->execute();

                        $aErreur = $sql->errorInfo();
                        if (strlen($aErreur[2]) > 0) {
                            echo $sql->queryString . "\n\n";
                            die($aErreur[2]);
                        }
                    }
                }
                $nulLingne ++;
            }
            fclose($file);
        }
        Util::logMajGeosirene("Fin update geosirene donnees BANO");
    }

    /**
     * geocode les adresses avec l'api bano
     */
    public function updateGeosireneBanoFromApi($oResult, $siret, $numfic) {

        //echo '----------------------------------------------------------updateGeosireneBano \n';

        $sLatitude = $oResult->features[0]->geometry->coordinates[1];
        $sLongitude = $oResult->features[0]->geometry->coordinates[0];
        $sResultLabel = $oResult->features[0]->properties->label;
        $sResultType = $oResult->features[0]->properties->type;
        $sResultId = $oResult->features[0]->properties->id;
        if ($oResult->features[0]->properties->housenumber) {
            $sResultHouseNumber = $oResult->features[0]->properties->housenumber;
        } else {
            $sResultHouseNumber = "";
        }

        $sResultName = $oResult->features[0]->properties->name;
        if ($oResult->features[0]->properties->street) {
            $sResultStreet = $oResult->features[0]->properties->street;
        } else {
            $sResultStreet = "";
        }

        $sResultPostCode = $oResult->features[0]->properties->postcode;
        $sResultCity = $oResult->features[0]->properties->city;
        $sResultContext = $oResult->features[0]->properties->context;
        $sResultCityCode = $oResult->features[0]->properties->citycode;
        $sResultScore = number_format($oResult->features[0]->properties->score, 2);
        $sAdresse = $oResult->features[0]->properties->label;

        $sQuery = "UPDATE poi.geosirene
                SET adresse=:adresse,
                latitude=:latitude, 
                longitude=:longitude, 
                result_label=:result_label, 
                result_score=:result_score, 
                result_type=:result_type, 
                result_id=:result_id, 
                result_housenumber=:result_housenumber, 
                result_name=:result_name, 
                result_street=:result_street, 
                result_postcode=:result_postcode, 
                result_city=:result_city, 
                result_context=:result_context, 
                result_citycode=:result_citycode
                depcom=:depcom
                WHERE num_fic=:num_fic AND 
                siret =:siret;";

        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);
        $sql->bindParam(':adresse', $sAdresse, PDO::PARAM_STR);
        $sql->bindParam(':latitude', $sLatitude, PDO::PARAM_STR);
        $sql->bindParam(':longitude', $sLongitude, PDO::PARAM_STR);
        $sql->bindParam(':result_label', $sResultLabel, PDO::PARAM_STR);
        $sql->bindParam(':result_score', $sResultScore, PDO::PARAM_STR);
        $sql->bindParam(':result_type', $sResultType, PDO::PARAM_STR);
        $sql->bindParam(':result_id', $sResultId, PDO::PARAM_STR);
        $sql->bindParam(':result_housenumber', $sResultHouseNumber, PDO::PARAM_STR);
        $sql->bindParam(':result_name', $sResultName, PDO::PARAM_STR);
        $sql->bindParam(':result_street', $sResultStreet, PDO::PARAM_STR);
        $sql->bindParam(':result_postcode', $sResultPostCode, PDO::PARAM_STR);
        $sql->bindParam(':result_city', $sResultCity, PDO::PARAM_STR);
        $sql->bindParam(':result_context', $sResultContext, PDO::PARAM_STR);
        $sql->bindParam(':result_citycode', $sResultCityCode, PDO::PARAM_STR);
        $sql->bindParam(':num_fic', $numfic, PDO::PARAM_INT);
        $sql->bindParam(':siret', $siret, PDO::PARAM_STR);
        $sql->bindParam(':depcom', $sResultCityCode, PDO::PARAM_STR);

        $sql->execute();
//
        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            echo $sQuery . "\n\n";
            $this->sendMailIncidentQuery($sQuery, $aErreur[2]);
            //die($aErreur[2]);
        }

        // Util::logMajGeosirene("Fin update geosirene donnees BANO");
    }

    /**
     * 
     * @author
     */
    public function geocodageIris($numfic){
        $this->updateGeosireneByAddDepcom($numfic);
        $this->geocentrecom($numfic);
        $this->updateLatLngForPOIGeocodeAtCity($numfic);
        $this->setDcomIris($numfic);
        $this->setDcomIrisToXXXX($numfic);
        $this->setDcomIrisTo0000($numfic);
        $this->setDcomIrisToXXXXWhenDcomirisIsNull($numfic);
        $this->setDcomIrisTo0000WhenDcomirisIsNull($numfic);
        $this->setDcomIrisToXXXXWhenResultTypeIsNull($numfic);
        $this->setDcomIrisTo0000WhenResultTypeIsNull($numfic);
        $this->updateLatLngWithCityCoordinates($numfic);
        $this->calculateGeometrieTo3857EPSG($numfic);

    }
    /**
     * @author faniry 
     * Remplissage de "comirisables"
     */
    public function updateGeosireneByAddDepcom($numfic){
        $sqlQuery="UPDATE $this->geosirene as g set comirisable = dico.comirisable
                   FROM corresp.dico_commune_lastref as dico WHERE g.depcom = dico.depcom and num_fic= :num_fic" ;
        $db=$this->getConnexion();
        $sql=$db->prepare($sqlQuery);
        $sql->bindParam(':num_fic', $numfic, PDO::PARAM_INT);
        $sql->execute();

        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            echo $sqlQuery . "\n\n";
            $this->sendMailIncidentQuery($sqlQuery, $aErreur[2]);
            //die($aErreur[2]);
        }

    }

    /**
     * @author faniry
     * Remplissage de "geocentrecom" si le geocodage était fait au niveau de commune ou quartier
     */
    public function geocentrecom($numfic){
        $sqlQuery="UPDATE $this->geosirene SET geocentrecom =:geocentrecom
                   WHERE result_type IN (:locality,:municipality) AND nim_fic=:num_fic";
        $db=$this->getConnexion();
        $sql=$db->prepare($sqlQuery);
        $sql->bindParam(':geocentrecom', true,PDO::PARAM_BOOL);
        $sql->bindParam(':locality','locality',PDO::PARAM_STR);
        $sql->bindParam(':municipality','municipality',PDO::PARAM_STR);
        $sql->bindParam(':num_fic', $numfic, PDO::PARAM_INT);

        $sql->execute();

        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            echo $sqlQuery . "\n\n";
            $this->sendMailIncidentQuery($sqlQuery, $aErreur[2]);
            //die($aErreur[2]);
        }
    } 

    /**
     * @author faniry
     *  UPDATE longitude / latitude pour les POIs géocodés à la commune (result_type = 'locality' --> geocentrecom = true)
     */
    public function updateLatLngForPOIGeocodeAtCity($numfic){
        $sqlQuery="UPDATE $this->geosirene SET modifxy = :modifxy  
                  WHERE geocentrecom= :geocentrecom AND num_fic =:num_fic";
        $db=$this->getConnexion();
        $sql=$db->prepare($sqlQuery);
        $sql->bindParam(':modifxy', true,PDO::PARAM_BOOL);
        $sql->bindParam(':geocentrecom', true,PDO::PARAM_BOOL);
        $sql->bindParam(':num_fic', $numfic, PDO::PARAM_INT);
        $sql->execute();

        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            echo $sqlQuery . "\n\n";
            $this->sendMailIncidentQuery($sqlQuery, $aErreur[2]);
            //die($aErreur[2]);
        }
    }

    /**
     * @author faniry
     * Attribution du DCOMIRIS COMPLET
     */
    public function setDcomIris($numfic){
        $sqlQuery="UPDATE $this->geosirene as gm set dcomiris= iris.dcomiris 
                   FROM geo.iris_geo as iris 
                   WHERE 
                    (gm.geocentrecom =:geocentrecom1  OR (gm.geocentrecom =:geocentrecom2 AND gm.comirisables =:comirisables))
                    AND
                    (ST_intersects(ST_Transform(ST_SetSRID(ST_MakePoint(gm.longitude,gm.latitude),4326),3857),iris.the_geom_3857))
                    AND
                    (gm.num_fic =:num_fic)";
         $db=$this->getConnexion();
         $sql=$db->prepare($sqlQuery);
         $sql->bindParam(':geocentrecom1', false,PDO::PARAM_BOOL);
         $sql->bindParam(':geocentrecom2', true,PDO::PARAM_BOOL);
         $sql->bindParam(':comirisables', false,PDO::PARAM_BOOL);
         $sql->bindParam(':num_fic', $numfic, PDO::PARAM_INT);
         $sql->execute();
 
         $aErreur = $sql->errorInfo();
         if (strlen($aErreur[2]) > 0) {
             echo $sqlQuery . "\n\n";
             $this->sendMailIncidentQuery($sqlQuery, $aErreur[2]);
             //die($aErreur[2]);
         }
    }

    /**
     * @author faniry
     * Attribution du DCOMIRIS XXXX and dcomiris not null
     */
    public function setDcomIrisToXXXX($numfic){
        $sqlQuery="UPDATE $this->geosirene SET dcomiris = depcom || :xxxx, result_type= :result_type, geocentrecom = :geocentrecom, modifxy = :modifxy 
                WHERE dcomiris IS NOT NULL 
                AND SUBSTR(dcomiris, 1, 5) <>depcom 
                AND comirisables= :comirisables
                AND num_fic =:num_fic";
         $db=$this->getConnexion();
         $sql=$db->prepare($sqlQuery);
         $sql->bindParam(':xxxx','xxxx',PDO::PARAM_STR);
         $sql->bindParam(':result_type','municipality',PDO::PARAM_STR);
         $sql->bindParam(':geocentrecom', true,PDO::PARAM_BOOL);
         $sql->bindParam(':modifxy', true,PDO::PARAM_BOOL);
         $sql->bindParam(':comirisables', true,PDO::PARAM_BOOL);
         $sql->bindParam(':num_fic', $numfic, PDO::PARAM_INT);
         $sql->execute();
 
         $aErreur = $sql->errorInfo();
         if (strlen($aErreur[2]) > 0) {
             echo $sqlQuery . "\n\n";
             $this->sendMailIncidentQuery($sqlQuery, $aErreur[2]);
             //die($aErreur[2]);
         }
    }

    /**
     * @author faniry
     * Attribution du DCOMIRIS 0000 and dcomiris not null
     */
    public function setDcomIrisTo0000($numfic){
        $sqlQuery="UPDATE $this->geosirene SET dcomiris = depcom || :xxxx, result_type= :result_type, geocentrecom = :geocentrecom, modifxy = :modifxy 
                WHERE dcomiris IS NOT NULL 
                AND SUBSTR(dcomiris, 1, 5) <>depcom 
                AND comirisables= :comirisables
                AND num_fic =:num_fic";
         $db=$this->getConnexion();
         $sql=$db->prepare($sqlQuery);
         $sql->bindParam(':xxxx','0000',PDO::PARAM_STR);
         $sql->bindParam(':result_type','municipality',PDO::PARAM_STR);
         $sql->bindParam(':geocentrecom', true,PDO::PARAM_BOOL);
         $sql->bindParam(':modifxy', true,PDO::PARAM_BOOL);
         $sql->bindParam(':comirisables', false,PDO::PARAM_BOOL);
         $sql->bindParam(':num_fic', $numfic, PDO::PARAM_INT);
 
         $sql->execute();
 
         $aErreur = $sql->errorInfo();
         if (strlen($aErreur[2]) > 0) {
             echo $sqlQuery . "\n\n";
             $this->sendMailIncidentQuery($sqlQuery, $aErreur[2]);
             //die($aErreur[2]);
         }
    }
    /**
     * @author faniry
     * Attribution du DCOMIRIS XXXX and dcomiris == null
     */
    public function setDcomIrisToXXXXWhenDcomirisIsNull($numfic){
        $sqlQuery="UPDATE $this->geosirene SET dcomiris = depcom || :xxxx, result_type= :result_type, geocentrecom = :geocentrecom, modifxy = :modifxy 
                WHERE dcomiris IS NULL 
                AND comirisables= :comirisables 
                AND  latitude IS NOT NULL 
                AND longitude IS NOT NULL 
                AND num_fic =:num_fic";
         $db=$this->getConnexion();
         $sql=$db->prepare($sqlQuery);
         $sql->bindParam(':xxxx','xxxx',PDO::PARAM_STR);
         $sql->bindParam(':result_type','municipality',PDO::PARAM_STR);
         $sql->bindParam(':geocentrecom', true,PDO::PARAM_BOOL);
         $sql->bindParam(':modifxy', true,PDO::PARAM_BOOL);
         $sql->bindParam(':comirisables', true,PDO::PARAM_BOOL);
         $sql->bindParam(':num_fic', $numfic, PDO::PARAM_INT);
 
         $sql->execute();
 
         $aErreur = $sql->errorInfo();
         if (strlen($aErreur[2]) > 0) {
             echo $sqlQuery . "\n\n";
             $this->sendMailIncidentQuery($sqlQuery, $aErreur[2]);
             //die($aErreur[2]);
         }
    }

    /**
     * @author faniry
     * Attribution du DCOMIRIS 0000 and dcomiris  null
     */
    public function setDcomIrisTo0000WhenDcomirisIsNull($numfic){
        $sqlQuery="UPDATE $this->geosirene SET dcomiris = depcom || :xxxx, result_type= :result_type, geocentrecom = :geocentrecom, modifxy = :modifxy 
                WHERE dcomiris IS NULL 
                AND comirisables= :comirisables 
                AND  latitude IS NOT NULL 
                AND longitude IS NOT NULL 
                AND num_fic =:num_fic";
         $db=$this->getConnexion();
         $sql=$db->prepare($sqlQuery);
         $sql->bindParam(':xxxx','0000',PDO::PARAM_STR);
         $sql->bindParam(':result_type','municipality',PDO::PARAM_STR);
         $sql->bindParam(':geocentrecom', true,PDO::PARAM_BOOL);
         $sql->bindParam(':modifxy', true,PDO::PARAM_BOOL);
         $sql->bindParam(':comirisables', false,PDO::PARAM_BOOL);
         $sql->bindParam(':num_fic', $numfic, PDO::PARAM_INT);
         $sql->execute();
 
         $aErreur = $sql->errorInfo();
         if (strlen($aErreur[2]) > 0) {
             echo $sqlQuery . "\n\n";
             $this->sendMailIncidentQuery($sqlQuery, $aErreur[2]);
             //die($aErreur[2]);
         }
    }

    /**
     * @author faniry
     * Attribution du DCOMIRIS XXXX and result_type == null
     */
    public function setDcomIrisToXXXXWhenResultTypeIsNull($numfic){
        $sqlQuery="UPDATE $this->geosirene SET dcomiris = depcom || :xxxx, result_type= :result_type, geocentrecom = :geocentrecom, modifxy = :modifxy 
                WHERE result_type IS NULL 
                AND comirisables= :comirisables 
                AND  latitude IS NOT NULL 
                AND longitude IS NOT NULL 
                AND num_fic =:num_fic";
         $db=$this->getConnexion();
         $sql=$db->prepare($sqlQuery);
         $sql->bindParam(':xxxx','xxxx',PDO::PARAM_STR);
         $sql->bindParam(':result_type','municipality',PDO::PARAM_STR);
         $sql->bindParam(':geocentrecom', true,PDO::PARAM_BOOL);
         $sql->bindParam(':modifxy', true,PDO::PARAM_BOOL);
         $sql->bindParam(':comirisables', true,PDO::PARAM_BOOL);
         $sql->bindParam(':num_fic', $numfic, PDO::PARAM_INT);
         $sql->execute();
 
         $aErreur = $sql->errorInfo();
         if (strlen($aErreur[2]) > 0) {
             echo $sqlQuery . "\n\n";
             $this->sendMailIncidentQuery($sqlQuery, $aErreur[2]);
             //die($aErreur[2]);
         }
    }

    /**
     * @author faniry
     * Attribution du DCOMIRIS 0000 and result_type == null
     */
    public function setDcomIrisTo0000WhenResultTypeIsNull($numfic){
        $sqlQuery="UPDATE $this->geosirene SET dcomiris = depcom || :xxxx, result_type= :result_type, geocentrecom = :geocentrecom, modifxy = :modifxy 
                WHERE result_type IS NULL 
                AND comirisables= :comirisables 
                AND  latitude IS NOT NULL 
                AND longitude IS NOT NULL 
                AND num_fic = :num_fic";
         $db=$this->getConnexion();
         $sql=$db->prepare($sqlQuery);
         $sql->bindParam(':xxxx','0000',PDO::PARAM_STR);
         $sql->bindParam(':result_type','municipality',PDO::PARAM_STR);
         $sql->bindParam(':geocentrecom', true,PDO::PARAM_BOOL);
         $sql->bindParam(':modifxy', true,PDO::PARAM_BOOL);
         $sql->bindParam(':comirisables', false,PDO::PARAM_BOOL);
         $sql->bindParam(':num_fic', $numfic, PDO::PARAM_INT);
         $sql->execute();
 
         $aErreur = $sql->errorInfo();
         if (strlen($aErreur[2]) > 0) {
             echo $sqlQuery . "\n\n";
             $this->sendMailIncidentQuery($sqlQuery, $aErreur[2]);
             //die($aErreur[2]);
         }
    }

    /**
     * @author faniry 
     * Si modifxy = TRUE --> On met à jour les coordonnées XY avec le centroïde de la COMMUNE
     */
    public function updateLatLngWithCityCoordinates($numfic){
        $sqlQuery="UPDATE $this->geosirene as gm SET longitude= ST_X(ST_Centroid(ST_Transform(ST_SetSRID(geo.the_geom_3857,3857),4326)))
        , latitude=ST_Y(ST_Centroid(ST_Transform(ST_SetSRID(geo.the_geom_3857,3857),4326))) 
        FROM geo.commune_geo as geo 
        WHERE modifxy = :modifxy 
        AND gm.depcom = geo.depcom
        AND num_fic = :num_fic";
        $db=$this->getConnexion();
        $sql=$db->prepare($sqlQuery);
        $sql->bindParam(':modifxy', true,PDO::PARAM_BOOL);
        $sql->bindParam(':num_fic', $numfic, PDO::PARAM_INT);
        $sql->execute();
 
        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            echo $sqlQuery . "\n\n";
            $this->sendMailIncidentQuery($sqlQuery, $aErreur[2]);
            //die($aErreur[2]);
        }
    }

    /**
     * @author faniry 
     *  Calcul de la géométrie en EPSG:3857 à partir des coordonnées (longitude/latitude en EPSG:4326)
     */
    public function calculateGeometrieTo3857EPSG($numfic){
        $sqlQuery="UPDATE $this->geosirene SET 
        the_geom_3857 = ST_Transform(ST_SetSRID(ST_MakePoint(longitude,latitude),4326),3857) 
        WHERE longitude IS NOT NULL 
        AND latitude IS NOT NULL
        AND num_fic = :num_fic";
         $db=$this->getConnexion();
         $sql=$db->prepare($sqlQuery);
         $sql->bindParam(':num_fic', $numfic, PDO::PARAM_INT);
         $sql->execute();
         $aErreur = $sql->errorInfo();
         if (strlen($aErreur[2]) > 0) {
             echo $sqlQuery . "\n\n";
             $this->sendMailIncidentQuery($sqlQuery, $aErreur[2]);
             //die($aErreur[2]);
         }
    }

    public function updateGeosireneBanoFromApiSansNumFic($oResult, $siret) {

        //echo '----------------------------------------------------------updateGeosireneBano \n';

        $sLatitude = $oResult->features[0]->geometry->coordinates[1];
        $sLongitude = $oResult->features[0]->geometry->coordinates[0];
        $sResultLabel = $oResult->features[0]->properties->label;
        $sResultType = $oResult->features[0]->properties->type;
        $sResultId = $oResult->features[0]->properties->id;
        if ($oResult->features[0]->properties->housenumber) {
            $sResultHouseNumber = $oResult->features[0]->properties->housenumber;
        } else {
            $sResultHouseNumber = "";
        }

        $sResultName = $oResult->features[0]->properties->name;
        if ($oResult->features[0]->properties->street) {
            $sResultStreet = $oResult->features[0]->properties->street;
        } else {
            $sResultStreet = "";
        }

        $sResultPostCode = $oResult->features[0]->properties->postcode;
        $sResultCity = $oResult->features[0]->properties->city;
        $sResultContext = $oResult->features[0]->properties->context;
        $sResultCityCode = $oResult->features[0]->properties->citycode;
        $sResultScore = number_format($oResult->features[0]->properties->score, 2);
        $sAdresse = $oResult->query;

        $sQuery = "UPDATE poi.geosirene
                SET adresse=:adresse,
                latitude=:latitude, 
                longitude=:longitude, 
                result_label=:result_label, 
                result_score=:result_score, 
                result_type=:result_type, 
                result_id=:result_id, 
                result_housenumber=:result_housenumber, 
                result_name=:result_name, 
                result_street=:result_street, 
                result_postcode=:result_postcode, 
                result_city=:result_city, 
                result_context=:result_context, 
                result_citycode=:result_citycode
                WHERE 
                siret =:siret;";

        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);
        $sql->bindParam(':adresse', $sAdresse, PDO::PARAM_STR);
        $sql->bindParam(':latitude', $sLatitude, PDO::PARAM_STR);
        $sql->bindParam(':longitude', $sLongitude, PDO::PARAM_STR);
        $sql->bindParam(':result_label', $sResultLabel, PDO::PARAM_STR);
        $sql->bindParam(':result_score', $sResultScore, PDO::PARAM_STR);
        $sql->bindParam(':result_type', $sResultType, PDO::PARAM_STR);
        $sql->bindParam(':result_id', $sResultId, PDO::PARAM_STR);
        $sql->bindParam(':result_housenumber', $sResultHouseNumber, PDO::PARAM_STR);
        $sql->bindParam(':result_name', $sResultName, PDO::PARAM_STR);
        $sql->bindParam(':result_street', $sResultStreet, PDO::PARAM_STR);
        $sql->bindParam(':result_postcode', $sResultPostCode, PDO::PARAM_STR);
        $sql->bindParam(':result_city', $sResultCity, PDO::PARAM_STR);
        $sql->bindParam(':result_context', $sResultContext, PDO::PARAM_STR);
        $sql->bindParam(':result_citycode', $sResultCityCode, PDO::PARAM_STR);
        $sql->bindParam(':siret', $siret, PDO::PARAM_STR);

        $sql->execute();

        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            echo $sql->queryString . "\n\n";
            die($aErreur[2]);
        }

        //Util::logMajGeosirene("Fin update geosirene donnees BANO");
    }

    public function updateGeosireneFermeBanoFromApi($oResult, $siret, $numfic) {

        //echo '----------------------------------------------------------updateGeosireneBano \n';

        $sLatitude = $oResult->features[0]->geometry->coordinates[1];
        $sLongitude = $oResult->features[0]->geometry->coordinates[0];
        $sResultLabel = $oResult->features[0]->properties->label;
        $sResultType = $oResult->features[0]->properties->type;
        $sResultId = $oResult->features[0]->properties->id;
        if ($oResult->features[0]->properties->housenumber) {
            $sResultHouseNumber = $oResult->features[0]->properties->housenumber;
        } else {
            $sResultHouseNumber = "";
        }

        $sResultName = $oResult->features[0]->properties->name;
        if ($oResult->features[0]->properties->street) {
            $sResultStreet = $oResult->features[0]->properties->street;
        } else {
            $sResultStreet = "";
        }

        $sResultPostCode = $oResult->features[0]->properties->postcode;
        $sResultCity = $oResult->features[0]->properties->city;
        $sResultContext = $oResult->features[0]->properties->context;
        $sResultCityCode = $oResult->features[0]->properties->citycode;
        $sResultScore = number_format($oResult->features[0]->properties->score, 2);
        $sAdresse = $oResult->query;

        $sQuery = "UPDATE poi.sirene_etablissement_ferme
                SET adresse=:adresse,
                latitude=:latitude, 
                longitude=:longitude, 
                result_label=:result_label, 
                result_score=:result_score, 
                result_type=:result_type, 
                result_id=:result_id, 
                result_housenumber=:result_housenumber, 
                result_name=:result_name, 
                result_street=:result_street, 
                result_postcode=:result_postcode, 
                result_city=:result_city, 
                result_context=:result_context, 
                result_citycode=:result_citycode
                WHERE 
                siret =:siret;";

        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);
        $sql->bindParam(':adresse', $sAdresse, PDO::PARAM_STR);
        $sql->bindParam(':latitude', $sLatitude, PDO::PARAM_STR);
        $sql->bindParam(':longitude', $sLongitude, PDO::PARAM_STR);
        $sql->bindParam(':result_label', $sResultLabel, PDO::PARAM_STR);
        $sql->bindParam(':result_score', $sResultScore, PDO::PARAM_STR);
        $sql->bindParam(':result_type', $sResultType, PDO::PARAM_STR);
        $sql->bindParam(':result_id', $sResultId, PDO::PARAM_STR);
        $sql->bindParam(':result_housenumber', $sResultHouseNumber, PDO::PARAM_STR);
        $sql->bindParam(':result_name', $sResultName, PDO::PARAM_STR);
        $sql->bindParam(':result_street', $sResultStreet, PDO::PARAM_STR);
        $sql->bindParam(':result_postcode', $sResultPostCode, PDO::PARAM_STR);
        $sql->bindParam(':result_city', $sResultCity, PDO::PARAM_STR);
        $sql->bindParam(':result_context', $sResultContext, PDO::PARAM_STR);
        $sql->bindParam(':result_citycode', $sResultCityCode, PDO::PARAM_STR);
        $sql->bindParam(':siret', $siret, PDO::PARAM_STR);

        $sql->execute();

        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            echo $sql->queryString . "\n\n";
            die($aErreur[2]);
        }

        Util::logMajGeosirene("Fin update geosirene donnees BANO");
    }

//    public function updateGeosireneBanoFromApiProd($oResult, $siret, $numfic) {
//
//        //echo '----------------------------------------------------------updateGeosireneBano \n';
//
//        $sLatitude = $oResult->features[0]->geometry->coordinates[1];
//        $sLongitude = $oResult->features[0]->geometry->coordinates[0];
//        $sResultLabel = $oResult->features[0]->properties->label;
//        $sResultType = $oResult->features[0]->properties->type;
//        $sResultId = $oResult->features[0]->properties->id;
//        if ($oResult->features[0]->properties->housenumber) {
//           $sResultHouseNumber = $oResult->features[0]->properties->housenumber; 
//        } else {
//            $sResultHouseNumber = "";
//        }
//        
//        $sResultName = $oResult->features[0]->properties->name;
//        if ($oResult->features[0]->properties->street) {
//            $sResultStreet = $oResult->features[0]->properties->street;
//        } else {
//            $sResultStreet = "";
//        }
//        
//        $sResultPostCode = $oResult->features[0]->properties->postcode;
//        $sResultCity = $oResult->features[0]->properties->city;
//        $sResultContext = $oResult->features[0]->properties->context;
//        $sResultCityCode = $oResult->features[0]->properties->citycode;
//        $sResultScore = number_format($oResult->features[0]->properties->score, 2);
//        $sAdresse = $oResult->query;
//
//        $sQuery = "UPDATE poi.geosirene
//                SET adresse=:adresse,
//                latitude=:latitude, 
//                longitude=:longitude, 
//                result_label=:result_label, 
//                result_score=:result_score, 
//                result_type=:result_type, 
//                result_id=:result_id, 
//                result_housenumber=:result_housenumber, 
//                result_name=:result_name, 
//                result_street=:result_street, 
//                result_postcode=:result_postcode, 
//                result_city=:result_city, 
//                result_context=:result_context, 
//                result_citycode=:result_citycode
//                WHERE num_fic=:num_fic AND 
//                siret =:siret;";
//
//        $db = $this->getConnexionProd();
//        $sql = $db->prepare($sQuery);
//        $sql->bindParam(':adresse', $sAdresse, PDO::PARAM_STR);
//        $sql->bindParam(':latitude', $sLatitude, PDO::PARAM_STR);
//        $sql->bindParam(':longitude', $sLongitude, PDO::PARAM_STR);
//        $sql->bindParam(':result_label', $sResultLabel, PDO::PARAM_STR);
//        $sql->bindParam(':result_score', $sResultScore, PDO::PARAM_STR);
//        $sql->bindParam(':result_type', $sResultType, PDO::PARAM_STR);
//        $sql->bindParam(':result_id', $sResultId, PDO::PARAM_STR);
//        $sql->bindParam(':result_housenumber', $sResultHouseNumber, PDO::PARAM_STR);
//        $sql->bindParam(':result_name', $sResultName, PDO::PARAM_STR);
//        $sql->bindParam(':result_street', $sResultStreet, PDO::PARAM_STR);
//        $sql->bindParam(':result_postcode', $sResultPostCode, PDO::PARAM_STR);
//        $sql->bindParam(':result_city', $sResultCity, PDO::PARAM_STR);
//        $sql->bindParam(':result_context', $sResultContext, PDO::PARAM_STR);
//        $sql->bindParam(':result_citycode', $sResultCityCode, PDO::PARAM_STR);
//        $sql->bindParam(':num_fic', $numfic, PDO::PARAM_INT);
//        $sql->bindParam(':siret', $siret, PDO::PARAM_STR);
//
//        $sql->execute();
//
//        $aErreur = $sql->errorInfo();
//        if (strlen($aErreur[2]) > 0) {
//            echo $sql->queryString . "\n\n";
//            die($aErreur[2]);
//        }
//
//        Util::logMajGeosirene("Fin update geosirene donnees BANO");
//    }
//    public function updateGeosireneBanoFromApiProdSansNumFic($oResult, $siret) {
//
//        //echo '----------------------------------------------------------updateGeosireneBano \n';
//
//        $sLatitude = $oResult->features[0]->geometry->coordinates[1];
//        $sLongitude = $oResult->features[0]->geometry->coordinates[0];
//        $sResultLabel = $oResult->features[0]->properties->label;
//        $sResultType = $oResult->features[0]->properties->type;
//        $sResultId = $oResult->features[0]->properties->id;
//        if ($oResult->features[0]->properties->housenumber) {
//           $sResultHouseNumber = $oResult->features[0]->properties->housenumber; 
//        } else {
//            $sResultHouseNumber = "";
//        }
//        
//        $sResultName = $oResult->features[0]->properties->name;
//        if ($oResult->features[0]->properties->street) {
//            $sResultStreet = $oResult->features[0]->properties->street;
//        } else {
//            $sResultStreet = "";
//        }
//        
//        $sResultPostCode = $oResult->features[0]->properties->postcode;
//        $sResultCity = $oResult->features[0]->properties->city;
//        $sResultContext = $oResult->features[0]->properties->context;
//        $sResultCityCode = $oResult->features[0]->properties->citycode;
//        $sResultScore = number_format($oResult->features[0]->properties->score, 2);
//        $sAdresse = $oResult->query;
//
//        $sQuery = "UPDATE poi.geosirene
//                SET adresse=:adresse,
//                latitude=:latitude, 
//                longitude=:longitude, 
//                result_label=:result_label, 
//                result_score=:result_score, 
//                result_type=:result_type, 
//                result_id=:result_id, 
//                result_housenumber=:result_housenumber, 
//                result_name=:result_name, 
//                result_street=:result_street, 
//                result_postcode=:result_postcode, 
//                result_city=:result_city, 
//                result_context=:result_context, 
//                result_citycode=:result_citycode
//                WHERE 
//                siret =:siret;";
//
//        $db = $this->getConnexionProd();
//        $sql = $db->prepare($sQuery);
//        $sql->bindParam(':adresse', $sAdresse, PDO::PARAM_STR);
//        $sql->bindParam(':latitude', $sLatitude, PDO::PARAM_STR);
//        $sql->bindParam(':longitude', $sLongitude, PDO::PARAM_STR);
//        $sql->bindParam(':result_label', $sResultLabel, PDO::PARAM_STR);
//        $sql->bindParam(':result_score', $sResultScore, PDO::PARAM_STR);
//        $sql->bindParam(':result_type', $sResultType, PDO::PARAM_STR);
//        $sql->bindParam(':result_id', $sResultId, PDO::PARAM_STR);
//        $sql->bindParam(':result_housenumber', $sResultHouseNumber, PDO::PARAM_STR);
//        $sql->bindParam(':result_name', $sResultName, PDO::PARAM_STR);
//        $sql->bindParam(':result_street', $sResultStreet, PDO::PARAM_STR);
//        $sql->bindParam(':result_postcode', $sResultPostCode, PDO::PARAM_STR);
//        $sql->bindParam(':result_city', $sResultCity, PDO::PARAM_STR);
//        $sql->bindParam(':result_context', $sResultContext, PDO::PARAM_STR);
//        $sql->bindParam(':result_citycode', $sResultCityCode, PDO::PARAM_STR);
//        $sql->bindParam(':siret', $siret, PDO::PARAM_STR);
//
//        $sql->execute();
//
//        $aErreur = $sql->errorInfo();
//        if (strlen($aErreur[2]) > 0) {
//            echo $sql->queryString . "\n\n";
//            die($aErreur[2]);
//        }
//
//        Util::logMajGeosirene("Fin update geosirene donnees BANO");
//    }
//    public function updateGeosireneFermeBanoFromApiProd($oResult, $siret, $numfic) {
//
//        //echo '----------------------------------------------------------updateGeosireneBano \n';
//
//        $sLatitude = $oResult->features[0]->geometry->coordinates[1];
//        $sLongitude = $oResult->features[0]->geometry->coordinates[0];
//        $sResultLabel = $oResult->features[0]->properties->label;
//        $sResultType = $oResult->features[0]->properties->type;
//        $sResultId = $oResult->features[0]->properties->id;
//        if ($oResult->features[0]->properties->housenumber) {
//           $sResultHouseNumber = $oResult->features[0]->properties->housenumber; 
//        } else {
//            $sResultHouseNumber = "";
//        }
//        
//        $sResultName = $oResult->features[0]->properties->name;
//        if ($oResult->features[0]->properties->street) {
//            $sResultStreet = $oResult->features[0]->properties->street;
//        } else {
//            $sResultStreet = "";
//        }
//        
//        $sResultPostCode = $oResult->features[0]->properties->postcode;
//        $sResultCity = $oResult->features[0]->properties->city;
//        $sResultContext = $oResult->features[0]->properties->context;
//        $sResultCityCode = $oResult->features[0]->properties->citycode;
//        $sResultScore = number_format($oResult->features[0]->properties->score, 2);
//        $sAdresse = $oResult->query;
//
//        $sQuery = "UPDATE poi.sirene_etablissement_ferme
//                SET adresse=:adresse,
//                latitude=:latitude, 
//                longitude=:longitude, 
//                result_label=:result_label, 
//                result_score=:result_score, 
//                result_type=:result_type, 
//                result_id=:result_id, 
//                result_housenumber=:result_housenumber, 
//                result_name=:result_name, 
//                result_street=:result_street, 
//                result_postcode=:result_postcode, 
//                result_city=:result_city, 
//                result_context=:result_context, 
//                result_citycode=:result_citycode
//                WHERE 
//                siret =:siret;";
//
//        $db = $this->getConnexionProd();
//        $sql = $db->prepare($sQuery);
//        $sql->bindParam(':adresse', $sAdresse, PDO::PARAM_STR);
//        $sql->bindParam(':latitude', $sLatitude, PDO::PARAM_STR);
//        $sql->bindParam(':longitude', $sLongitude, PDO::PARAM_STR);
//        $sql->bindParam(':result_label', $sResultLabel, PDO::PARAM_STR);
//        $sql->bindParam(':result_score', $sResultScore, PDO::PARAM_STR);
//        $sql->bindParam(':result_type', $sResultType, PDO::PARAM_STR);
//        $sql->bindParam(':result_id', $sResultId, PDO::PARAM_STR);
//        $sql->bindParam(':result_housenumber', $sResultHouseNumber, PDO::PARAM_STR);
//        $sql->bindParam(':result_name', $sResultName, PDO::PARAM_STR);
//        $sql->bindParam(':result_street', $sResultStreet, PDO::PARAM_STR);
//        $sql->bindParam(':result_postcode', $sResultPostCode, PDO::PARAM_STR);
//        $sql->bindParam(':result_city', $sResultCity, PDO::PARAM_STR);
//        $sql->bindParam(':result_context', $sResultContext, PDO::PARAM_STR);
//        $sql->bindParam(':result_citycode', $sResultCityCode, PDO::PARAM_STR);
//        $sql->bindParam(':siret', $siret, PDO::PARAM_STR);
//
//        $sql->execute();
//
//        $aErreur = $sql->errorInfo();
//        if (strlen($aErreur[2]) > 0) {
//            echo $sql->queryString . "\n\n";
//            die($aErreur[2]);
//        }
//
//        Util::logMajGeosirene("Fin update geosirene donnees BANO");
//    }


    public function updateGeosireneBanoStockFromApi($oResult, $siret, $numfic) {

        //echo '----------------------------------------------------------updateGeosireneBano \n';

        $sLatitude = $oResult->features[0]->geometry->coordinates[1];
        $sLongitude = $oResult->features[0]->geometry->coordinates[0];
        $sResultLabel = $oResult->features[0]->properties->label;
        $sResultType = $oResult->features[0]->properties->type;
        $sResultId = $oResult->features[0]->properties->id;
        $sResultHouseNumber = $oResult->features[0]->properties->housenumber;
        $sResultName = $oResult->features[0]->properties->name;
        $sResultStreet = $oResult->features[0]->properties->street;
        $sResultPostCode = $oResult->features[0]->properties->postcode;
        $sResultCity = $oResult->features[0]->properties->city;
        $sResultContext = $oResult->features[0]->properties->context;
        $sResultCityCode = $oResult->features[0]->properties->citycode;
        $sResultScore = number_format($oResult->features[0]->properties->score, 2);
        $sAdresse = $oResult->query;

        $sQuery = "UPDATE poi.geosirene_stock
                SET adresse=:adresse,
                latitude=:latitude, 
                longitude=:longitude, 
                result_label=:result_label, 
                result_score=:result_score, 
                result_type=:result_type, 
                result_id=:result_id, 
                result_housenumber=:result_housenumber, 
                result_name=:result_name, 
                result_street=:result_street, 
                result_postcode=:result_postcode, 
                result_city=:result_city, 
                result_context=:result_context, 
                result_citycode=:result_citycode
                WHERE num_fic=:num_fic AND 
                siret =:siret;";

        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);
        $sql->bindParam(':adresse', $sAdresse, PDO::PARAM_STR);
        $sql->bindParam(':latitude', $sLatitude, PDO::PARAM_STR);
        $sql->bindParam(':longitude', $sLongitude, PDO::PARAM_STR);
        $sql->bindParam(':result_label', $sResultLabel, PDO::PARAM_STR);
        $sql->bindParam(':result_score', $sResultScore, PDO::PARAM_STR);
        $sql->bindParam(':result_type', $sResultType, PDO::PARAM_STR);
        $sql->bindParam(':result_id', $sResultId, PDO::PARAM_STR);
        $sql->bindParam(':result_housenumber', $sResultHouseNumber, PDO::PARAM_STR);
        $sql->bindParam(':result_name', $sResultName, PDO::PARAM_STR);
        $sql->bindParam(':result_street', $sResultStreet, PDO::PARAM_STR);
        $sql->bindParam(':result_postcode', $sResultPostCode, PDO::PARAM_STR);
        $sql->bindParam(':result_city', $sResultCity, PDO::PARAM_STR);
        $sql->bindParam(':result_context', $sResultContext, PDO::PARAM_STR);
        $sql->bindParam(':result_citycode', $sResultCityCode, PDO::PARAM_STR);
        $sql->bindParam(':num_fic', $numfic, PDO::PARAM_INT);
        $sql->bindParam(':siret', $siret, PDO::PARAM_STR);

        $sql->execute();

        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            echo $sql->queryString . "\n\n";
            die($aErreur[2]);
        }



        Util::logMajGeosirene("Fin update geosirene donnees BANO");
    }

    public function updateGeosireneBanoTmp($numfic) {

        //echo '----------------------------------------------------------updateGeosireneBano <>';

        $sFile = FILE_SORTIE_BANO;
        if (file_exists($sFile)) {
            echo 'file exist \n';
        } else {
            die('file nt exist');
        }
        if (!$file = @fopen($sFile, "r")) {
            die("Echec de l'ouverture du fichier");
        } else {
            $nulLingne = 0;

            while (!feof($file)) {

                $aTabBano = fgetcsv($file, '', ";");

                if ($nulLingne > 0) {

                    if (count($aTabBano) > 0 && $aTabBano[6]) {

                        echo " UPDATE GEOSIRENE BANO => " . $aTabBano[0] . "\n";


                        $sQuery = "UPDATE poi.geosirene_tmp_old
                        SET adresse=:adresse,
                        latitude=:latitude, 
                        longitude=:longitude, 
                        result_label=:result_label, 
                        result_score=:result_score, 
                        result_type=:result_type, 
                        result_id=:result_id, 
                        result_housenumber=:result_housenumber, 
                        result_name=:result_name, 
                        result_street=:result_street, 
                        result_postcode=:result_postcode, 
                        result_city=:result_city, 
                        result_context=:result_context, 
                        result_citycode=:result_citycode
                        WHERE num_fic=:num_fic AND 
                        siret =:siret;";

                        $db = $this->getConnexion();
                        $sql = $db->prepare($sQuery);
                        $sql->bindParam(':adresse', $aTabBano[1], PDO::PARAM_STR);
                        $sql->bindParam(':latitude', $aTabBano[2], PDO::PARAM_STR);
                        $sql->bindParam(':longitude', $aTabBano[3], PDO::PARAM_STR);
                        $sql->bindParam(':result_label', $aTabBano[4], PDO::PARAM_STR);
                        $sql->bindParam(':result_score', $aTabBano[5], PDO::PARAM_STR);
                        $sql->bindParam(':result_type', $aTabBano[6], PDO::PARAM_STR);
                        $sql->bindParam(':result_id', $aTabBano[7], PDO::PARAM_STR);
                        $sql->bindParam(':result_housenumber', $aTabBano[8], PDO::PARAM_STR);
                        $sql->bindParam(':result_name', $aTabBano[9], PDO::PARAM_STR);
                        $sql->bindParam(':result_street', $aTabBano[10], PDO::PARAM_STR);
                        $sql->bindParam(':result_postcode', $aTabBano[11], PDO::PARAM_STR);
                        $sql->bindParam(':result_city', $aTabBano[12], PDO::PARAM_STR);
                        $sql->bindParam(':result_context', $aTabBano[13], PDO::PARAM_STR);
                        $sql->bindParam(':result_citycode', $aTabBano[14], PDO::PARAM_STR);
                        $sql->bindParam(':num_fic', $numfic, PDO::PARAM_INT);
                        $sql->bindParam(':siret', $aTabBano[0], PDO::PARAM_STR);

                        $sql->execute();

                        $aErreur = $sql->errorInfo();
                        if (strlen($aErreur[2]) > 0) {
                            echo $sql->queryString . "\n\n";
                            die($aErreur[2]);
                        }
                    }
                }
                $nulLingne ++;
            }
            fclose($file);
        }
        Util::logMajGeosirene("Fin update geosirene donnees BANO");
    }

    public function updateGeosireneStockBano($aTabBano) {

        //print_r($aTabBano);
        //die();
        //echo '----------------------------------------------------------updateGeosireneBano \n';



        $sQuery = "UPDATE poi.geosirene_stock
                        SET  latitude =:latitude, 
                        longitude =:longitude, 
                        dcomiris =:dcomiris, 
                        nom_iris =:nom_iris, 
                        depcom =:depcom, 
                        nom_commune =:nom_commune, 
                        code_departement =:code_departement, 
                        nom_departement =:nom_departement, 
                        the_geom_3857=:the_geom_3857, 
                        streetview=:streetview                         
                        WHERE 
                        siret =:siret;";

        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);
        $sql->bindParam(':latitude', $aTabBano['latitude'], PDO::PARAM_STR);
        $sql->bindParam(':longitude', $aTabBano['longitude'], PDO::PARAM_STR);
        $sql->bindParam(':dcomiris', $aTabBano['dcomiris'], PDO::PARAM_STR);
        $sql->bindParam(':nom_iris', $aTabBano['nom_iris'], PDO::PARAM_STR);
        $sql->bindParam(':depcom', $aTabBano['depcom'], PDO::PARAM_STR);
        $sql->bindParam(':nom_commune', $aTabBano['nom_commune'], PDO::PARAM_STR);
        $sql->bindParam(':code_departement', $aTabBano['code_departement'], PDO::PARAM_STR);
        $sql->bindParam(':nom_departement', $aTabBano['nom_departement'], PDO::PARAM_STR);
        $sql->bindParam(':the_geom_3857', $aTabBano['the_geom_3857'], PDO::PARAM_STR);
        $sql->bindParam(':streetview', $aTabBano['streetview'], PDO::PARAM_STR);
        $sql->bindParam(':siret', $aTabBano['siret'], PDO::PARAM_STR);

        $sql->execute();

        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            echo $sql->queryString . "\n\n";
            die($aErreur[2]);
        }
    }

    public function updateGeosireneBanoStock() {

        //echo '----------------------------------------------------------updateGeosireneBano \n';

        $sFile = FILE_SORTIE_BANO;
        if (file_exists($sFile)) {
            echo 'file exist \n';
        } else {
            die('file nt exist');
        }
        if (!$file = @fopen($sFile, "r")) {
            die("Echec de l'ouverture du fichier");
        } else {
            $nulLingne = 0;

            while (!feof($file)) {

                $aTabBano = fgetcsv($file, '', ";");
                print_r($aTabBano) . "\n\n";
                //die();
                if ($nulLingne > 0) {

                    if (count($aTabBano) > 0 && $aTabBano[6]) {

                        echo " UPDATE GEOSIRENE BANO => " . $aTabBano[0] . '\n\n';

                        $numfic = 1;

                        $sQuery = "UPDATE poi.geosirene_stock
                        SET adresse=:adresse,
                        latitude=:latitude, 
                        longitude=:longitude, 
                        result_label=:result_label, 
                        result_score=:result_score, 
                        result_type=:result_type, 
                        result_id=:result_id, 
                        result_housenumber=:result_housenumber, 
                        result_name=:result_name, 
                        result_street=:result_street, 
                        result_postcode=:result_postcode, 
                        result_city=:result_city, 
                        result_context=:result_context, 
                        result_citycode=:result_citycode
                        WHERE num_fic=:num_fic AND 
                        siret =:siret;";

                        $db = $this->getConnexion();
                        $sql = $db->prepare($sQuery);
                        $sql->bindParam(':adresse', $aTabBano[1], PDO::PARAM_STR);
                        $sql->bindParam(':latitude', $aTabBano[2], PDO::PARAM_STR);
                        $sql->bindParam(':longitude', $aTabBano[3], PDO::PARAM_STR);
                        $sql->bindParam(':result_label', $aTabBano[4], PDO::PARAM_STR);
                        $sql->bindParam(':result_score', $aTabBano[5], PDO::PARAM_STR);
                        $sql->bindParam(':result_type', $aTabBano[6], PDO::PARAM_STR);
                        $sql->bindParam(':result_id', $aTabBano[7], PDO::PARAM_STR);
                        $sql->bindParam(':result_housenumber', $aTabBano[8], PDO::PARAM_STR);
                        $sql->bindParam(':result_name', $aTabBano[9], PDO::PARAM_STR);
                        $sql->bindParam(':result_street', $aTabBano[10], PDO::PARAM_STR);
                        $sql->bindParam(':result_postcode', $aTabBano[11], PDO::PARAM_STR);
                        $sql->bindParam(':result_city', $aTabBano[12], PDO::PARAM_STR);
                        $sql->bindParam(':result_context', $aTabBano[13], PDO::PARAM_STR);
                        $sql->bindParam(':result_citycode', $aTabBano[14], PDO::PARAM_STR);
                        $sql->bindParam(':num_fic', $numfic, PDO::PARAM_INT);
                        $sql->bindParam(':siret', $aTabBano[0], PDO::PARAM_STR);

                        $sql->execute();

                        $aErreur = $sql->errorInfo();
                        if (strlen($aErreur[2]) > 0) {
                            echo $sql->queryString . "\n\n";
                            die($aErreur[2]);
                        }
                    }
                }
                $nulLingne ++;
            }
            fclose($file);
        }
    }

    public function insertGeosirene($oStock, $aTabChgt, $numFic, $sDateFormat, $bCreation, $denominationGeoscar) {


//die(print_r($oStock));
        //echo '------------------------------insertGeosirene \n';
        $bEntreDiffComm = 'FALSE';
        $bAdresseChange = 'FALSE';
        $bChgtEtatAdmin = 'FALSE';
        $bChgtTrancheEff = 'FALSE';
        $bChgtActivitePrincipale = 'FALSE';

        if (!is_null($aTabChgt)) {
            if (in_array('statutdiffusionetablissement', $aTabChgt)) {
                //echo "*****************MODIF = statutdiffusionetablissement\n";
                $bEntreDiffComm = 'TRUE';
            }
            if (in_array('numerovoieetablissement', $aTabChgt) || in_array('typevoieetablissement', $aTabChgt) || in_array('libellevoieetablissement', $aTabChgt) || in_array('codepostaletablissement', $aTabChgt) || in_array('libellecommuneetablissement', $aTabChgt)) {
                $bAdresseChange = 'TRUE';
                $this->iCountChgAdresse++;
                // echo "*****************MODIF = numerovoieetablissement\n";
            }
            if (in_array('etatAdministratifEtablissement', $aTabChgt)) {
                $bChgtEtatAdmin = 'TRUE';
                // echo "*****************MODIF = etatAdministratifEtablissement\n";
            }
            if (in_array('trancheEffectifsEtablissement', $aTabChgt)) {
                $bChgtTrancheEff = 'TRUE';
                // echo "*****************MODIF = trancheEffectifsEtablissement\n";
            }
            if (in_array('activiteprincipaleetablissement', $aTabChgt)) {
                $bChgtActivitePrincipale = 'TRUE';
                //echo "*****************MODIF = activiteprincipaleetablissement\n";
            }
        }



        $sReq = "INSERT INTO poi.geosirene(siren, nic, siret, statutdiffusionetablissement, datecreationetablissement, 
        trancheeffectifsetablissement, anneeeffectifsetablissement, activiteprincipaleregistremetiersetablissement, 
        datederniertraitementetablissement, etablissementsiege, nombreperiodesetablissement, 
        complementadresseetablissement, numerovoieetablissement, indicerepetitionetablissement, 
        typevoieetablissement, libellevoieetablissement, codepostaletablissement, libellecommuneetablissement, 
        libellecommuneetrangeretablissement, distributionspecialeetablissement, codecommuneetablissement, codecedexetablissement, 
        libellecedexetablissement, codepaysetrangeretablissement, libellepaysetrangeretablissement, complementadresse2etablissement, 
        numerovoie2etablissement, indicerepetition2etablissement, typevoie2etablissement, libellevoie2etablissement, 
        codepostal2etablissement, libellecommune2etablissement, libellecommuneetranger2etablissement, distributionspeciale2etablissement, 
        codecommune2etablissement, codecedex2etablissement, libellecedex2etablissement, codepaysetranger2etablissement, 
        libellepaysetranger2etablissement, datedebut, etatadministratifetablissement, enseigne1etablissement, enseigne2etablissement, 
        enseigne3etablissement, denominationusuelleetablissement, activiteprincipaleetablissement, nomenclatureactiviteprincipaleetablissement, 
        caractereemployeuretablissement,
        entree_champ_diffusion_commerciale, changement_activiteprincipaleetablissement, demenagement, changement_etat_administratif, 
	modification_tranche_nb_salaries, num_fic, date_integration, creation, denomination_geoscar)
        VALUES 
        (:siren, :nic, :siret, :statutdiffusionetablissement, :datecreationetablissement, 
        :trancheeffectifsetablissement, :anneeeffectifsetablissement, :activiteprincipaleregistremetiersetablissement, 
        :datederniertraitementetablissement, :etablissementsiege, :nombreperiodesetablissement, 
        :complementadresseetablissement, :numerovoieetablissement, :indicerepetitionetablissement, 
        :typevoieetablissement, :libellevoieetablissement, :codepostaletablissement, :libellecommuneetablissement, 
        :libellecommuneetrangeretablissement, :distributionspecialeetablissement, :codecommuneetablissement, :codecedexetablissement, 
        :libellecedexetablissement, :codepaysetrangeretablissement, :libellepaysetrangeretablissement, :complementadresse2etablissement, 
        :numerovoie2etablissement, :indicerepetition2etablissement, :typevoie2etablissement, :libellevoie2etablissement, 
        :codepostal2etablissement, :libellecommune2etablissement, :libellecommuneetranger2etablissement, :distributionspeciale2etablissement, 
        :codecommune2etablissement, :codecedex2etablissement, :libellecedex2etablissement, :codepaysetranger2etablissement, 
        :libellepaysetranger2etablissement, :datedebut, :etatadministratifetablissement, :enseigne1etablissement, :enseigne2etablissement, 
        :enseigne3etablissement, :denominationusuelleetablissement, :activiteprincipaleetablissement, :nomenclatureactiviteprincipaleetablissement, 
        :caractereemployeuretablissement, :entree_champ_diffusion_commerciale, :changement_activiteprincipaleetablissement, :demenagement, :changement_etat_administratif, 
	:modification_tranche_nb_salaries, :num_fic, :date_integration, :creation, :denomination_geoscar)";

        //echo "--------------------ACTICITE = " . $oStock['activiteprincipaleetablissement'] . "\n";
        $sActivite = str_replace(".", "", $oStock['activiteprincipaleetablissement']);
        //echo "--------------------ACTICITE APRES = " . $sActivite . "\n";

        $db = $this->getConnexion();
        $sql = $db->prepare($sReq);
        $sql->bindParam(':siren', $oStock['siren'], PDO::PARAM_STR);
        $sql->bindParam(':nic', $oStock['nic'], PDO::PARAM_STR);
        $sql->bindParam(':siret', $oStock['siret'], PDO::PARAM_STR);
        $sql->bindParam(':statutdiffusionetablissement', $oStock['statutdiffusionetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':datecreationetablissement', $oStock['datecreationetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':trancheeffectifsetablissement', $oStock['trancheeffectifsetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':anneeeffectifsetablissement', $oStock['anneeeffectifsetablissement'], PDO::PARAM_INT);
        $sql->bindParam(':activiteprincipaleregistremetiersetablissement', $oStock['activiteprincipaleregistremetiersetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':datederniertraitementetablissement', $oStock['datederniertraitementetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':etablissementsiege', $oStock['etablissementsiege'], PDO::PARAM_BOOL);
        $sql->bindParam(':nombreperiodesetablissement', $oStock['nombreperiodesetablissement'], PDO::PARAM_INT);
        $sql->bindParam(':complementadresseetablissement', $oStock['complementadresseetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':numerovoieetablissement', $oStock['numerovoieetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':indicerepetitionetablissement', $oStock['indicerepetitionetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':typevoieetablissement', $oStock['typevoieetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellevoieetablissement', $oStock['libellevoieetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codepostaletablissement', $oStock['codepostaletablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellecommuneetablissement', $oStock['libellecommuneetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellecommuneetrangeretablissement', $oStock['libellecommuneetrangeretablissement'], PDO::PARAM_STR);
        $sql->bindParam(':distributionspecialeetablissement', $oStock['distributionspecialeetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codecommuneetablissement', $oStock['codecommuneetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codecedexetablissement', $oStock['codecedexetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellecedexetablissement', $oStock['libellecedexetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codepaysetrangeretablissement', $oStock['codepaysetrangeretablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellepaysetrangeretablissement', $oStock['libellepaysetrangeretablissement'], PDO::PARAM_STR);
        $sql->bindParam(':complementadresse2etablissement', $oStock['complementadresse2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':numerovoie2etablissement', $oStock['numerovoie2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':indicerepetition2etablissement', $oStock['indicerepetition2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':typevoie2etablissement', $oStock['typevoie2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellevoie2etablissement', $oStock['libellevoie2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codepostal2etablissement', $oStock['codepostal2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellecommune2etablissement', $oStock['libellecommune2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellecommuneetranger2etablissement', $oStock['libellecommuneetranger2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':distributionspeciale2etablissement', $oStock['distributionspeciale2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codecommune2etablissement', $oStock['codecommune2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codecedex2etablissement', $oStock['codecedex2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellecedex2etablissement', $oStock['libellecedex2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codepaysetranger2etablissement', $oStock['codepaysetranger2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellepaysetranger2etablissement', $oStock['libellepaysetranger2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':datedebut', $oStock['datedebut'], PDO::PARAM_STR);
        $sql->bindParam(':etatadministratifetablissement', $oStock['etatadministratifetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':enseigne1etablissement', $oStock['enseigne1etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':enseigne2etablissement', $oStock['enseigne2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':enseigne3etablissement', $oStock['enseigne3etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':denominationusuelleetablissement', $oStock['denominationusuelleetablissement'], PDO::PARAM_STR);
        $sql->bindValue(':activiteprincipaleetablissement', str_replace(".", "", $oStock['activiteprincipaleetablissement']), PDO::PARAM_STR);
        $sql->bindParam(':nomenclatureactiviteprincipaleetablissement', $oStock['nomenclatureactiviteprincipaleetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':caractereemployeuretablissement', $oStock['caractereemployeuretablissement'], PDO::PARAM_STR);
        $sql->bindParam(':entree_champ_diffusion_commerciale', $bEntreDiffComm, PDO::PARAM_BOOL);
        $sql->bindParam(':changement_activiteprincipaleetablissement', $bChgtActivitePrincipale, PDO::PARAM_BOOL);
        $sql->bindParam(':demenagement', $bAdresseChange, PDO::PARAM_BOOL);
        $sql->bindParam(':changement_etat_administratif', $bChgtEtatAdmin, PDO::PARAM_BOOL);
        $sql->bindParam(':modification_tranche_nb_salaries', $bChgtTrancheEff, PDO::PARAM_BOOL);
        $sql->bindParam(':num_fic', $numFic, PDO::PARAM_INT);
        $sql->bindParam(':date_integration', $sDateFormat, PDO::PARAM_STR);
        $sql->bindParam(':creation', $bCreation, PDO::PARAM_BOOL);
        $sql->bindParam(':denomination_geoscar', $denominationGeoscar, PDO::PARAM_BOOL);

        try{
            $sql->execute();
        }catch(Exception $e){
            echo 'Exception reçue : '. $e . "\n";
        }
//
        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            echo $sReq . "\n\n";
            $this->sendMailIncidentQuery($sReq, $aErreur[2]);
            //die($aErreur[2]);
        }
        //echo "============================================ ADRESSE CHG = " . $this->iCountChgAdresse . "===================================================\n\n";
    }

//    public function insertGeosireneProd($oStock, $aTabChgt, $numFic, $sDateFormat, $bCreation) {
//
//
////die(print_r($oStock));
//
//        //echo '------------------------------insertGeosirene \n';
//        $bEntreDiffComm = 'FALSE';
//        $bAdresseChange = 'FALSE';
//        $bChgtEtatAdmin = 'FALSE';
//        $bChgtTrancheEff = 'FALSE';
//        $bChgtActivitePrincipale = 'FALSE';
//
//        if (!is_null($aTabChgt)) {
//            if (in_array('statutdiffusionetablissement', $aTabChgt)) {
//                //echo "*****************MODIF = statutdiffusionetablissement\n";
//                $bEntreDiffComm = 'TRUE';
//            }
//            if (in_array('numerovoieetablissement', $aTabChgt) || in_array('typevoieetablissement', $aTabChgt) || in_array('libellevoieetablissement', $aTabChgt) || in_array('codepostaletablissement', $aTabChgt) || in_array('libellecommuneetablissement', $aTabChgt)) {
//                $bAdresseChange = 'TRUE';
//                $this->iCountChgAdresse++;
//                //echo "*****************MODIF = numerovoieetablissement\n";
//            }
//            if (in_array('etatAdministratifEtablissement', $aTabChgt)) {
//                $bChgtEtatAdmin = 'TRUE';
//                //echo "*****************MODIF = etatAdministratifEtablissement\n";
//            }
//            if (in_array('trancheEffectifsEtablissement', $aTabChgt)) {
//                $bChgtTrancheEff = 'TRUE';
//                //echo "*****************MODIF = trancheEffectifsEtablissement\n";
//            }
//            if (in_array('activiteprincipaleetablissement', $aTabChgt)) {
//                $bChgtActivitePrincipale = 'TRUE';
//                //echo "*****************MODIF = activiteprincipaleetablissement\n";
//            }
//        }
//
//
//
//        $sReq = "INSERT INTO poi.geosirene(siren, nic, siret, statutdiffusionetablissement, datecreationetablissement, 
//        trancheeffectifsetablissement, anneeeffectifsetablissement, activiteprincipaleregistremetiersetablissement, 
//        datederniertraitementetablissement, etablissementsiege, nombreperiodesetablissement, 
//        complementadresseetablissement, numerovoieetablissement, indicerepetitionetablissement, 
//        typevoieetablissement, libellevoieetablissement, codepostaletablissement, libellecommuneetablissement, 
//        libellecommuneetrangeretablissement, distributionspecialeetablissement, codecommuneetablissement, codecedexetablissement, 
//        libellecedexetablissement, codepaysetrangeretablissement, libellepaysetrangeretablissement, complementadresse2etablissement, 
//        numerovoie2etablissement, indicerepetition2etablissement, typevoie2etablissement, libellevoie2etablissement, 
//        codepostal2etablissement, libellecommune2etablissement, libellecommuneetranger2etablissement, distributionspeciale2etablissement, 
//        codecommune2etablissement, codecedex2etablissement, libellecedex2etablissement, codepaysetranger2etablissement, 
//        libellepaysetranger2etablissement, datedebut, etatadministratifetablissement, enseigne1etablissement, enseigne2etablissement, 
//        enseigne3etablissement, denominationusuelleetablissement, activiteprincipaleetablissement, nomenclatureactiviteprincipaleetablissement, 
//        caractereemployeuretablissement,
//        entree_champ_diffusion_commerciale, changement_activiteprincipaleetablissement, demenagement, changement_etat_administratif, 
//	modification_tranche_nb_salaries, num_fic, date_integration, creation)
//        VALUES 
//        (:siren, :nic, :siret, :statutdiffusionetablissement, :datecreationetablissement, 
//        :trancheeffectifsetablissement, :anneeeffectifsetablissement, :activiteprincipaleregistremetiersetablissement, 
//        :datederniertraitementetablissement, :etablissementsiege, :nombreperiodesetablissement, 
//        :complementadresseetablissement, :numerovoieetablissement, :indicerepetitionetablissement, 
//        :typevoieetablissement, :libellevoieetablissement, :codepostaletablissement, :libellecommuneetablissement, 
//        :libellecommuneetrangeretablissement, :distributionspecialeetablissement, :codecommuneetablissement, :codecedexetablissement, 
//        :libellecedexetablissement, :codepaysetrangeretablissement, :libellepaysetrangeretablissement, :complementadresse2etablissement, 
//        :numerovoie2etablissement, :indicerepetition2etablissement, :typevoie2etablissement, :libellevoie2etablissement, 
//        :codepostal2etablissement, :libellecommune2etablissement, :libellecommuneetranger2etablissement, :distributionspeciale2etablissement, 
//        :codecommune2etablissement, :codecedex2etablissement, :libellecedex2etablissement, :codepaysetranger2etablissement, 
//        :libellepaysetranger2etablissement, :datedebut, :etatadministratifetablissement, :enseigne1etablissement, :enseigne2etablissement, 
//        :enseigne3etablissement, :denominationusuelleetablissement, :activiteprincipaleetablissement, :nomenclatureactiviteprincipaleetablissement, 
//        :caractereemployeuretablissement, :entree_champ_diffusion_commerciale, :changement_activiteprincipaleetablissement, :demenagement, :changement_etat_administratif, 
//	:modification_tranche_nb_salaries, :num_fic, :date_integration, :creation)";
//
//        //echo "--------------------ACTICITE = " . $oStock['activiteprincipaleetablissement'] . "\n";
//        $sActivite = str_replace(".", "", $oStock['activiteprincipaleetablissement']);
//        //echo "--------------------ACTICITE APRES = " . $sActivite . "\n";
//
//        $db = $this->getConnexionProd();
//        $sql = $db->prepare($sReq);
//        $sql->bindParam(':siren', $oStock['siren'], PDO::PARAM_STR);
//        $sql->bindParam(':nic', $oStock['nic'], PDO::PARAM_STR);
//        $sql->bindParam(':siret', $oStock['siret'], PDO::PARAM_STR);
//        $sql->bindParam(':statutdiffusionetablissement', $oStock['statutdiffusionetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':datecreationetablissement', $oStock['datecreationetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':trancheeffectifsetablissement', $oStock['trancheeffectifsetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':anneeeffectifsetablissement', $oStock['anneeeffectifsetablissement'], PDO::PARAM_INT);
//        $sql->bindParam(':activiteprincipaleregistremetiersetablissement', $oStock['activiteprincipaleregistremetiersetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':datederniertraitementetablissement', $oStock['datederniertraitementetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':etablissementsiege', $oStock['etablissementsiege'], PDO::PARAM_BOOL);
//        $sql->bindParam(':nombreperiodesetablissement', $oStock['nombreperiodesetablissement'], PDO::PARAM_INT);
//        $sql->bindParam(':complementadresseetablissement', $oStock['complementadresseetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':numerovoieetablissement', $oStock['numerovoieetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':indicerepetitionetablissement', $oStock['indicerepetitionetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':typevoieetablissement', $oStock['typevoieetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':libellevoieetablissement', $oStock['libellevoieetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':codepostaletablissement', $oStock['codepostaletablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':libellecommuneetablissement', $oStock['libellecommuneetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':libellecommuneetrangeretablissement', $oStock['libellecommuneetrangeretablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':distributionspecialeetablissement', $oStock['distributionspecialeetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':codecommuneetablissement', $oStock['codecommuneetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':codecedexetablissement', $oStock['codecedexetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':libellecedexetablissement', $oStock['libellecedexetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':codepaysetrangeretablissement', $oStock['codepaysetrangeretablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':libellepaysetrangeretablissement', $oStock['libellepaysetrangeretablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':complementadresse2etablissement', $oStock['complementadresse2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':numerovoie2etablissement', $oStock['numerovoie2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':indicerepetition2etablissement', $oStock['indicerepetition2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':typevoie2etablissement', $oStock['typevoie2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':libellevoie2etablissement', $oStock['libellevoie2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':codepostal2etablissement', $oStock['codepostal2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':libellecommune2etablissement', $oStock['libellecommune2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':libellecommuneetranger2etablissement', $oStock['libellecommuneetranger2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':distributionspeciale2etablissement', $oStock['distributionspeciale2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':codecommune2etablissement', $oStock['codecommune2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':codecedex2etablissement', $oStock['codecedex2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':libellecedex2etablissement', $oStock['libellecedex2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':codepaysetranger2etablissement', $oStock['codepaysetranger2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':libellepaysetranger2etablissement', $oStock['libellepaysetranger2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':datedebut', $oStock['datedebut'], PDO::PARAM_STR);
//        $sql->bindParam(':etatadministratifetablissement', $oStock['etatadministratifetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':enseigne1etablissement', $oStock['enseigne1etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':enseigne2etablissement', $oStock['enseigne2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':enseigne3etablissement', $oStock['enseigne3etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':denominationusuelleetablissement', $oStock['denominationusuelleetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':activiteprincipaleetablissement', $sActivite, PDO::PARAM_STR);
//        $sql->bindParam(':nomenclatureactiviteprincipaleetablissement', $oStock['nomenclatureactiviteprincipaleetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':caractereemployeuretablissement', $oStock['caractereemployeuretablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':entree_champ_diffusion_commerciale', $bEntreDiffComm, PDO::PARAM_BOOL);
//        $sql->bindParam(':changement_activiteprincipaleetablissement', $bChgtActivitePrincipale, PDO::PARAM_BOOL);
//        $sql->bindParam(':demenagement', $bAdresseChange, PDO::PARAM_BOOL);
//        $sql->bindParam(':changement_etat_administratif', $bChgtEtatAdmin, PDO::PARAM_BOOL);
//        $sql->bindParam(':modification_tranche_nb_salaries', $bChgtTrancheEff, PDO::PARAM_BOOL);
//        $sql->bindParam(':num_fic', $numFic, PDO::PARAM_INT);
//        $sql->bindParam(':date_integration', $sDateFormat, PDO::PARAM_STR);
//        $sql->bindParam(':creation', $bCreation, PDO::PARAM_BOOL);
//
//        $sql->execute();
//
//        $aErreur = $sql->errorInfo();
//        if (strlen($aErreur[2]) > 0) {
//            echo $sql->queryString . "\n\n";
//            die($aErreur[2]);
//        }
//        //echo "============================================ ADRESSE CHG = " . $this->iCountChgAdresse . "===================================================\n\n";
//    }

    public function insertGeosireneTmp($oStock, $aTabChgt, $numFic, $sDateFormat, $bCreation) {


//die(print_r($oStock));
        //echo '------------------------------insertGeosirene <\n>';
        $bEntreDiffComm = 'FALSE';
        $bAdresseChange = 'FALSE';
        $bChgtEtatAdmin = 'FALSE';
        $bChgtTrancheEff = 'FALSE';
        $bChgtActivitePrincipale = 'FALSE';

        if (!is_null($aTabChgt)) {
            if (in_array('statutdiffusionetablissement', $aTabChgt)) {
                //echo "*****************MODIF = statutdiffusionetablissement\n";
                $bEntreDiffComm = 'TRUE';
            }
            if (in_array('numerovoieetablissement', $aTabChgt) || in_array('typevoieetablissement', $aTabChgt) || in_array('libellevoieetablissement', $aTabChgt) || in_array('codepostaletablissement', $aTabChgt) || in_array('libellecommuneetablissement', $aTabChgt)) {
                $bAdresseChange = 'TRUE';
                $this->iCountChgAdresse++;
                //echo "*****************MODIF = numerovoieetablissement\n";
            }
            if (in_array('etatAdministratifEtablissement', $aTabChgt)) {
                $bChgtEtatAdmin = 'TRUE';
                //echo "*****************MODIF = etatAdministratifEtablissement\n";
            }
            if (in_array('trancheEffectifsEtablissement', $aTabChgt)) {
                $bChgtTrancheEff = 'TRUE';
                //echo "*****************MODIF = trancheEffectifsEtablissement\n";
            }
            if (in_array('activiteprincipaleetablissement', $aTabChgt)) {
                $bChgtActivitePrincipale = 'TRUE';
                //echo "*****************MODIF = activiteprincipaleetablissement\n";
            }
        }



        $sReq = "INSERT INTO poi.geosirene_tmp_old(siren, nic, siret, statutdiffusionetablissement, datecreationetablissement, 
        trancheeffectifsetablissement, anneeeffectifsetablissement, activiteprincipaleregistremetiersetablissement, 
        datederniertraitementetablissement, etablissementsiege, nombreperiodesetablissement, 
        complementadresseetablissement, numerovoieetablissement, indicerepetitionetablissement, 
        typevoieetablissement, libellevoieetablissement, codepostaletablissement, libellecommuneetablissement, 
        libellecommuneetrangeretablissement, distributionspecialeetablissement, codecommuneetablissement, codecedexetablissement, 
        libellecedexetablissement, codepaysetrangeretablissement, libellepaysetrangeretablissement, complementadresse2etablissement, 
        numerovoie2etablissement, indicerepetition2etablissement, typevoie2etablissement, libellevoie2etablissement, 
        codepostal2etablissement, libellecommune2etablissement, libellecommuneetranger2etablissement, distributionspeciale2etablissement, 
        codecommune2etablissement, codecedex2etablissement, libellecedex2etablissement, codepaysetranger2etablissement, 
        libellepaysetranger2etablissement, datedebut, etatadministratifetablissement, enseigne1etablissement, enseigne2etablissement, 
        enseigne3etablissement, denominationusuelleetablissement, activiteprincipaleetablissement, nomenclatureactiviteprincipaleetablissement, 
        caractereemployeuretablissement,
        entree_champ_diffusion_commerciale, changement_activiteprincipaleetablissement, demenagement, changement_etat_administratif, 
	modification_tranche_nb_salaries, num_fic, date_integration, creation)
        VALUES 
        (:siren, :nic, :siret, :statutdiffusionetablissement, :datecreationetablissement, 
        :trancheeffectifsetablissement, :anneeeffectifsetablissement, :activiteprincipaleregistremetiersetablissement, 
        :datederniertraitementetablissement, :etablissementsiege, :nombreperiodesetablissement, 
        :complementadresseetablissement, :numerovoieetablissement, :indicerepetitionetablissement, 
        :typevoieetablissement, :libellevoieetablissement, :codepostaletablissement, :libellecommuneetablissement, 
        :libellecommuneetrangeretablissement, :distributionspecialeetablissement, :codecommuneetablissement, :codecedexetablissement, 
        :libellecedexetablissement, :codepaysetrangeretablissement, :libellepaysetrangeretablissement, :complementadresse2etablissement, 
        :numerovoie2etablissement, :indicerepetition2etablissement, :typevoie2etablissement, :libellevoie2etablissement, 
        :codepostal2etablissement, :libellecommune2etablissement, :libellecommuneetranger2etablissement, :distributionspeciale2etablissement, 
        :codecommune2etablissement, :codecedex2etablissement, :libellecedex2etablissement, :codepaysetranger2etablissement, 
        :libellepaysetranger2etablissement, :datedebut, :etatadministratifetablissement, :enseigne1etablissement, :enseigne2etablissement, 
        :enseigne3etablissement, :denominationusuelleetablissement, :activiteprincipaleetablissement, :nomenclatureactiviteprincipaleetablissement, 
        :caractereemployeuretablissement, :entree_champ_diffusion_commerciale, :changement_activiteprincipaleetablissement, :demenagement, :changement_etat_administratif, 
	:modification_tranche_nb_salaries, :num_fic, :date_integration, :creation)";

        //echo "--------------------ACTICITE = " . $oStock['activiteprincipaleetablissement'] . "\n";
        $sActivite = str_replace(".", "", $oStock['activiteprincipaleetablissement']);
        //echo "--------------------ACTICITE APRES = " . $sActivite . "\n";

        $db = $this->getConnexion();
        $sql = $db->prepare($sReq);
        $sql->bindParam(':siren', $oStock['siren'], PDO::PARAM_STR);
        $sql->bindParam(':nic', $oStock['nic'], PDO::PARAM_STR);
        $sql->bindParam(':siret', $oStock['siret'], PDO::PARAM_STR);
        $sql->bindParam(':statutdiffusionetablissement', $oStock['statutdiffusionetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':datecreationetablissement', $oStock['datecreationetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':trancheeffectifsetablissement', $oStock['trancheeffectifsetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':anneeeffectifsetablissement', $oStock['anneeeffectifsetablissement'], PDO::PARAM_INT);
        $sql->bindParam(':activiteprincipaleregistremetiersetablissement', $oStock['activiteprincipaleregistremetiersetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':datederniertraitementetablissement', $oStock['datederniertraitementetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':etablissementsiege', $oStock['etablissementsiege'], PDO::PARAM_BOOL);
        $sql->bindParam(':nombreperiodesetablissement', $oStock['nombreperiodesetablissement'], PDO::PARAM_INT);
        $sql->bindParam(':complementadresseetablissement', $oStock['complementadresseetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':numerovoieetablissement', $oStock['numerovoieetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':indicerepetitionetablissement', $oStock['indicerepetitionetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':typevoieetablissement', $oStock['typevoieetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellevoieetablissement', $oStock['libellevoieetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codepostaletablissement', $oStock['codepostaletablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellecommuneetablissement', $oStock['libellecommuneetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellecommuneetrangeretablissement', $oStock['libellecommuneetrangeretablissement'], PDO::PARAM_STR);
        $sql->bindParam(':distributionspecialeetablissement', $oStock['distributionspecialeetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codecommuneetablissement', $oStock['codecommuneetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codecedexetablissement', $oStock['codecedexetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellecedexetablissement', $oStock['libellecedexetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codepaysetrangeretablissement', $oStock['codepaysetrangeretablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellepaysetrangeretablissement', $oStock['libellepaysetrangeretablissement'], PDO::PARAM_STR);
        $sql->bindParam(':complementadresse2etablissement', $oStock['complementadresse2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':numerovoie2etablissement', $oStock['numerovoie2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':indicerepetition2etablissement', $oStock['indicerepetition2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':typevoie2etablissement', $oStock['typevoie2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellevoie2etablissement', $oStock['libellevoie2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codepostal2etablissement', $oStock['codepostal2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellecommune2etablissement', $oStock['libellecommune2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellecommuneetranger2etablissement', $oStock['libellecommuneetranger2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':distributionspeciale2etablissement', $oStock['distributionspeciale2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codecommune2etablissement', $oStock['codecommune2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codecedex2etablissement', $oStock['codecedex2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellecedex2etablissement', $oStock['libellecedex2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codepaysetranger2etablissement', $oStock['codepaysetranger2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellepaysetranger2etablissement', $oStock['libellepaysetranger2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':datedebut', $oStock['datedebut'], PDO::PARAM_STR);
        $sql->bindParam(':etatadministratifetablissement', $oStock['etatadministratifetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':enseigne1etablissement', $oStock['enseigne1etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':enseigne2etablissement', $oStock['enseigne2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':enseigne3etablissement', $oStock['enseigne3etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':denominationusuelleetablissement', $oStock['denominationusuelleetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':activiteprincipaleetablissement', $sActivite, PDO::PARAM_STR);
        $sql->bindParam(':nomenclatureactiviteprincipaleetablissement', $oStock['nomenclatureactiviteprincipaleetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':caractereemployeuretablissement', $oStock['caractereemployeuretablissement'], PDO::PARAM_STR);
        $sql->bindParam(':entree_champ_diffusion_commerciale', $bEntreDiffComm, PDO::PARAM_BOOL);
        $sql->bindParam(':changement_activiteprincipaleetablissement', $bChgtActivitePrincipale, PDO::PARAM_BOOL);
        $sql->bindParam(':demenagement', $bAdresseChange, PDO::PARAM_BOOL);
        $sql->bindParam(':changement_etat_administratif', $bChgtEtatAdmin, PDO::PARAM_BOOL);
        $sql->bindParam(':modification_tranche_nb_salaries', $bChgtTrancheEff, PDO::PARAM_BOOL);
        $sql->bindParam(':num_fic', $numFic, PDO::PARAM_INT);
        $sql->bindParam(':date_integration', $sDateFormat, PDO::PARAM_STR);
        $sql->bindParam(':creation', $bCreation, PDO::PARAM_BOOL);

        $sql->execute();

        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            echo $sql->queryString . "\n\n";
            die($aErreur[2]);
        }
        //echo "============================================ ADRESSE CHG = " . $this->iCountChgAdresse . "===================================================\n\n";
    }

    public function searchGeosireneStock($sSiret) {
        $sQuery = "SELECT * FROM poi.geosirene_stock WHERE siret = :siret";
        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);
        $sql->bindParam(':siret', $sSiret, PDO::PARAM_STR);
        $sql->execute();

        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            echo $sql->queryString . "\n\n";
            die($aErreur[2]);
        } else {
            return $sql->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    public function insertGeosireneStock($oStock) {



        $sReq = "INSERT INTO poi.geosirene_stock(siren, nic, siret, statutdiffusionetablissement, datecreationetablissement, 
        trancheeffectifsetablissement, anneeeffectifsetablissement, activiteprincipaleregistremetiersetablissement, 
        datederniertraitementetablissement, etablissementsiege, nombreperiodesetablissement, 
        complementadresseetablissement, numerovoieetablissement, indicerepetitionetablissement, 
        typevoieetablissement, libellevoieetablissement, codepostaletablissement, libellecommuneetablissement, 
        libellecommuneetrangeretablissement, distributionspecialeetablissement, codecommuneetablissement, codecedexetablissement, 
        libellecedexetablissement, codepaysetrangeretablissement, libellepaysetrangeretablissement, complementadresse2etablissement, 
        numerovoie2etablissement, indicerepetition2etablissement, typevoie2etablissement, libellevoie2etablissement, 
        codepostal2etablissement, libellecommune2etablissement, libellecommuneetranger2etablissement, distributionspeciale2etablissement, 
        codecommune2etablissement, codecedex2etablissement, libellecedex2etablissement, codepaysetranger2etablissement, 
        libellepaysetranger2etablissement, datedebut, etatadministratifetablissement, enseigne1etablissement, enseigne2etablissement, 
        enseigne3etablissement, denominationusuelleetablissement, activiteprincipaleetablissement, nomenclatureactiviteprincipaleetablissement, 
        caractereemployeuretablissement)
        VALUES 
        (:siren, :nic, :siret, :statutdiffusionetablissement, :datecreationetablissement, 
        :trancheeffectifsetablissement, :anneeeffectifsetablissement, :activiteprincipaleregistremetiersetablissement, 
        :datederniertraitementetablissement, :etablissementsiege, :nombreperiodesetablissement, 
        :complementadresseetablissement, :numerovoieetablissement, :indicerepetitionetablissement, 
        :typevoieetablissement, :libellevoieetablissement, :codepostaletablissement, :libellecommuneetablissement, 
        :libellecommuneetrangeretablissement, :distributionspecialeetablissement, :codecommuneetablissement, :codecedexetablissement, 
        :libellecedexetablissement, :codepaysetrangeretablissement, :libellepaysetrangeretablissement, :complementadresse2etablissement, 
        :numerovoie2etablissement, :indicerepetition2etablissement, :typevoie2etablissement, :libellevoie2etablissement, 
        :codepostal2etablissement, :libellecommune2etablissement, :libellecommuneetranger2etablissement, :distributionspeciale2etablissement, 
        :codecommune2etablissement, :codecedex2etablissement, :libellecedex2etablissement, :codepaysetranger2etablissement, 
        :libellepaysetranger2etablissement, :datedebut, :etatadministratifetablissement, :enseigne1etablissement, :enseigne2etablissement, 
        :enseigne3etablissement, :denominationusuelleetablissement, :activiteprincipaleetablissement, :nomenclatureactiviteprincipaleetablissement, 
        :caractereemployeuretablissement)";

        //echo "--------------------ACTICITE = " . $oStock['activiteprincipaleetablissement'] . "\n";
        $sActivite = str_replace(".", "", $oStock['activiteprincipaleetablissement']);
        //echo "--------------------ACTICITE APRES = " . $sActivite . "\n";

        $db = $this->getConnexion();
        $sql = $db->prepare($sReq);
        $sql->bindParam(':siren', $oStock['siren'], PDO::PARAM_STR);
        $sql->bindParam(':nic', $oStock['nic'], PDO::PARAM_STR);
        $sql->bindParam(':siret', $oStock['siret'], PDO::PARAM_STR);
        $sql->bindParam(':statutdiffusionetablissement', $oStock['statutdiffusionetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':datecreationetablissement', $oStock['datecreationetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':trancheeffectifsetablissement', $oStock['trancheeffectifsetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':anneeeffectifsetablissement', $oStock['anneeeffectifsetablissement'], PDO::PARAM_INT);
        $sql->bindParam(':activiteprincipaleregistremetiersetablissement', $oStock['activiteprincipaleregistremetiersetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':datederniertraitementetablissement', $oStock['datederniertraitementetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':etablissementsiege', $oStock['etablissementsiege'], PDO::PARAM_BOOL);
        $sql->bindParam(':nombreperiodesetablissement', $oStock['nombreperiodesetablissement'], PDO::PARAM_INT);
        $sql->bindParam(':complementadresseetablissement', $oStock['complementadresseetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':numerovoieetablissement', $oStock['numerovoieetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':indicerepetitionetablissement', $oStock['indicerepetitionetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':typevoieetablissement', $oStock['typevoieetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellevoieetablissement', $oStock['libellevoieetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codepostaletablissement', $oStock['codepostaletablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellecommuneetablissement', $oStock['libellecommuneetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellecommuneetrangeretablissement', $oStock['libellecommuneetrangeretablissement'], PDO::PARAM_STR);
        $sql->bindParam(':distributionspecialeetablissement', $oStock['distributionspecialeetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codecommuneetablissement', $oStock['codecommuneetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codecedexetablissement', $oStock['codecedexetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellecedexetablissement', $oStock['libellecedexetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codepaysetrangeretablissement', $oStock['codepaysetrangeretablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellepaysetrangeretablissement', $oStock['libellepaysetrangeretablissement'], PDO::PARAM_STR);
        $sql->bindParam(':complementadresse2etablissement', $oStock['complementadresse2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':numerovoie2etablissement', $oStock['numerovoie2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':indicerepetition2etablissement', $oStock['indicerepetition2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':typevoie2etablissement', $oStock['typevoie2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellevoie2etablissement', $oStock['libellevoie2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codepostal2etablissement', $oStock['codepostal2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellecommune2etablissement', $oStock['libellecommune2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellecommuneetranger2etablissement', $oStock['libellecommuneetranger2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':distributionspeciale2etablissement', $oStock['distributionspeciale2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codecommune2etablissement', $oStock['codecommune2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codecedex2etablissement', $oStock['codecedex2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellecedex2etablissement', $oStock['libellecedex2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codepaysetranger2etablissement', $oStock['codepaysetranger2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellepaysetranger2etablissement', $oStock['libellepaysetranger2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':datedebut', $oStock['datedebut'], PDO::PARAM_STR);
        $sql->bindParam(':etatadministratifetablissement', $oStock['etatadministratifetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':enseigne1etablissement', $oStock['enseigne1etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':enseigne2etablissement', $oStock['enseigne2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':enseigne3etablissement', $oStock['enseigne3etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':denominationusuelleetablissement', $oStock['denominationusuelleetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':activiteprincipaleetablissement', $sActivite, PDO::PARAM_STR);
        $sql->bindParam(':nomenclatureactiviteprincipaleetablissement', $oStock['nomenclatureactiviteprincipaleetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':caractereemployeuretablissement', $oStock['caractereemployeuretablissement'], PDO::PARAM_STR);


        $sql->execute();

        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            echo $sql->queryString . "\n\n";
            die($aErreur[2]);
        }
        //echo "============================================ ADRESSE CHG = " . $this->iCountChgAdresse . "===================================================\n\n";
    }

    public function insertStock($oStock, $sDateFormat, $bCreation, $denominationGeoscar) {

        // echo '---------------------------------------------------------------------insertStock \n';
        //die(print_r($oStock));

        $sReq = "INSERT INTO poi.sirene_etablissement_n0 (siren, nic, siret, statutdiffusionetablissement, datecreationetablissement, 
        trancheeffectifsetablissement, anneeeffectifsetablissement, activiteprincipaleregistremetiersetablissement, 
        datederniertraitementetablissement, etablissementsiege, nombreperiodesetablissement, 
        complementadresseetablissement, numerovoieetablissement, indicerepetitionetablissement, 
        typevoieetablissement, libellevoieetablissement, codepostaletablissement, libellecommuneetablissement, 
        libellecommuneetrangeretablissement, distributionspecialeetablissement, codecommuneetablissement, codecedexetablissement, 
        libellecedexetablissement, codepaysetrangeretablissement, libellepaysetrangeretablissement, complementadresse2etablissement, 
        numerovoie2etablissement, indicerepetition2etablissement, typevoie2etablissement, libellevoie2etablissement, 
        codepostal2etablissement, libellecommune2etablissement, libellecommuneetranger2etablissement, distributionspeciale2etablissement, 
        codecommune2etablissement, codecedex2etablissement, libellecedex2etablissement, codepaysetranger2etablissement, 
        libellepaysetranger2etablissement, datedebut, etatadministratifetablissement, enseigne1etablissement, enseigne2etablissement, 
        enseigne3etablissement, denominationusuelleetablissement, activiteprincipaleetablissement, nomenclatureactiviteprincipaleetablissement, 
        caractereemployeuretablissement, date_integration, creation, denomination_geoscar)
        VALUES 
        (:siren, :nic, :siret, :statutdiffusionetablissement, :datecreationetablissement, 
        :trancheeffectifsetablissement, :anneeeffectifsetablissement, :activiteprincipaleregistremetiersetablissement, 
        :datederniertraitementetablissement, :etablissementsiege, :nombreperiodesetablissement, 
        :complementadresseetablissement, :numerovoieetablissement, :indicerepetitionetablissement, 
        :typevoieetablissement, :libellevoieetablissement, :codepostaletablissement, :libellecommuneetablissement, 
        :libellecommuneetrangeretablissement, :distributionspecialeetablissement, :codecommuneetablissement, :codecedexetablissement, 
        :libellecedexetablissement, :codepaysetrangeretablissement, :libellepaysetrangeretablissement, :complementadresse2etablissement, 
        :numerovoie2etablissement, :indicerepetition2etablissement, :typevoie2etablissement, :libellevoie2etablissement, 
        :codepostal2etablissement, :libellecommune2etablissement, :libellecommuneetranger2etablissement, :distributionspeciale2etablissement, 
        :codecommune2etablissement, :codecedex2etablissement, :libellecedex2etablissement, :codepaysetranger2etablissement, 
        :libellepaysetranger2etablissement, :datedebut, :etatadministratifetablissement, :enseigne1etablissement, :enseigne2etablissement, 
        :enseigne3etablissement, :denominationusuelleetablissement, :activiteprincipaleetablissement, :nomenclatureactiviteprincipaleetablissement, 
        :caractereemployeuretablissement, :date_integration, :creation, :denomination_geoscar)";

        //echo "--------------------ACTICITE = " . $oStock['activiteprincipaleetablissement'] . "\n";
        //$sActivite = str_replace(".", "", $oStock['activiteprincipaleetablissement']);
        //echo "--------------------ACTICITE APRES = " . $sActivite . "\n";

        $db = $this->getConnexion();
        $sql = $db->prepare($sReq);
        $sql->bindParam(':siren', $oStock['siren'], PDO::PARAM_STR);
        $sql->bindParam(':nic', $oStock['nic'], PDO::PARAM_STR);
        $sql->bindParam(':siret', $oStock['siret'], PDO::PARAM_STR);
        $sql->bindParam(':statutdiffusionetablissement', $oStock['statutdiffusionetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':datecreationetablissement', $oStock['datecreationetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':trancheeffectifsetablissement', $oStock['trancheeffectifsetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':anneeeffectifsetablissement', $oStock['anneeeffectifsetablissement'], PDO::PARAM_INT);
        $sql->bindParam(':activiteprincipaleregistremetiersetablissement', $oStock['activiteprincipaleregistremetiersetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':datederniertraitementetablissement', $oStock['datederniertraitementetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':etablissementsiege', $oStock['etablissementsiege'], PDO::PARAM_BOOL);
        $sql->bindParam(':nombreperiodesetablissement', $oStock['nombreperiodesetablissement'], PDO::PARAM_INT);
        $sql->bindParam(':complementadresseetablissement', $oStock['complementadresseetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':numerovoieetablissement', $oStock['numerovoieetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':indicerepetitionetablissement', $oStock['indicerepetitionetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':typevoieetablissement', $oStock['typevoieetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellevoieetablissement', $oStock['libellevoieetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codepostaletablissement', $oStock['codepostaletablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellecommuneetablissement', $oStock['libellecommuneetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellecommuneetrangeretablissement', $oStock['libellecommuneetrangeretablissement'], PDO::PARAM_STR);
        $sql->bindParam(':distributionspecialeetablissement', $oStock['distributionspecialeetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codecommuneetablissement', $oStock['codecommuneetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codecedexetablissement', $oStock['codecedexetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellecedexetablissement', $oStock['libellecedexetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codepaysetrangeretablissement', $oStock['codepaysetrangeretablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellepaysetrangeretablissement', $oStock['libellepaysetrangeretablissement'], PDO::PARAM_STR);
        $sql->bindParam(':complementadresse2etablissement', $oStock['complementadresse2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':numerovoie2etablissement', $oStock['numerovoie2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':indicerepetition2etablissement', $oStock['indicerepetition2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':typevoie2etablissement', $oStock['typevoie2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellevoie2etablissement', $oStock['libellevoie2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codepostal2etablissement', $oStock['codepostal2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellecommune2etablissement', $oStock['libellecommune2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellecommuneetranger2etablissement', $oStock['libellecommuneetranger2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':distributionspeciale2etablissement', $oStock['distributionspeciale2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codecommune2etablissement', $oStock['codecommune2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codecedex2etablissement', $oStock['codecedex2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellecedex2etablissement', $oStock['libellecedex2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codepaysetranger2etablissement', $oStock['codepaysetranger2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellepaysetranger2etablissement', $oStock['libellepaysetranger2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':datedebut', $oStock['datedebut'], PDO::PARAM_STR);
        $sql->bindParam(':etatadministratifetablissement', $oStock['etatadministratifetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':enseigne1etablissement', $oStock['enseigne1etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':enseigne2etablissement', $oStock['enseigne2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':enseigne3etablissement', $oStock['enseigne3etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':denominationusuelleetablissement', $oStock['denominationusuelleetablissement'], PDO::PARAM_STR);
        $sql->bindValue(':activiteprincipaleetablissement', str_replace(".", "", $oStock['activiteprincipaleetablissement']), PDO::PARAM_STR);
        $sql->bindParam(':nomenclatureactiviteprincipaleetablissement', $oStock['nomenclatureactiviteprincipaleetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':caractereemployeuretablissement', $oStock['caractereemployeuretablissement'], PDO::PARAM_STR);
        $sql->bindParam(':date_integration', $sDateFormat, PDO::PARAM_STR);
        $sql->bindParam(':creation', $bCreation, PDO::PARAM_STR);
        $sql->bindParam(':denomination_geoscar', $denominationGeoscar, PDO::PARAM_STR);


        $sql->execute();
//
        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            echo $sReq . "\n\n";
            $this->sendMailIncidentQuery($sReq, $aErreur[2]);
            //die($aErreur[2]);
        }
//        $db->beginTransaction();
//        try {
//            $sql->execute();
//            $db->commit();
//        } catch (Exception $e) {
//            $db->rollBack();
//            exit();
//        }



        /* $aErreur = $sql->errorInfo();
          if (strlen($aErreur[2]) > 0) {
          echo $sql->queryString . "\n\n";
          die($aErreur[2]);
          } */
    }

//    public function insertStockProd($oStock) {
//
//       // echo '---------------------------------------------------------------------insertStock \n';
//        //die(print_r($oStock));
//
//        $sReq = "INSERT INTO poi.sirene_etablissement_n0 (siren, nic, siret, statutdiffusionetablissement, datecreationetablissement, 
//        trancheeffectifsetablissement, anneeeffectifsetablissement, activiteprincipaleregistremetiersetablissement, 
//        datederniertraitementetablissement, etablissementsiege, nombreperiodesetablissement, 
//        complementadresseetablissement, numerovoieetablissement, indicerepetitionetablissement, 
//        typevoieetablissement, libellevoieetablissement, codepostaletablissement, libellecommuneetablissement, 
//        libellecommuneetrangeretablissement, distributionspecialeetablissement, codecommuneetablissement, codecedexetablissement, 
//        libellecedexetablissement, codepaysetrangeretablissement, libellepaysetrangeretablissement, complementadresse2etablissement, 
//        numerovoie2etablissement, indicerepetition2etablissement, typevoie2etablissement, libellevoie2etablissement, 
//        codepostal2etablissement, libellecommune2etablissement, libellecommuneetranger2etablissement, distributionspeciale2etablissement, 
//        codecommune2etablissement, codecedex2etablissement, libellecedex2etablissement, codepaysetranger2etablissement, 
//        libellepaysetranger2etablissement, datedebut, etatadministratifetablissement, enseigne1etablissement, enseigne2etablissement, 
//        enseigne3etablissement, denominationusuelleetablissement, activiteprincipaleetablissement, nomenclatureactiviteprincipaleetablissement, 
//        caractereemployeuretablissement)
//        VALUES 
//        (:siren, :nic, :siret, :statutdiffusionetablissement, :datecreationetablissement, 
//        :trancheeffectifsetablissement, :anneeeffectifsetablissement, :activiteprincipaleregistremetiersetablissement, 
//        :datederniertraitementetablissement, :etablissementsiege, :nombreperiodesetablissement, 
//        :complementadresseetablissement, :numerovoieetablissement, :indicerepetitionetablissement, 
//        :typevoieetablissement, :libellevoieetablissement, :codepostaletablissement, :libellecommuneetablissement, 
//        :libellecommuneetrangeretablissement, :distributionspecialeetablissement, :codecommuneetablissement, :codecedexetablissement, 
//        :libellecedexetablissement, :codepaysetrangeretablissement, :libellepaysetrangeretablissement, :complementadresse2etablissement, 
//        :numerovoie2etablissement, :indicerepetition2etablissement, :typevoie2etablissement, :libellevoie2etablissement, 
//        :codepostal2etablissement, :libellecommune2etablissement, :libellecommuneetranger2etablissement, :distributionspeciale2etablissement, 
//        :codecommune2etablissement, :codecedex2etablissement, :libellecedex2etablissement, :codepaysetranger2etablissement, 
//        :libellepaysetranger2etablissement, :datedebut, :etatadministratifetablissement, :enseigne1etablissement, :enseigne2etablissement, 
//        :enseigne3etablissement, :denominationusuelleetablissement, :activiteprincipaleetablissement, :nomenclatureactiviteprincipaleetablissement, 
//        :caractereemployeuretablissement)";
//
//        //echo "--------------------ACTICITE = " . $oStock['activiteprincipaleetablissement'] . "\n";
//        $sActivite = str_replace(".", "", $oStock['activiteprincipaleetablissement']);
//        //echo "--------------------ACTICITE APRES = " . $sActivite . "\n";
//
//        $db = $this->getConnexionProd();
//        $sql = $db->prepare($sReq);
//        $sql->bindParam(':siren', $oStock['siren'], PDO::PARAM_STR);
//        $sql->bindParam(':nic', $oStock['nic'], PDO::PARAM_STR);
//        $sql->bindParam(':siret', $oStock['siret'], PDO::PARAM_STR);
//        $sql->bindParam(':statutdiffusionetablissement', $oStock['statutdiffusionetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':datecreationetablissement', $oStock['datecreationetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':trancheeffectifsetablissement', $oStock['trancheeffectifsetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':anneeeffectifsetablissement', $oStock['anneeeffectifsetablissement'], PDO::PARAM_INT);
//        $sql->bindParam(':activiteprincipaleregistremetiersetablissement', $oStock['activiteprincipaleregistremetiersetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':datederniertraitementetablissement', $oStock['datederniertraitementetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':etablissementsiege', $oStock['etablissementsiege'], PDO::PARAM_BOOL);
//        $sql->bindParam(':nombreperiodesetablissement', $oStock['nombreperiodesetablissement'], PDO::PARAM_INT);
//        $sql->bindParam(':complementadresseetablissement', $oStock['complementadresseetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':numerovoieetablissement', $oStock['numerovoieetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':indicerepetitionetablissement', $oStock['indicerepetitionetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':typevoieetablissement', $oStock['typevoieetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':libellevoieetablissement', $oStock['libellevoieetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':codepostaletablissement', $oStock['codepostaletablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':libellecommuneetablissement', $oStock['libellecommuneetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':libellecommuneetrangeretablissement', $oStock['libellecommuneetrangeretablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':distributionspecialeetablissement', $oStock['distributionspecialeetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':codecommuneetablissement', $oStock['codecommuneetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':codecedexetablissement', $oStock['codecedexetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':libellecedexetablissement', $oStock['libellecedexetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':codepaysetrangeretablissement', $oStock['codepaysetrangeretablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':libellepaysetrangeretablissement', $oStock['libellepaysetrangeretablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':complementadresse2etablissement', $oStock['complementadresse2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':numerovoie2etablissement', $oStock['numerovoie2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':indicerepetition2etablissement', $oStock['indicerepetition2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':typevoie2etablissement', $oStock['typevoie2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':libellevoie2etablissement', $oStock['libellevoie2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':codepostal2etablissement', $oStock['codepostal2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':libellecommune2etablissement', $oStock['libellecommune2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':libellecommuneetranger2etablissement', $oStock['libellecommuneetranger2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':distributionspeciale2etablissement', $oStock['distributionspeciale2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':codecommune2etablissement', $oStock['codecommune2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':codecedex2etablissement', $oStock['codecedex2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':libellecedex2etablissement', $oStock['libellecedex2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':codepaysetranger2etablissement', $oStock['codepaysetranger2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':libellepaysetranger2etablissement', $oStock['libellepaysetranger2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':datedebut', $oStock['datedebut'], PDO::PARAM_STR);
//        $sql->bindParam(':etatadministratifetablissement', $oStock['etatadministratifetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':enseigne1etablissement', $oStock['enseigne1etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':enseigne2etablissement', $oStock['enseigne2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':enseigne3etablissement', $oStock['enseigne3etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':denominationusuelleetablissement', $oStock['denominationusuelleetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':activiteprincipaleetablissement', $sActivite, PDO::PARAM_STR);
//        $sql->bindParam(':nomenclatureactiviteprincipaleetablissement', $oStock['nomenclatureactiviteprincipaleetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':caractereemployeuretablissement', $oStock['caractereemployeuretablissement'], PDO::PARAM_STR);
//
//
//        $sql->execute();
//
//        $aErreur = $sql->errorInfo();
//        if (strlen($aErreur[2]) > 0) {
//            echo $sql->queryString . "\n\n";
//            die($aErreur[2]);
//        }
//    }
    public function insertStockFermes($oStock, $denominationGeoscar) {

        //echo '---------------------------------------------------------------------insertStock \n';
        //die(print_r($oStock));

        $sReq = "INSERT INTO poi.sirene_etablissement_ferme(siren, nic, siret, statutdiffusionetablissement, datecreationetablissement, 
        trancheeffectifsetablissement, anneeeffectifsetablissement, activiteprincipaleregistremetiersetablissement, 
        datederniertraitementetablissement, etablissementsiege, nombreperiodesetablissement, 
        complementadresseetablissement, numerovoieetablissement, indicerepetitionetablissement, 
        typevoieetablissement, libellevoieetablissement, codepostaletablissement, libellecommuneetablissement, 
        libellecommuneetrangeretablissement, distributionspecialeetablissement, codecommuneetablissement, codecedexetablissement, 
        libellecedexetablissement, codepaysetrangeretablissement, libellepaysetrangeretablissement, complementadresse2etablissement, 
        numerovoie2etablissement, indicerepetition2etablissement, typevoie2etablissement, libellevoie2etablissement, 
        codepostal2etablissement, libellecommune2etablissement, libellecommuneetranger2etablissement, distributionspeciale2etablissement, 
        codecommune2etablissement, codecedex2etablissement, libellecedex2etablissement, codepaysetranger2etablissement, 
        libellepaysetranger2etablissement, datedebut, etatadministratifetablissement, enseigne1etablissement, enseigne2etablissement, 
        enseigne3etablissement, denominationusuelleetablissement, activiteprincipaleetablissement, nomenclatureactiviteprincipaleetablissement, 
        caractereemployeuretablissement, date_integration, denomination_geoscar)
	VALUES (:siren, :nic, :siret, :statutdiffusionetablissement, :datecreationetablissement, 
        :trancheeffectifsetablissement, :anneeeffectifsetablissement, :activiteprincipaleregistremetiersetablissement, 
        :datederniertraitementetablissement, :etablissementsiege, :nombreperiodesetablissement, 
        :complementadresseetablissement, :numerovoieetablissement, :indicerepetitionetablissement, 
        :typevoieetablissement, :libellevoieetablissement, :codepostaletablissement, :libellecommuneetablissement, 
        :libellecommuneetrangeretablissement, :distributionspecialeetablissement, :codecommuneetablissement, :codecedexetablissement, 
        :libellecedexetablissement, :codepaysetrangeretablissement, :libellepaysetrangeretablissement, :complementadresse2etablissement, 
        :numerovoie2etablissement, :indicerepetition2etablissement, :typevoie2etablissement, :libellevoie2etablissement, 
        :codepostal2etablissement, :libellecommune2etablissement, :libellecommuneetranger2etablissement, :distributionspeciale2etablissement, 
        :codecommune2etablissement, :codecedex2etablissement, :libellecedex2etablissement, :codepaysetranger2etablissement, 
        :libellepaysetranger2etablissement, :datedebut, :etatadministratifetablissement, :enseigne1etablissement, :enseigne2etablissement, 
        :enseigne3etablissement, :denominationusuelleetablissement, :activiteprincipaleetablissement, :nomenclatureactiviteprincipaleetablissement, 
        :caractereemployeuretablissement, :date_integration, :denomination_geoscar)";
        $db = $this->getConnexion();
        $sql = $db->prepare($sReq);
        $sql->bindParam(':siren', $oStock['siren'], PDO::PARAM_STR);
        $sql->bindParam(':nic', $oStock['nic'], PDO::PARAM_STR);
        $sql->bindParam(':siret', $oStock['siret'], PDO::PARAM_STR);
        $sql->bindParam(':statutdiffusionetablissement', $oStock['statutdiffusionetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':datecreationetablissement', $oStock['datecreationetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':trancheeffectifsetablissement', $oStock['trancheeffectifsetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':anneeeffectifsetablissement', $oStock['anneeeffectifsetablissement'], PDO::PARAM_INT);
        $sql->bindParam(':activiteprincipaleregistremetiersetablissement', $oStock['activiteprincipaleregistremetiersetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':datederniertraitementetablissement', $oStock['datederniertraitementetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':etablissementsiege', $oStock['etablissementsiege'], PDO::PARAM_BOOL);
        $sql->bindParam(':nombreperiodesetablissement', $oStock['nombreperiodesetablissement'], PDO::PARAM_INT);
        $sql->bindParam(':complementadresseetablissement', $oStock['complementadresseetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':numerovoieetablissement', $oStock['numerovoieetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':indicerepetitionetablissement', $oStock['indicerepetitionetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':typevoieetablissement', $oStock['typevoieetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellevoieetablissement', $oStock['libellevoieetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codepostaletablissement', $oStock['codepostaletablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellecommuneetablissement', $oStock['libellecommuneetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellecommuneetrangeretablissement', $oStock['libellecommuneetrangeretablissement'], PDO::PARAM_STR);
        $sql->bindParam(':distributionspecialeetablissement', $oStock['distributionspecialeetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codecommuneetablissement', $oStock['codecommuneetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codecedexetablissement', $oStock['codecedexetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellecedexetablissement', $oStock['libellecedexetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codepaysetrangeretablissement', $oStock['codepaysetrangeretablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellepaysetrangeretablissement', $oStock['libellepaysetrangeretablissement'], PDO::PARAM_STR);
        $sql->bindParam(':complementadresse2etablissement', $oStock['complementadresse2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':numerovoie2etablissement', $oStock['numerovoie2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':indicerepetition2etablissement', $oStock['indicerepetition2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':typevoie2etablissement', $oStock['typevoie2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellevoie2etablissement', $oStock['libellevoie2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codepostal2etablissement', $oStock['codepostal2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellecommune2etablissement', $oStock['libellecommune2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellecommuneetranger2etablissement', $oStock['libellecommuneetranger2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':distributionspeciale2etablissement', $oStock['distributionspeciale2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codecommune2etablissement', $oStock['codecommune2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codecedex2etablissement', $oStock['codecedex2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellecedex2etablissement', $oStock['libellecedex2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codepaysetranger2etablissement', $oStock['codepaysetranger2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellepaysetranger2etablissement', $oStock['libellepaysetranger2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':datedebut', $oStock['datedebut'], PDO::PARAM_STR);
        $sql->bindParam(':etatadministratifetablissement', $oStock['etatadministratifetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':enseigne1etablissement', $oStock['enseigne1etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':enseigne2etablissement', $oStock['enseigne2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':enseigne3etablissement', $oStock['enseigne3etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':denominationusuelleetablissement', $oStock['denominationusuelleetablissement'], PDO::PARAM_STR);
        $sql->bindValue(':activiteprincipaleetablissement', str_replace(".", "", $oStock['activiteprincipaleetablissement']), PDO::PARAM_STR);
        $sql->bindParam(':nomenclatureactiviteprincipaleetablissement', $oStock['nomenclatureactiviteprincipaleetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':caractereemployeuretablissement', $oStock['caractereemployeuretablissement'], PDO::PARAM_STR);
        $sql->bindParam(':date_integration', $oStock['date_integration'], PDO::PARAM_STR);
        $sql->bindParam(':denomination_geoscar', $denominationGeoscar, PDO::PARAM_STR);
        $sql->execute();

        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            echo $sReq . "\n";
            $this->sendMailIncidentQuery($sReq, $aErreur[2]);
            //die($aErreur[2]);
        }
    }

//    public function insertStockFermesProd($oStock) {
//
//        //echo '---------------------------------------------------------------------insertStock \n';
//        //die(print_r($oStock));
//
//        $sReq = "INSERT INTO poi.sirene_etablissement_ferme(siren, nic, siret, statutdiffusionetablissement, datecreationetablissement, 
//        trancheeffectifsetablissement, anneeeffectifsetablissement, activiteprincipaleregistremetiersetablissement, 
//        datederniertraitementetablissement, etablissementsiege, nombreperiodesetablissement, 
//        complementadresseetablissement, numerovoieetablissement, indicerepetitionetablissement, 
//        typevoieetablissement, libellevoieetablissement, codepostaletablissement, libellecommuneetablissement, 
//        libellecommuneetrangeretablissement, distributionspecialeetablissement, codecommuneetablissement, codecedexetablissement, 
//        libellecedexetablissement, codepaysetrangeretablissement, libellepaysetrangeretablissement, complementadresse2etablissement, 
//        numerovoie2etablissement, indicerepetition2etablissement, typevoie2etablissement, libellevoie2etablissement, 
//        codepostal2etablissement, libellecommune2etablissement, libellecommuneetranger2etablissement, distributionspeciale2etablissement, 
//        codecommune2etablissement, codecedex2etablissement, libellecedex2etablissement, codepaysetranger2etablissement, 
//        libellepaysetranger2etablissement, datedebut, etatadministratifetablissement, enseigne1etablissement, enseigne2etablissement, 
//        enseigne3etablissement, denominationusuelleetablissement, activiteprincipaleetablissement, nomenclatureactiviteprincipaleetablissement, 
//        caractereemployeuretablissement, date_integration)
//	VALUES (:siren, :nic, :siret, :statutdiffusionetablissement, :datecreationetablissement, 
//        :trancheeffectifsetablissement, :anneeeffectifsetablissement, :activiteprincipaleregistremetiersetablissement, 
//        :datederniertraitementetablissement, :etablissementsiege, :nombreperiodesetablissement, 
//        :complementadresseetablissement, :numerovoieetablissement, :indicerepetitionetablissement, 
//        :typevoieetablissement, :libellevoieetablissement, :codepostaletablissement, :libellecommuneetablissement, 
//        :libellecommuneetrangeretablissement, :distributionspecialeetablissement, :codecommuneetablissement, :codecedexetablissement, 
//        :libellecedexetablissement, :codepaysetrangeretablissement, :libellepaysetrangeretablissement, :complementadresse2etablissement, 
//        :numerovoie2etablissement, :indicerepetition2etablissement, :typevoie2etablissement, :libellevoie2etablissement, 
//        :codepostal2etablissement, :libellecommune2etablissement, :libellecommuneetranger2etablissement, :distributionspeciale2etablissement, 
//        :codecommune2etablissement, :codecedex2etablissement, :libellecedex2etablissement, :codepaysetranger2etablissement, 
//        :libellepaysetranger2etablissement, :datedebut, :etatadministratifetablissement, :enseigne1etablissement, :enseigne2etablissement, 
//        :enseigne3etablissement, :denominationusuelleetablissement, :activiteprincipaleetablissement, :nomenclatureactiviteprincipaleetablissement, 
//        :caractereemployeuretablissement, :date_integration)";
//        $db = $this->getConnexionProd();
//        $sql = $db->prepare($sReq);
//        $sql->bindParam(':siren', $oStock['siren'], PDO::PARAM_STR);
//        $sql->bindParam(':nic', $oStock['nic'], PDO::PARAM_STR);
//        $sql->bindParam(':siret', $oStock['siret'], PDO::PARAM_STR);
//        $sql->bindParam(':statutdiffusionetablissement', $oStock['statutdiffusionetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':datecreationetablissement', $oStock['datecreationetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':trancheeffectifsetablissement', $oStock['trancheeffectifsetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':anneeeffectifsetablissement', $oStock['anneeeffectifsetablissement'], PDO::PARAM_INT);
//        $sql->bindParam(':activiteprincipaleregistremetiersetablissement', $oStock['activiteprincipaleregistremetiersetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':datederniertraitementetablissement', $oStock['datederniertraitementetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':etablissementsiege', $oStock['etablissementsiege'], PDO::PARAM_BOOL);
//        $sql->bindParam(':nombreperiodesetablissement', $oStock['nombreperiodesetablissement'], PDO::PARAM_INT);
//        $sql->bindParam(':complementadresseetablissement', $oStock['complementadresseetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':numerovoieetablissement', $oStock['numerovoieetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':indicerepetitionetablissement', $oStock['indicerepetitionetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':typevoieetablissement', $oStock['typevoieetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':libellevoieetablissement', $oStock['libellevoieetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':codepostaletablissement', $oStock['codepostaletablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':libellecommuneetablissement', $oStock['libellecommuneetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':libellecommuneetrangeretablissement', $oStock['libellecommuneetrangeretablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':distributionspecialeetablissement', $oStock['distributionspecialeetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':codecommuneetablissement', $oStock['codecommuneetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':codecedexetablissement', $oStock['codecedexetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':libellecedexetablissement', $oStock['libellecedexetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':codepaysetrangeretablissement', $oStock['codepaysetrangeretablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':libellepaysetrangeretablissement', $oStock['libellepaysetrangeretablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':complementadresse2etablissement', $oStock['complementadresse2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':numerovoie2etablissement', $oStock['numerovoie2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':indicerepetition2etablissement', $oStock['indicerepetition2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':typevoie2etablissement', $oStock['typevoie2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':libellevoie2etablissement', $oStock['libellevoie2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':codepostal2etablissement', $oStock['codepostal2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':libellecommune2etablissement', $oStock['libellecommune2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':libellecommuneetranger2etablissement', $oStock['libellecommuneetranger2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':distributionspeciale2etablissement', $oStock['distributionspeciale2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':codecommune2etablissement', $oStock['codecommune2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':codecedex2etablissement', $oStock['codecedex2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':libellecedex2etablissement', $oStock['libellecedex2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':codepaysetranger2etablissement', $oStock['codepaysetranger2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':libellepaysetranger2etablissement', $oStock['libellepaysetranger2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':datedebut', $oStock['datedebut'], PDO::PARAM_STR);
//        $sql->bindParam(':etatadministratifetablissement', $oStock['etatadministratifetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':enseigne1etablissement', $oStock['enseigne1etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':enseigne2etablissement', $oStock['enseigne2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':enseigne3etablissement', $oStock['enseigne3etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':denominationusuelleetablissement', $oStock['denominationusuelleetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':activiteprincipaleetablissement', $sActivite, PDO::PARAM_STR);
//        $sql->bindParam(':nomenclatureactiviteprincipaleetablissement', $oStock['nomenclatureactiviteprincipaleetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':caractereemployeuretablissement', $oStock['caractereemployeuretablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':date_integration', $oStock['date_integration'], PDO::PARAM_STR);
//        $sql->execute();
//
//        $aErreur = $sql->errorInfo();
//        if (strlen($aErreur[2]) > 0) {
//            echo $sReq . "\n";
//            die($aErreur[2]);
//        }
//    }

    public function trucateTmp() {
        echo 'truncate \n';
        $sQueryTrucnate = "truncate table poi.tmp_stock";
        $this->queryPDOResulset($sQueryTrucnate);

        $sQueryVaccum = "VACUUM ANALYZE poi.tmp_stock;";
        $this->queryPDOResulset($sQueryVaccum);
    }

    public function trucateGeosireneTmp() {
        echo 'truncate \n';
        $sQueryTrucnate = "truncate table poi.geosirene";
        $this->queryPDOResulset($sQueryTrucnate);

        $sQueryVaccum = "VACUUM ANALYZE poi.geosirene;";
        $this->queryPDOResulset($sQueryVaccum);
    }

    public function cleanStock() {

        $sQuery = "SELECT siret FROM poi.geosirene WHERE creation = true AND num_fic =1";
        $aCreationTrue = $this->queryPDOResulset($sQuery);

        if (count($aCreationTrue) > 0) {
            for ($i = 0; $i < count($aCreationTrue); $i++) {
                $sQueryDelete = "DELETE FROM poi.sirene_etablissement_n0 WHERE siret = '" . $aCreationTrue[$i]['siret'] . "'";
                $this->queryPDO($sQueryDelete);
            }
        }
    }

    public function cleanStockByDate($sDate) {

        $sQuery = "SELECT siret FROM poi.geosirene WHERE creation = true and date_integration = '" . $sDate . "'";
        $aCreationTrue = $this->queryPDOResulset($sQuery);

        if (count($aCreationTrue) > 0) {
            for ($i = 0; $i < count($aCreationTrue); $i++) {
                $sQueryDelete = "DELETE FROM poi.sirene_etablissement_n0 WHERE siret = '" . $aCreationTrue[$i]['siret'] . "'";
                $this->queryPDO($sQueryDelete);
            }
        }
    }

    public function insertTmpStock($oStock, $sDate) {

//        echo "ADRESSE  = ".$oStock['adresseEtablissement->libelleVoieEtablissement."\n";
//        echo "<pre>";
//        var_dump($oStock);
//        echo "</pre>";
//        
//        die();
        $db = $this->getConnexion();
        $sReq = "INSERT INTO poi.tmp_stock(
	siren, nic, siret, statutdiffusionetablissement, datecreationetablissement, trancheeffectifsetablissement, anneeeffectifsetablissement, 
        activiteprincipaleregistremetiersetablissement, datederniertraitementetablissement, etablissementsiege, nombreperiodesetablissement, 
        complementadresseetablissement, numerovoieetablissement, indicerepetitionetablissement, typevoieetablissement, libellevoieetablissement, 
        codepostaletablissement, libellecommuneetablissement, libellecommuneetrangeretablissement, distributionspecialeetablissement, 
        codecommuneetablissement, codecedexetablissement, libellecedexetablissement, codepaysetrangeretablissement, libellepaysetrangeretablissement, 
        complementadresse2etablissement, numerovoie2etablissement, indicerepetition2etablissement, typevoie2etablissement, libellevoie2etablissement, 
        codepostal2etablissement, libellecommune2etablissement, libellecommuneetranger2etablissement, distributionspeciale2etablissement, codecommune2etablissement, 
        codecedex2etablissement, libellecedex2etablissement, codepaysetranger2etablissement, libellepaysetranger2etablissement, datedebut, etatadministratifetablissement, 
        enseigne1etablissement, enseigne2etablissement, enseigne3etablissement, denominationusuelleetablissement, activiteprincipaleetablissement, 
        nomenclatureactiviteprincipaleetablissement, caractereemployeuretablissement, date_integration)
	VALUES (:siren, :nic, :siret, :statutdiffusionetablissement, :datecreationetablissement, 
        :trancheeffectifsetablissement, :anneeeffectifsetablissement, :activiteprincipaleregistremetiersetablissement, 
        :datederniertraitementetablissement, :etablissementsiege, :nombreperiodesetablissement, 
        :complementadresseetablissement, :numerovoieetablissement, :indicerepetitionetablissement, 
        :typevoieetablissement, :libellevoieetablissement, :codepostaletablissement, :libellecommuneetablissement, 
        :libellecommuneetrangeretablissement, :distributionspecialeetablissement, :codecommuneetablissement, :codecedexetablissement, 
        :libellecedexetablissement, :codepaysetrangeretablissement, :libellepaysetrangeretablissement, :complementadresse2etablissement, 
        :numerovoie2etablissement, :indicerepetition2etablissement, :typevoie2etablissement, :libellevoie2etablissement, 
        :codepostal2etablissement, :libellecommune2etablissement, :libellecommuneetranger2etablissement, :distributionspeciale2etablissement, 
        :codecommune2etablissement, :codecedex2etablissement, :libellecedex2etablissement, :codepaysetranger2etablissement, 
        :libellepaysetranger2etablissement, :datedebut, :etatadministratifetablissement, :enseigne1etablissement, :enseigne2etablissement, 
        :enseigne3etablissement, :denominationusuelleetablissement, :activiteprincipaleetablissement, :nomenclatureactiviteprincipaleetablissement, 
        :caractereemployeuretablissement, :date_integration);";
        $sActivitePrincipale = str_replace(".", "", $oStock->activitePrincipaleRegistreMetiersEtablissement);

        $sql = $db->prepare($sReq);
        $sql->bindParam(':siren', $oStock->siren, PDO::PARAM_STR);
        $sql->bindParam(':nic', $oStock->nic, PDO::PARAM_STR);
        $sql->bindParam(':siret', $oStock->siret, PDO::PARAM_STR);
        $sql->bindParam(':statutdiffusionetablissement', $oStock->statutDiffusionEtablissement, PDO::PARAM_STR);
        $sql->bindParam(':datecreationetablissement', $oStock->dateCreationEtablissement, PDO::PARAM_STR);
        $sql->bindParam(':trancheeffectifsetablissement', $oStock->trancheEffectifsEtablissement, PDO::PARAM_STR);
        $sql->bindParam(':anneeeffectifsetablissement', $oStock->anneeEffectifsEtablissement, PDO::PARAM_INT);
        $sql->bindParam(':activiteprincipaleregistremetiersetablissement', $sActivitePrincipale, PDO::PARAM_STR);
        $sql->bindParam(':datederniertraitementetablissement', $oStock->dateDernierTraitementEtablissement, PDO::PARAM_STR);
        $sql->bindParam(':etablissementsiege', $oStock->etablissementSiege, PDO::PARAM_BOOL);
        $sql->bindParam(':nombreperiodesetablissement', $oStock->nombrePeriodesEtablissement, PDO::PARAM_INT);
        $sql->bindParam(':complementadresseetablissement', $oStock->adresseEtablissement->complementAdresseEtablissement, PDO::PARAM_STR);
        $sql->bindParam(':numerovoieetablissement', $oStock->adresseEtablissement->numeroVoieEtablissement, PDO::PARAM_STR);
        $sql->bindParam(':indicerepetitionetablissement', $oStock->adresseEtablissement->indiceRepetitionEtablissement, PDO::PARAM_STR);
        $sql->bindParam(':typevoieetablissement', $oStock->adresseEtablissement->typeVoieEtablissement, PDO::PARAM_STR);
        $sql->bindParam(':libellevoieetablissement', $oStock->adresseEtablissement->libelleVoieEtablissement, PDO::PARAM_STR);
        $sql->bindParam(':codepostaletablissement', $oStock->adresseEtablissement->codePostalEtablissement, PDO::PARAM_STR);
        $sql->bindParam(':libellecommuneetablissement', $oStock->adresseEtablissement->libelleCommuneEtablissement, PDO::PARAM_STR);
        $sql->bindParam(':libellecommuneetrangeretablissement', $oStock->adresseEtablissement->libelleCommuneEtrangerEtablissement, PDO::PARAM_STR);
        $sql->bindParam(':distributionspecialeetablissement', $oStock->adresseEtablissement->distributionSpecialeEtablissement, PDO::PARAM_STR);
        $sql->bindParam(':codecommuneetablissement', $oStock->adresseEtablissement->codeCommuneEtablissement, PDO::PARAM_STR);
        $sql->bindParam(':codecedexetablissement', $oStock->adresseEtablissement->codeCedexEtablissement, PDO::PARAM_STR);
        $sql->bindParam(':libellecedexetablissement', $oStock->adresseEtablissement->libelleCedexEtablissement, PDO::PARAM_STR);
        $sql->bindParam(':codepaysetrangeretablissement', $oStock->adresseEtablissement->codePaysEtrangerEtablissement, PDO::PARAM_STR);
        $sql->bindParam(':libellepaysetrangeretablissement', $oStock->adresseEtablissement->libellePaysEtrangerEtablissement, PDO::PARAM_STR);
        $sql->bindParam(':complementadresse2etablissement', $oStock->adresse2Etablissement->complementAdresse2Etablissement, PDO::PARAM_STR);
        $sql->bindParam(':numerovoie2etablissement', $oStock->adresse2Etablissement->numeroVoie2Etablissement, PDO::PARAM_STR);
        $sql->bindParam(':indicerepetition2etablissement', $oStock->adresse2Etablissement->indiceRepetition2Etablissement, PDO::PARAM_STR);
        $sql->bindParam(':typevoie2etablissement', $oStock->adresse2Etablissement->typeVoie2Etablissement, PDO::PARAM_STR);
        $sql->bindParam(':libellevoie2etablissement', $oStock->adresse2Etablissement->libelleVoie2Etablissement, PDO::PARAM_STR);
        $sql->bindParam(':codepostal2etablissement', $oStock->adresse2Etablissement->codePostal2Etablissement, PDO::PARAM_STR);
        $sql->bindParam(':libellecommune2etablissement', $oStock->adresse2Etablissement->libelleCommune2Etablissement, PDO::PARAM_STR);
        $sql->bindParam(':libellecommuneetranger2etablissement', $oStock->adresse2Etablissement->libelleCommuneEtranger2Etablissement, PDO::PARAM_STR);
        $sql->bindParam(':distributionspeciale2etablissement', $oStock->adresse2Etablissement->distributionSpeciale2Etablissement, PDO::PARAM_STR);
        $sql->bindParam(':codecommune2etablissement', $oStock->adresse2Etablissement->codeCommune2Etablissement, PDO::PARAM_STR);
        $sql->bindParam(':codecedex2etablissement', $oStock->adresse2Etablissement->codeCedex2Etablissement, PDO::PARAM_STR);
        $sql->bindParam(':libellecedex2etablissement', $oStock->adresse2Etablissement->libelleCedex2Etablissement, PDO::PARAM_STR);
        $sql->bindParam(':codepaysetranger2etablissement', $oStock->adresse2Etablissement->codePaysEtranger2Etablissement, PDO::PARAM_STR);
        $sql->bindParam(':libellepaysetranger2etablissement', $oStock->adresse2Etablissement->libellePaysEtranger2Etablissement, PDO::PARAM_STR);
        $sql->bindParam(':datedebut', $oStock->periodesEtablissement[0]->dateDebut, PDO::PARAM_STR);
        $sql->bindParam(':etatadministratifetablissement', $oStock->periodesEtablissement[0]->etatAdministratifEtablissement, PDO::PARAM_STR);
        $sql->bindParam(':enseigne1etablissement', $oStock->periodesEtablissement[0]->enseigne1Etablissement, PDO::PARAM_STR);
        $sql->bindParam(':enseigne2etablissement', $oStock->periodesEtablissement[0]->enseigne2Etablissement, PDO::PARAM_STR);
        $sql->bindParam(':enseigne3etablissement', $oStock->periodesEtablissement[0]->enseigne3Etablissement, PDO::PARAM_STR);
        $sql->bindParam(':denominationusuelleetablissement', $oStock->periodesEtablissement[0]->denominationUsuelleEtablissement, PDO::PARAM_STR);
        $sql->bindParam(':activiteprincipaleetablissement', $oStock->periodesEtablissement[0]->activitePrincipaleEtablissement, PDO::PARAM_STR);
        $sql->bindParam(':nomenclatureactiviteprincipaleetablissement', $oStock->periodesEtablissement[0]->nomenclatureActivitePrincipaleEtablissement, PDO::PARAM_STR);
        $sql->bindParam(':caractereemployeuretablissement', $oStock->periodesEtablissement[0]->caractereEmployeurEtablissement, PDO::PARAM_STR);
        $sql->bindParam(':date_integration', $sDate, PDO::PARAM_STR);
        $sql->execute();
        $sql->closeCursor();
        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            echo $sReq . "\n";
            die($aErreur[2]);
        }
    }

    public function insertTmpStockArray($oStock, $sDate) {


        $db = $this->getConnexion();
        $sReq = "INSERT INTO poi.tmp_stock(
	siren, nic, siret, statutdiffusionetablissement, datecreationetablissement, trancheeffectifsetablissement, anneeeffectifsetablissement, 
        activiteprincipaleregistremetiersetablissement, datederniertraitementetablissement, etablissementsiege, nombreperiodesetablissement, 
        complementadresseetablissement, numerovoieetablissement, indicerepetitionetablissement, typevoieetablissement, libellevoieetablissement, 
        codepostaletablissement, libellecommuneetablissement, libellecommuneetrangeretablissement, distributionspecialeetablissement, 
        codecommuneetablissement, codecedexetablissement, libellecedexetablissement, codepaysetrangeretablissement, libellepaysetrangeretablissement, 
        complementadresse2etablissement, numerovoie2etablissement, indicerepetition2etablissement, typevoie2etablissement, libellevoie2etablissement, 
        codepostal2etablissement, libellecommune2etablissement, libellecommuneetranger2etablissement, distributionspeciale2etablissement, codecommune2etablissement, 
        codecedex2etablissement, libellecedex2etablissement, codepaysetranger2etablissement, libellepaysetranger2etablissement, datedebut, etatadministratifetablissement, 
        enseigne1etablissement, enseigne2etablissement, enseigne3etablissement, denominationusuelleetablissement, activiteprincipaleetablissement, 
        nomenclatureactiviteprincipaleetablissement, caractereemployeuretablissement, date_integration)
	VALUES (:siren, :nic, :siret, :statutdiffusionetablissement, :datecreationetablissement, 
        :trancheeffectifsetablissement, :anneeeffectifsetablissement, :activiteprincipaleregistremetiersetablissement, 
        :datederniertraitementetablissement, :etablissementsiege, :nombreperiodesetablissement, 
        :complementadresseetablissement, :numerovoieetablissement, :indicerepetitionetablissement, 
        :typevoieetablissement, :libellevoieetablissement, :codepostaletablissement, :libellecommuneetablissement, 
        :libellecommuneetrangeretablissement, :distributionspecialeetablissement, :codecommuneetablissement, :codecedexetablissement, 
        :libellecedexetablissement, :codepaysetrangeretablissement, :libellepaysetrangeretablissement, :complementadresse2etablissement, 
        :numerovoie2etablissement, :indicerepetition2etablissement, :typevoie2etablissement, :libellevoie2etablissement, 
        :codepostal2etablissement, :libellecommune2etablissement, :libellecommuneetranger2etablissement, :distributionspeciale2etablissement, 
        :codecommune2etablissement, :codecedex2etablissement, :libellecedex2etablissement, :codepaysetranger2etablissement, 
        :libellepaysetranger2etablissement, :datedebut, :etatadministratifetablissement, :enseigne1etablissement, :enseigne2etablissement, 
        :enseigne3etablissement, :denominationusuelleetablissement, :activiteprincipaleetablissement, :nomenclatureactiviteprincipaleetablissement, 
        :caractereemployeuretablissement, :date_integration);";
        $sActivitePrincipale = str_replace(".", "", $oStock['activitePrincipaleRegistreMetiersEtablissement']);

        $sql = $db->prepare($sReq);
        $sql->bindParam(':siren', $oStock['siren'], PDO::PARAM_STR);
        $sql->bindParam(':nic', $oStock['nic'], PDO::PARAM_STR);
        $sql->bindParam(':siret', $oStock['siret'], PDO::PARAM_STR);
        $sql->bindParam(':statutdiffusionetablissement', $oStock['statutdiffusionetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':datecreationetablissement', $oStock['datecreationetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':trancheeffectifsetablissement', $oStock['trancheeffectifsetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':anneeeffectifsetablissement', $oStock['anneeeffectifsetablissement'], PDO::PARAM_INT);
        $sql->bindParam(':activiteprincipaleregistremetiersetablissement', $sActivitePrincipale, PDO::PARAM_STR);
        $sql->bindParam(':datederniertraitementetablissement', $oStock['datederniertraitementetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':etablissementsiege', $oStock['etablissementsiege'], PDO::PARAM_BOOL);
        $sql->bindParam(':nombreperiodesetablissement', $oStock['nombreperiodesetablissement'], PDO::PARAM_INT);
        $sql->bindParam(':complementadresseetablissement', $oStock['adresseetablissement']['complementadresseetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':numerovoieetablissement', $oStock['adresseetablissement']['numerovoieetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':indicerepetitionetablissement', $oStock['adresseetablissement']['indicerepetitionetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':typevoieetablissement', $oStock['adresseetablissement']['typevoieetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellevoieetablissement', $oStock['adresseetablissement']['libellevoieetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codepostaletablissement', $oStock['adresseetablissement']['codepostaletablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellecommuneetablissement', $oStock['adresseetablissement']['libellecommuneetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellecommuneetrangeretablissement', $oStock['adresseetablissement']['libellecommuneetrangeretablissement'], PDO::PARAM_STR);
        $sql->bindParam(':distributionspecialeetablissement', $oStock['adresseetablissement']['distributionspecialeetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codecommuneetablissement', $oStock['adresseetablissement']['codecommuneetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codecedexetablissement', $oStock['adresseetablissement']['codecedexetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellecedexetablissement', $oStock['adresseetablissement']['libellecedexetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codepaysetrangeretablissement', $oStock['adresseetablissement']['codepaysetrangeretablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellepaysetrangeretablissement', $oStock['adresseetablissement']['libellepaysetrangeretablissement'], PDO::PARAM_STR);
        $sql->bindParam(':complementadresse2etablissement', $oStock['adresse2etablissement']['complementadresse2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':numerovoie2etablissement', $oStock['adresse2etablissement']['numerovoie2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':indicerepetition2etablissement', $oStock['adresse2etablissement']['indicerepetition2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':typevoie2etablissement', $oStock['adresse2etablissement']['typevoie2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellevoie2etablissement', $oStock['adresse2etablissement']['libellevoie2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codepostal2etablissement', $oStock['adresse2etablissement']['codepostal2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellecommune2etablissement', $oStock['adresse2etablissement']['libellecommune2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellecommuneetranger2etablissement', $oStock['adresse2etablissement']['libellecommuneetranger2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':distributionspeciale2etablissement', $oStock['adresse2etablissement']['distributionspeciale2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codecommune2etablissement', $oStock['adresse2etablissement']['codecommune2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codecedex2etablissement', $oStock['adresse2etablissement']['codecedex2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellecedex2etablissement', $oStock['adresse2etablissement']['libellecedex2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codepaysetranger2etablissement', $oStock['adresse2etablissement']['codepaysetranger2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellepaysetranger2etablissement', $oStock['adresse2etablissement']['libellepaysetranger2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':datedebut', $oStock['periodesetablissement'][0]['datedebutperiodesetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':etatadministratifetablissement', $oStock['periodesetablissement'][0]['etatadministratifetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':enseigne1etablissement', $oStock['periodesetablissement'][0]['enseigne1etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':enseigne2etablissement', $oStock['periodesetablissement'][0]['enseigne2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':enseigne3etablissement', $oStock['periodesetablissement'][0]['enseigne3etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':denominationusuelleetablissement', $oStock['periodesetablissement'][0]['denominationusuelleetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':activiteprincipaleetablissement', $oStock['periodesetablissement'][0]['activiteprincipaleetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':nomenclatureactiviteprincipaleetablissement', $oStock['periodesetablissement'][0]['nomenclatureactiviteprincipaleetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':caractereemployeuretablissement', $oStock['periodesetablissement'][0]['caractereemployeuretablissement'], PDO::PARAM_STR);
        $sql->bindParam(':date_integration', $sDate, PDO::PARAM_STR);
        $sql->execute();

        //$sql->closeCursor();
        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            echo $sReq . "\n";
            die($aErreur[2]);
        }
    }

    public function getTmpStock() {

        $sQuery = "SELECT * FROM poi.tmp_stock ";

        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);
        $sql->execute();
        $aRet = $sql->fetchAll(PDO::FETCH_ASSOC);
        //$sql->closeCursor();        
        return $aRet;
    }

    public function getTmpStockOffset($iOffset) {

        $sQuery = "SELECT * FROM poi.tmp_stock LIMIT 5000 OFFSET " . $iOffset;
        return $this->queryPDOResulset($sQuery);
    }

    public function getTmpStock1enreg() {


        $sQuery = "select * from poi.tmp_stock where siret ='05650295800145'";
        echo $sQuery . '\n';

        return $this->queryPDOResulset($sQuery);
    }

    public function isDiffusionCommercialeDiff($sSiret, $sDiff) {

        $bDiffComm = FALSE;
        $sQuery = "SELECT statutdiffusionetablissement  FROM poi.sirene_etablissement_n0 WHERE siret = '" . $sSiret . "' ";
        //echo $sQuery."\n";
        $aResult = $this->queryPDOResulset($sQuery);
        //echo "diff 1 = ".$aResult[0]['statutdiffusionetablissement']."\n";
        //echo "diff 2 = ".$sDiff."\n";
        if ($aResult[0]['statutdiffusionetablissement'] != "O" && $sDiff == "O") {
            $bDiffComm = TRUE;
        }
        return $bDiffComm;
    }

    public function ajoutFichierPourBano($oEtablissement) {
        //echo "ajoutFichierPourBano \n";

        $sAdresse = $this->formatAdressePourBano($oEtablissement);
        //Util::arrayDebug($oEtablissement);
        $aArrayEntetes = array("siren", "nic", "siret", "statutdiffusionetablissement", "datecreationetablissement", "trancheeffectifsetablissement", "anneeeffectifsetablissement",
            "activiteprincipaleregistremetiersetablissement", "datederniertraitementetablissement", "etablissementsiege", "nombreperiodesetablissement", "complementadresseetablissement",
            "numerovoieetablissement", "indicerepetitionetablissement", "typevoieetablissement", "libellevoieetablissement", "codepostaletablissement", "libellecommuneetablissement",
            "libellecommuneetrangeretablissement", "distributionspecialeetablissement", "codecommuneetablissement", "codecedexetablissement", "libellecedexetablissement",
            "codepaysetrangeretablissement", "libellepaysetrangeretablissement", "complementadresse2etablissement", "numerovoie2etablissement", "indicerepetition2etablissement",
            "typevoie2etablissement", "libellevoie2etablissement", "codepostal2etablissement", "libellecommune2etablissement", "libellecommuneetranger2etablissement",
            "distributionspeciale2etablissement", "codecommune2etablissement", "codecedex2etablissement", "libellecedex2etablissement", "codepaysetranger2etablissement",
            "libellepaysetranger2etablissement", "datedebut", "etatadministratifetablissement", "enseigne1etablissement", "enseigne2etablissement", "enseigne3etablissement",
            "denominationusuelleetablissement", "activiteprincipaleetablissement", "nomenclatureactiviteprincipaleetablissement", "caractereemployeuretablissement", "adresse", "numfic");

        $aArrayValeurs = array($oEtablissement->siren, $oEtablissement->nic, $oEtablissement->siret, $oEtablissement->statutDiffusionEtablissement, $oEtablissement->dateCreationEtablissement, $oEtablissement->trancheEffectifsEtablissement, $oEtablissement->anneeEffectifsEtablissement,
            $oEtablissement->activitePrincipaleRegistreMetiersEtablissement, $oEtablissement->dateDernierTraitementEtablissement, $oEtablissement->etablissementSiege, $oEtablissement->nombrePeriodesEtablissement, $oEtablissement->adresseEtablissement->complementAdresseEtablissement,
            $oEtablissement->adresseEtablissement->numeroVoieEtablissement,
            $oEtablissement->adresseEtablissement->indiceRepetitionEtablissement,
            $oEtablissement->adresseEtablissement->typeVoieEtablissement,
            $oEtablissement->adresseEtablissement->libelleVoieEtablissement,
            $oEtablissement->adresseEtablissement->codePostalEtablissement,
            $oEtablissement->adresseEtablissement->libelleCommuneEtablissement,
            $oEtablissement->adresseEtablissement->libelleCommuneEtrangerEtablissement,
            $oEtablissement->adresseEtablissement->distributionSpecialeEtablissement,
            $oEtablissement->adresseEtablissement->codeCommuneEtablissement,
            $oEtablissement->adresseEtablissement->codeCedexEtablissement,
            $oEtablissement->adresseEtablissement->libelleCedexEtablissement,
            $oEtablissement->adresseEtablissement->codePaysEtrangerEtablissement,
            $oEtablissement->adresseEtablissement->libellePaysEtrangerEtablissement,
            $oEtablissement->adresse2Etablissement->complementAdresse2Etablissement,
            $oEtablissement->adresse2Etablissement->numeroVoie2Etablissement,
            $oEtablissement->adresse2Etablissement->indiceRepetition2Etablissement,
            $oEtablissement->adresse2Etablissement->typeVoie2Etablissement,
            $oEtablissement->adresse2Etablissement->libelleVoie2Etablissement,
            $oEtablissement->adresse2Etablissement->codePostal2Etablissement,
            $oEtablissement->adresse2Etablissement->libelleCommune2Etablissement,
            $oEtablissement->adresse2Etablissement->libelleCommuneEtranger2Etablissement,
            $oEtablissement->adresse2Etablissement->distributionSpeciale2Etablissement,
            $oEtablissement->adresse2Etablissement->codeCommune2Etablissement,
            $oEtablissement->adresse2Etablissement->codeCedex2Etablissement,
            $oEtablissement->adresse2Etablissement->libelleCedex2Etablissement,
            $oEtablissement->adresse2Etablissement->codePaysEtranger2Etablissement,
            $oEtablissement->adresse2Etablissement->libellePaysEtranger2Etablissement,
            $oEtablissement->periodesEtablissement[0]->dateDebut,
            $oEtablissement->periodesEtablissement[0]->etatAdministratifEtablissement,
            $oEtablissement->periodesEtablissement[0]->enseigne1Etablissement,
            $oEtablissement->periodesEtablissement[0]->enseigne2Etablissement,
            $oEtablissement->periodesEtablissement[0]->enseigne3Etablissement,
            $oEtablissement->periodesEtablissement[0]->denominationUsuelleEtablissement,
            str_replace(".", "", $oEtablissement->periodesEtablissement[0]->activitePrincipaleEtablissement),
            $oEtablissement->periodesEtablissement[0]->nomenclatureActivitePrincipaleEtablissement,
            $oEtablissement->periodesEtablissement[0]->caractereEmployeurEtablissement,
            $sAdresse, "1");

        // SI FICHIER N'EXISTE PAS ON CREE LE FICHIER ET LES ENTETES
        if (!file_exists(FILE_RESULT_POUR_BANO)) {
            $fp = fopen(FILE_RESULT_POUR_BANO, 'a+');
            fputcsv($fp, $aArrayEntetes, ';', "*");
            fputcsv($fp, $aArrayValeurs, ';', "*");
        } else {
            $fp = fopen(FILE_RESULT_POUR_BANO, 'a+');
            fputcsv($fp, $aArrayValeurs, ';', "*");
        }
        fclose($fp);
        // REMPLACEMENT DES * MISES EN ENCLOSURE OBLIGATOIRE DE fputcsv
        $replace = str_replace("*", "", file_get_contents(FILE_RESULT_POUR_BANO));
        file_put_contents(FILE_RESULT_POUR_BANO, $replace);
    }

    public function ajoutFichierPourBanoLight($oEtablissement) {
        //echo "ajoutFichierPourBano \n";

        $sAdresse = $this->formatAdressePourBano($oEtablissement);
        //Util::arrayDebug($oEtablissement);
        $aArrayEntetes = array("siret", "adresse");

        $aArrayValeurs = array($oEtablissement->siret, $sAdresse);

        // SI FICHIER N'EXISTE PAS ON CREE LE FICHIER ET LES ENTETES
        if (!file_exists(FILE_RESULT_POUR_BANO)) {
            $fp = fopen(FILE_RESULT_POUR_BANO, 'a+');
            fputcsv($fp, $aArrayEntetes, ';', "*");
            fputcsv($fp, $aArrayValeurs, ';', "*");
        } else {
            $fp = fopen(FILE_RESULT_POUR_BANO, 'a+');
            fputcsv($fp, $aArrayValeurs, ';', "*");
        }
        fclose($fp);
        // REMPLACEMENT DES * MISES EN ENCLOSURE OBLIGATOIRE DE fputcsv
        $replace = str_replace("*", "", file_get_contents(FILE_RESULT_POUR_BANO));
        file_put_contents(FILE_RESULT_POUR_BANO, $replace);
    }

    public function ajoutFichierPourBanoLightStock($oEtablissement) {
        //echo "ajoutFichierPourBano \n";

        $sAdresse = $oEtablissement['numerovoieetablissement'] . " " . $oEtablissement['typevoieetablissement'] . " " . $oEtablissement['libellevoieetablissement'] . " " . $oEtablissement['codepostaletablissement'] . " " . $oEtablissement['libellecommuneetablissement'];
        //Util::arrayDebug($oEtablissement);
        $aArrayEntetes = array("siret", "adresse");

        $aArrayValeurs = array($oEtablissement['siret'], $sAdresse);

        // SI FICHIER N'EXISTE PAS ON CREE LE FICHIER ET LES ENTETES
        if (!file_exists(FILE_RESULT_POUR_BANO)) {
            $fp = fopen(FILE_RESULT_POUR_BANO, 'a+');
            fputcsv($fp, $aArrayEntetes, ';', "*");
            fputcsv($fp, $aArrayValeurs, ';', "*");
        } else {
            $fp = fopen(FILE_RESULT_POUR_BANO, 'a+');
            fputcsv($fp, $aArrayValeurs, ';', "*");
        }
        fclose($fp);
        // REMPLACEMENT DES * MISES EN ENCLOSURE OBLIGATOIRE DE fputcsv
        $replace = str_replace("*", "", file_get_contents(FILE_RESULT_POUR_BANO));
        file_put_contents(FILE_RESULT_POUR_BANO, $replace);
    }

    public function formatAdressePourBano($oEtablissement) {

        $sAdresse = $oEtablissement->adresseEtablissement->numeroVoieEtablissement . " " . $oEtablissement->adresseEtablissement->libelleVoieEtablissement . " " . $oEtablissement->adresseEtablissement->codePostalEtablissement . " " . $sAdresse = $oEtablissement->adresseEtablissement->libelleCommuneEtablissement;

        return $sAdresse;
    }

    public function formatAdressePourBanoTableau($aEtablissement) {

        $sAdresse = $aEtablissement['numerovoieetablissement'] . " " . $aEtablissement['libellevoieetablissement'] . " " . $aEtablissement['codepostaletablissement'] . " " . $aEtablissement['libellecommuneetablissement'];

        return str_replace(" ", "+", $sAdresse);
    }

    public function formatEnseignePj($enseigne) {

        $enseigneRetour = "";

        if (strpos($enseigne, "-")) {
            $enseigneRetour = str_replace("-", "+", $enseigne);
        }
        if (strpos($enseigne, "/")) {
            $enseigneRetour = str_replace("/", "+", $enseigne);
        }
        if (strpos($enseigne, ".")) {
            $enseigneRetour = str_replace(".", "+", $enseigne);
        }
        if (strpos($enseigne, " ")) {
            $enseigneRetour = str_replace(" ", "+", $enseigne);
        }
        return $enseigneRetour;
    }

    public function getGeosireneStock() {
        $sQuery = "select siret from poi.geosirene_stock where num_fic is null";
        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);
        $sql->execute();

        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            echo $sQuery . "\n";
            die($aErreur[2]);
        } else {
            return $sql->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    public function updateGeosireneStock($oStock) {

        //echo "***********************UPDATE STOCK " . $oStock['siret'] . "................................\n";
        //die(print_r($oStock));
        $sQuery = "UPDATE poi.geosirene_stock SET  
        statutdiffusionetablissement=:statutdiffusionetablissement, 
        datecreationetablissement=:datecreationetablissement, 
        trancheeffectifsetablissement=:trancheeffectifsetablissement, 
        anneeeffectifsetablissement=:anneeeffectifsetablissement, 
        activiteprincipaleregistremetiersetablissement=:activiteprincipaleregistremetiersetablissement, 
        datederniertraitementetablissement=:datederniertraitementetablissement, 
        etablissementsiege=:etablissementsiege, 
        nombreperiodesetablissement=:nombreperiodesetablissement, 
        complementadresseetablissement=:complementadresseetablissement, 
        numerovoieetablissement=:numerovoieetablissement, 
        indicerepetitionetablissement=:indicerepetitionetablissement, 
        typevoieetablissement=:typevoieetablissement, 
        libellevoieetablissement=:libellevoieetablissement, 
        codepostaletablissement=:codepostaletablissement, 
        libellecommuneetablissement=:libellecommuneetablissement, 
        libellecommuneetrangeretablissement=:libellecommuneetrangeretablissement, 
        distributionspecialeetablissement=:distributionspecialeetablissement, 
        codecommuneetablissement=:codecommuneetablissement, codecedexetablissement=:codecedexetablissement, 
        libellecedexetablissement=:libellecedexetablissement, 
        codepaysetrangeretablissement=:codepaysetrangeretablissement, 
        libellepaysetrangeretablissement=:libellepaysetrangeretablissement, 
        complementadresse2etablissement=:complementadresse2etablissement, 
        numerovoie2etablissement=:numerovoie2etablissement, 
        indicerepetition2etablissement=:indicerepetition2etablissement, 
        typevoie2etablissement=:typevoie2etablissement, 
        libellevoie2etablissement=:libellevoie2etablissement, 
        codepostal2etablissement=:codepostal2etablissement, 
        libellecommune2etablissement=:libellecommune2etablissement, 
        libellecommuneetranger2etablissement=:libellecommuneetranger2etablissement, 
        distributionspeciale2etablissement=:distributionspeciale2etablissement, 
        codecommune2etablissement=:codecommune2etablissement, 
        codecedex2etablissement=:codecedex2etablissement, 
        libellecedex2etablissement=:libellecedex2etablissement, 
        codepaysetranger2etablissement=:codepaysetranger2etablissement, 
        libellepaysetranger2etablissement=:libellepaysetranger2etablissement, 
        datedebut=:datedebut, 
        etatadministratifetablissement=:etatadministratifetablissement, 
        enseigne1etablissement=:enseigne1etablissement, 
        enseigne2etablissement=:enseigne2etablissement, 
        enseigne3etablissement=:enseigne3etablissement, 
        denominationusuelleetablissement=:denominationusuelleetablissement, 
        activiteprincipaleetablissement=:activiteprincipaleetablissement, 
        nomenclatureactiviteprincipaleetablissement=:nomenclatureactiviteprincipaleetablissement, 
        caractereemployeuretablissement=:caractereemployeuretablissement
	WHERE siret=:siret;";
        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);
        $sql->bindParam(':statutdiffusionetablissement', $oStock['statutdiffusionetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':datecreationetablissement', $oStock['datecreationetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':trancheeffectifsetablissement', $oStock['trancheeffectifsetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':anneeeffectifsetablissement', $oStock['anneeeffectifsetablissement'], PDO::PARAM_INT);
        $sql->bindParam(':activiteprincipaleregistremetiersetablissement', $oStock['activiteprincipaleregistremetiersetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':datederniertraitementetablissement', $oStock['dateDerniertraitementetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':etablissementsiege', $oStock['etablissementsiege'], PDO::PARAM_BOOL);
        $sql->bindParam(':nombreperiodesetablissement', $oStock['nombreperiodesetablissement'], PDO::PARAM_INT);
        $sql->bindParam(':complementadresseetablissement', $oStock['complementadresseetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':numerovoieetablissement', $oStock['numerovoieetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':indicerepetitionetablissement', $oStock['indicerepetitionetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':typevoieetablissement', $oStock['typevoieetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellevoieetablissement', $oStock['libellevoieetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codepostaletablissement', $oStock['codepostaletablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellecommuneetablissement', $oStock['libellecommuneetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellecommuneetrangeretablissement', $oStock['libellecommuneetrangeretablissement'], PDO::PARAM_STR);
        $sql->bindParam(':distributionspecialeetablissement', $oStock['distributionspecialeetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codecommuneetablissement', $oStock['codeCommuneetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codecedexetablissement', $oStock['codecedexetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellecedexetablissement', $oStock['libellecedexetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codepaysetrangeretablissement', $oStock['codePaysetrangeretablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellepaysetrangeretablissement', $oStock['libellepaysetrangeretablissement'], PDO::PARAM_STR);
        $sql->bindParam(':complementadresse2etablissement', $oStock['complementadresse2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':numerovoie2etablissement', $oStock['numerovoie2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':indicerepetition2etablissement', $oStock['indicerepetition2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':typevoie2etablissement', $oStock['typevoie2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellevoie2etablissement', $oStock['libellevoie2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codepostal2etablissement', $oStock['codepostal2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellecommune2etablissement', $oStock['libellecommune2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellecommuneetranger2etablissement', $oStock['libellecommuneetranger2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':distributionspeciale2etablissement', $oStock['distributionspeciale2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codecommune2etablissement', $oStock['codecommune2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codecedex2etablissement', $oStock['codecedex2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellecedex2etablissement', $oStock['libellecedex2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codepaysetranger2etablissement', $oStock['codepaysetranger2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellepaysetranger2etablissement', $oStock['libellepaysetranger2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':datedebut', $oStock['dateDebut'], PDO::PARAM_STR);
        $sql->bindParam(':etatadministratifetablissement', $oStock['etatadministratifetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':enseigne1etablissement', $oStock['enseigne1etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':enseigne2etablissement', $oStock['enseigne2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':enseigne3etablissement', $oStock['enseigne3etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':denominationusuelleetablissement', $oStock['denominationusuelleetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':activiteprincipaleetablissement', $oStock['activiteprincipaleetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':nomenclatureactiviteprincipaleetablissement', $oStock['nomenclatureactiviteprincipaleetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':caractereemployeuretablissement', $oStock['caractereemployeuretablissement'], PDO::PARAM_STR);
        $sql->bindParam(':siret', $oStock['siret'], PDO::PARAM_STR);

        $sql->execute();

        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            echo $sQuery . "\n";
            die($aErreur[2]);
        }
    }

    public function updateStock($oStock, $dateIntegration, $creation, $denominationGeoscar) {

        //echo "***********************UPDATE STOCK " . $oStock['siret'] . "................................\n";


        $sQuery = "UPDATE poi.sirene_etablissement_n0 SET  
        statutdiffusionetablissement=:statutdiffusionetablissement, 
        datecreationetablissement=:datecreationetablissement, 
        trancheeffectifsetablissement=:trancheeffectifsetablissement, 
        anneeeffectifsetablissement=:anneeeffectifsetablissement, 
        activiteprincipaleregistremetiersetablissement=:activiteprincipaleregistremetiersetablissement, 
        datederniertraitementetablissement=:datederniertraitementetablissement, 
        etablissementsiege=:etablissementsiege, 
        nombreperiodesetablissement=:nombreperiodesetablissement, 
        complementadresseetablissement=:complementadresseetablissement, 
        numerovoieetablissement=:numerovoieetablissement, 
        indicerepetitionetablissement=:indicerepetitionetablissement, 
        typevoieetablissement=:typevoieetablissement, 
        libellevoieetablissement=:libellevoieetablissement, 
        codepostaletablissement=:codepostaletablissement, 
        libellecommuneetablissement=:libellecommuneetablissement, 
        libellecommuneetrangeretablissement=:libellecommuneetrangeretablissement, 
        distributionspecialeetablissement=:distributionspecialeetablissement, 
        codecommuneetablissement=:codecommuneetablissement, codecedexetablissement=:codecedexetablissement, 
        libellecedexetablissement=:libellecedexetablissement, 
        codepaysetrangeretablissement=:codepaysetrangeretablissement, 
        libellepaysetrangeretablissement=:libellepaysetrangeretablissement, 
        complementadresse2etablissement=:complementadresse2etablissement, 
        numerovoie2etablissement=:numerovoie2etablissement, 
        indicerepetition2etablissement=:indicerepetition2etablissement, 
        typevoie2etablissement=:typevoie2etablissement, 
        libellevoie2etablissement=:libellevoie2etablissement, 
        codepostal2etablissement=:codepostal2etablissement, 
        libellecommune2etablissement=:libellecommune2etablissement, 
        libellecommuneetranger2etablissement=:libellecommuneetranger2etablissement, 
        distributionspeciale2etablissement=:distributionspeciale2etablissement, 
        codecommune2etablissement=:codecommune2etablissement, 
        codecedex2etablissement=:codecedex2etablissement, 
        libellecedex2etablissement=:libellecedex2etablissement, 
        codepaysetranger2etablissement=:codepaysetranger2etablissement, 
        libellepaysetranger2etablissement=:libellepaysetranger2etablissement, 
        datedebut=:datedebut, 
        etatadministratifetablissement=:etatadministratifetablissement, 
        enseigne1etablissement=:enseigne1etablissement, 
        enseigne2etablissement=:enseigne2etablissement, 
        enseigne3etablissement=:enseigne3etablissement, 
        denominationusuelleetablissement=:denominationusuelleetablissement, 
        activiteprincipaleetablissement=:activiteprincipaleetablissement, 
        nomenclatureactiviteprincipaleetablissement=:nomenclatureactiviteprincipaleetablissement, 
        caractereemployeuretablissement=:caractereemployeuretablissement, date_integration =:date_integration, creation=:creation ,
        denomination_geoscar =:denomination_geoscar
	WHERE siret=:siret;";
        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);
        $sql->bindParam(':statutdiffusionetablissement', $oStock['statutdiffusionetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':datecreationetablissement', $oStock['datecreationetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':trancheeffectifsetablissement', $oStock['trancheeffectifsetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':anneeeffectifsetablissement', $oStock['anneeeffectifsetablissement'], PDO::PARAM_INT);
        $sql->bindParam(':activiteprincipaleregistremetiersetablissement', $oStock['activiteprincipaleregistremetiersetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':datederniertraitementetablissement', $oStock['dateDerniertraitementetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':etablissementsiege', $oStock['etablissementsiege'], PDO::PARAM_BOOL);
        $sql->bindParam(':nombreperiodesetablissement', $oStock['nombreperiodesetablissement'], PDO::PARAM_INT);
        $sql->bindParam(':complementadresseetablissement', $oStock['complementadresseetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':numerovoieetablissement', $oStock['numerovoieetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':indicerepetitionetablissement', $oStock['indicerepetitionetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':typevoieetablissement', $oStock['typevoieetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellevoieetablissement', $oStock['libellevoieetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codepostaletablissement', $oStock['codepostaletablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellecommuneetablissement', $oStock['libellecommuneetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellecommuneetrangeretablissement', $oStock['libellecommuneetrangeretablissement'], PDO::PARAM_STR);
        $sql->bindParam(':distributionspecialeetablissement', $oStock['distributionspecialeetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codecommuneetablissement', $oStock['codeCommuneetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codecedexetablissement', $oStock['codecedexetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellecedexetablissement', $oStock['libellecedexetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codepaysetrangeretablissement', $oStock['codePaysetrangeretablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellepaysetrangeretablissement', $oStock['libellepaysetrangeretablissement'], PDO::PARAM_STR);
        $sql->bindParam(':complementadresse2etablissement', $oStock['complementadresse2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':numerovoie2etablissement', $oStock['numerovoie2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':indicerepetition2etablissement', $oStock['indicerepetition2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':typevoie2etablissement', $oStock['typevoie2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellevoie2etablissement', $oStock['libellevoie2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codepostal2etablissement', $oStock['codepostal2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellecommune2etablissement', $oStock['libellecommune2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellecommuneetranger2etablissement', $oStock['libellecommuneetranger2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':distributionspeciale2etablissement', $oStock['distributionspeciale2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codecommune2etablissement', $oStock['codecommune2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codecedex2etablissement', $oStock['codecedex2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellecedex2etablissement', $oStock['libellecedex2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':codepaysetranger2etablissement', $oStock['codepaysetranger2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':libellepaysetranger2etablissement', $oStock['libellepaysetranger2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':datedebut', $oStock['dateDebut'], PDO::PARAM_STR);
        $sql->bindParam(':etatadministratifetablissement', $oStock['etatadministratifetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':enseigne1etablissement', $oStock['enseigne1etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':enseigne2etablissement', $oStock['enseigne2etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':enseigne3etablissement', $oStock['enseigne3etablissement'], PDO::PARAM_STR);
        $sql->bindParam(':denominationusuelleetablissement', $oStock['denominationusuelleetablissement'], PDO::PARAM_STR);
        $sql->bindValue(':activiteprincipaleetablissement', str_replace(".", "", $oStock['activiteprincipaleetablissement']), PDO::PARAM_STR);
        $sql->bindParam(':nomenclatureactiviteprincipaleetablissement', $oStock['nomenclatureactiviteprincipaleetablissement'], PDO::PARAM_STR);
        $sql->bindParam(':caractereemployeuretablissement', $oStock['caractereemployeuretablissement'], PDO::PARAM_STR);
        $sql->bindParam(':date_integration', $dateIntegration, PDO::PARAM_STR);
        $sql->bindParam(':siret', $oStock['siret'], PDO::PARAM_STR);
        $sql->bindParam(':creation', $creation, PDO::PARAM_STR);
        $sql->bindParam(':denomination_geoscar', $denominationGeoscar, PDO::PARAM_STR);


        $sql->execute();
//
        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            echo $sQuery . "\n";
            $this->sendMailIncidentQuery($sQuery, $aErreur[2]);
        }
//        
//        
//        $db->beginTransaction();
//        try {
//            $sql->execute();
//            $db->commit();
//        } catch (Exception $e) {
//            $db->rollBack();
//            exit();
//        }
        //echo "***********************FIN UPDATE STOCK " . $oStock['siret'] . "................................\n";
        /* echo '<pre>';
          var_dump($oStock);
          echo '</pre>';
          die(); */
    }

//    public function updateStockProd($oStock) {
//
//        //echo "***********************UPDATE STOCK " . $oStock['siret'] . "................................\n";
//
//
//        $sQuery = "UPDATE poi.sirene_etablissement_n0 SET  
//        statutdiffusionetablissement=:statutdiffusionetablissement, 
//        datecreationetablissement=:datecreationetablissement, 
//        trancheeffectifsetablissement=:trancheeffectifsetablissement, 
//        anneeeffectifsetablissement=:anneeeffectifsetablissement, 
//        activiteprincipaleregistremetiersetablissement=:activiteprincipaleregistremetiersetablissement, 
//        datederniertraitementetablissement=:datederniertraitementetablissement, 
//        etablissementsiege=:etablissementsiege, 
//        nombreperiodesetablissement=:nombreperiodesetablissement, 
//        complementadresseetablissement=:complementadresseetablissement, 
//        numerovoieetablissement=:numerovoieetablissement, 
//        indicerepetitionetablissement=:indicerepetitionetablissement, 
//        typevoieetablissement=:typevoieetablissement, 
//        libellevoieetablissement=:libellevoieetablissement, 
//        codepostaletablissement=:codepostaletablissement, 
//        libellecommuneetablissement=:libellecommuneetablissement, 
//        libellecommuneetrangeretablissement=:libellecommuneetrangeretablissement, 
//        distributionspecialeetablissement=:distributionspecialeetablissement, 
//        codecommuneetablissement=:codecommuneetablissement, codecedexetablissement=:codecedexetablissement, 
//        libellecedexetablissement=:libellecedexetablissement, 
//        codepaysetrangeretablissement=:codepaysetrangeretablissement, 
//        libellepaysetrangeretablissement=:libellepaysetrangeretablissement, 
//        complementadresse2etablissement=:complementadresse2etablissement, 
//        numerovoie2etablissement=:numerovoie2etablissement, 
//        indicerepetition2etablissement=:indicerepetition2etablissement, 
//        typevoie2etablissement=:typevoie2etablissement, 
//        libellevoie2etablissement=:libellevoie2etablissement, 
//        codepostal2etablissement=:codepostal2etablissement, 
//        libellecommune2etablissement=:libellecommune2etablissement, 
//        libellecommuneetranger2etablissement=:libellecommuneetranger2etablissement, 
//        distributionspeciale2etablissement=:distributionspeciale2etablissement, 
//        codecommune2etablissement=:codecommune2etablissement, 
//        codecedex2etablissement=:codecedex2etablissement, 
//        libellecedex2etablissement=:libellecedex2etablissement, 
//        codepaysetranger2etablissement=:codepaysetranger2etablissement, 
//        libellepaysetranger2etablissement=:libellepaysetranger2etablissement, 
//        datedebut=:datedebut, 
//        etatadministratifetablissement=:etatadministratifetablissement, 
//        enseigne1etablissement=:enseigne1etablissement, 
//        enseigne2etablissement=:enseigne2etablissement, 
//        enseigne3etablissement=:enseigne3etablissement, 
//        denominationusuelleetablissement=:denominationusuelleetablissement, 
//        activiteprincipaleetablissement=:activiteprincipaleetablissement, 
//        nomenclatureactiviteprincipaleetablissement=:nomenclatureactiviteprincipaleetablissement, 
//        caractereemployeuretablissement=:caractereemployeuretablissement
//	WHERE siret=:siret;";
//        $db = $this->getConnexionProd();
//        $sql = $db->prepare($sQuery);
//        $sql->bindParam(':statutdiffusionetablissement', $oStock['statutdiffusionetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':datecreationetablissement', $oStock['datecreationetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':trancheeffectifsetablissement', $oStock['trancheeffectifsetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':anneeeffectifsetablissement', $oStock['anneeeffectifsetablissement'], PDO::PARAM_INT);
//        $sql->bindParam(':activiteprincipaleregistremetiersetablissement', $oStock['activiteprincipaleregistremetiersetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':datederniertraitementetablissement', $oStock['dateDerniertraitementetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':etablissementsiege', $oStock['etablissementsiege'], PDO::PARAM_BOOL);
//        $sql->bindParam(':nombreperiodesetablissement', $oStock['nombreperiodesetablissement'], PDO::PARAM_INT);
//        $sql->bindParam(':complementadresseetablissement', $oStock['complementadresseetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':numerovoieetablissement', $oStock['numerovoieetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':indicerepetitionetablissement', $oStock['indicerepetitionetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':typevoieetablissement', $oStock['typevoieetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':libellevoieetablissement', $oStock['libellevoieetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':codepostaletablissement', $oStock['codepostaletablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':libellecommuneetablissement', $oStock['libellecommuneetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':libellecommuneetrangeretablissement', $oStock['libellecommuneetrangeretablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':distributionspecialeetablissement', $oStock['distributionspecialeetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':codecommuneetablissement', $oStock['codeCommuneetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':codecedexetablissement', $oStock['codecedexetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':libellecedexetablissement', $oStock['libellecedexetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':codepaysetrangeretablissement', $oStock['codePaysetrangeretablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':libellepaysetrangeretablissement', $oStock['libellepaysetrangeretablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':complementadresse2etablissement', $oStock['complementadresse2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':numerovoie2etablissement', $oStock['numerovoie2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':indicerepetition2etablissement', $oStock['indicerepetition2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':typevoie2etablissement', $oStock['typevoie2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':libellevoie2etablissement', $oStock['libellevoie2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':codepostal2etablissement', $oStock['codepostal2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':libellecommune2etablissement', $oStock['libellecommune2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':libellecommuneetranger2etablissement', $oStock['libellecommuneetranger2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':distributionspeciale2etablissement', $oStock['distributionspeciale2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':codecommune2etablissement', $oStock['codecommune2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':codecedex2etablissement', $oStock['codecedex2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':libellecedex2etablissement', $oStock['libellecedex2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':codepaysetranger2etablissement', $oStock['codepaysetranger2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':libellepaysetranger2etablissement', $oStock['libellepaysetranger2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':datedebut', $oStock['dateDebut'], PDO::PARAM_STR);
//        $sql->bindParam(':etatadministratifetablissement', $oStock['etatadministratifetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':enseigne1etablissement', $oStock['enseigne1etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':enseigne2etablissement', $oStock['enseigne2etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':enseigne3etablissement', $oStock['enseigne3etablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':denominationusuelleetablissement', $oStock['denominationusuelleetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':activiteprincipaleetablissement', $oStock['activiteprincipaleetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':nomenclatureactiviteprincipaleetablissement', $oStock['nomenclatureactiviteprincipaleetablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':caractereemployeuretablissement', $oStock['caractereemployeuretablissement'], PDO::PARAM_STR);
//        $sql->bindParam(':siret', $oStock['siret'], PDO::PARAM_STR);
//
//        $sql->execute();
//
//        $aErreur = $sql->errorInfo();
//        if (strlen($aErreur[2]) > 0) {
//            echo $sQuery . "\n";
//            die($aErreur[2]);
//        }
//        //echo "***********************FIN UPDATE STOCK " . $oStock['siret'] . "................................\n";
//        /* echo '<pre>';
//          var_dump($oStock);
//          echo '</pre>';
//          die(); */
//    }

    public function getAllGeosirene() {
        $sQuery = "SELECT * FROM poi.geosirene";
        return $this->queryPDOResulset($sQuery);
    }

    public function getJsonStatCreationParJour() {
        $sQuery = "select json_agg(t) from (select d.region, d.dep,  g.etablissementsiege, count(*) as nbOuverure
                  from poi.geosirene g INNER JOIN poi.dep d 
                  ON d.cp = substring(g.result_postcode, 1, 2)
                  where creation =true AND num_fic=1 group by d.region, d.dep, g.etablissementsiege ORDER BY d.region, d.dep)t;";
        return $this->queryPDOResulset($sQuery);
    }

    public function getJsonStatCreation20Jours() {
        $sQuery = "select json_agg(t) from (select d.region, d.dep,  g.etablissementsiege, count(*) as nbOuverure
                  from poi.geosirene g INNER JOIN poi.dep d 
                  ON d.cp = substring(g.result_postcode, 1, 2)
                  where creation =true group by d.region, d.dep, g.etablissementsiege ORDER BY d.region, d.dep)t;";
        return $this->queryPDOResulset($sQuery);
    }

    public function getCountRegion20jours() {
        $sQuery = "select d.region, count(*) as nbOuverure
                  from poi.geosirene g INNER JOIN poi.dep d 
                  ON d.cp = substring(g.result_postcode, 1, 2)
                  where creation =true group by d.region ORDER BY d.region";
        return $this->queryPDOResulset($sQuery);
    }

    public function getCountRegionHier() {
        $sQuery = "select d.region, count(*) as nbOuverure
                  from poi.geosirene g INNER JOIN poi.dep d 
                  ON d.cp = substring(g.result_postcode, 1, 2)
                  where creation =true AND num_fic = 1 group by d.region ORDER BY d.region";
        return $this->queryPDOResulset($sQuery);
    }

    public function getJsonStatAPEParJour() {
        $sQuery = "select json_agg(t) from (select g.activiteprincipaleetablissement as name,c.intitulenaf, count(g.etablissementsiege) as etablissementsiege, count(*) as nbOuverure
                    from poi.geosirene g INNER JOIN poi.codeape c
                    ON g.activiteprincipaleetablissement = c.codenaf
                    where creation =true 
                    AND num_fic=1 
                    group by g.activiteprincipaleetablissement,c.intitulenaf, g.etablissementsiege order by nbOuverure DESc LIMIT 50)t;";
        return $this->queryPDOResulset($sQuery);
    }

    public function getJsonStatAPE20Jours() {
        $sQuery = "select json_agg(t) from (select g.activiteprincipaleetablissement as name,c.intitulenaf, count(g.etablissementsiege) as etablissementsiege, count(*) as nbOuverure
                    from poi.geosirene g INNER JOIN poi.codeape c
                    ON g.activiteprincipaleetablissement = c.codenaf
                    where creation =true                     
                    group by g.activiteprincipaleetablissement,c.intitulenaf, g.etablissementsiege order by nbOuverure DESc LIMIT 50)t;";
        return $this->queryPDOResulset($sQuery);
    }

    public function getHistoCreation() {
        $sQuery = "select d.region as country,  count(*) as population
                  from poi.geosirene g INNER JOIN poi.dep d 
                  ON d.cp = substring(g.result_postcode, 1, 2)
                  where creation =true AND num_fic=1 group by d.region  ORDER BY population DESC";
        return $this->queryPDOResulset($sQuery);
    }

    public function getHistoCreation20Jrs() {
        $sQuery = "select d.region as country,  count(*) as population
                  from poi.geosirene g INNER JOIN poi.dep d 
                  ON d.cp = substring(g.result_postcode, 1, 2)
                  where creation =true  group by d.region  ORDER BY population DESC";
        return $this->queryPDOResulset($sQuery);
    }

    public function getMapJour() {
        $sQuery = "SELECT codepostaletablissement, libellecommuneetablissement , latitude , longitude "
                . "FROM poi.geosirene WHERE num_fic = 1  AND codepaysetrangeretablissement is null";
        return $this->queryPDOResulset($sQuery);
    }

    public function getMap20Jours() {
        $sQuery = "SELECT codepostaletablissement, libellecommuneetablissement , latitude , longitude "
                . "FROM poi.geosirene WHERE codepaysetrangeretablissement is null";
        return $this->queryPDOResulset($sQuery);
    }

    public function sendMailIncident() {

        $sQuery = "select public.bdf_envoi_mail_erreur_maj();";
        $this->queryPDO($sQuery);
    }

    public function sendMailIncidentUL() {

        $sQuery = "select public.bdf_envoi_mail_erreur_maj_ul();";
        $this->queryPDO($sQuery);
    }

    public function sendMailIncidentBano() {

        $sQuery = "select public.bdf_envoi_mail_erreur_bano();";
        $this->queryPDO($sQuery);
    }

    public function sendMailMajUl() {

        $sQuery = "select public.bdf_envoi_mail_recap_maj_fin_ul();";
        $this->queryPDO($sQuery);
    }

    public function sendMailPagesJaunes() {

        $sQuery = "select public.bdf_envoi_mail_maj_pj();";
        $this->queryPDO($sQuery);
    }

    public function sendMailRecapMajgeosirene($inbCreation, $inbUdpate) {
        //Util::logMajGeosirene("Envoi mail récap  créations = " . $inbCreation . " Update = " . $inbUdpate);
        $sQuery = "SELECT public.bdf_envoi_mail_recap_maj(" . $inbCreation . ", " . $inbUdpate . ");";
        $this->queryPDO($sQuery);
    }

    public function getStockToGeocode($iOfsset) {
        $sQuery = "select * from poi.sirene_etablissement_n0
          where etatadministratifetablissement <> 'F'
          and (
          codecommuneetablissement like '06%'
          or codecommuneetablissement like '13%'
          or codecommuneetablissement like '2A%'
          or codecommuneetablissement like '2B%'
          or codecommuneetablissement like '26%'
          or codecommuneetablissement like '30%'
          or codecommuneetablissement like '34%'
          or codecommuneetablissement like '83%'
          or codecommuneetablissement like '84%') order by id limit 4000 offset " . $iOfsset;

        echo $sQuery . "\n";
        /* $sQuery = "select * from poi.sirene_etablissement_n0
          where etatadministratifetablissement <> 'F'
          and
          codecommuneetablissement like '84%'
          order by id limit 4000 offset " . $iOfsset; */
        return $this->queryPDOResulset($sQuery);
    }

    public function getStockToGeocodeSansOffset() {
        $sQuery = "select * from poi.sirene_etablissement_n0
        where etatadministratifetablissement <> 'F'
        and (
	codecommuneetablissement like '06%'
	or codecommuneetablissement like '13%'
	or codecommuneetablissement like '2A%'
	or codecommuneetablissement like '2B%'
	or codecommuneetablissement like '26%'
	or codecommuneetablissement like '30%'
	or codecommuneetablissement like '34%'
	or codecommuneetablissement like '83%'
	or codecommuneetablissement like '84%')";

        return $this->queryPDOResulset($sQuery);
    }

    public function getGeosireneBpmed($iOffset) {
        $sQuery = "SELECT siren FROM poi.geosirene_bpmed GROUP BY siren limit 5000 OFFSET " . $iOffset;
        return $this->queryPDOResulset($sQuery);
    }

    public function getBilanBySiren($sSiret) {

        $sSiren = substr($sSiret, 0, 9);
        $sQuery = "SELECT * FROM poi.siren_liasse WHERE liasse_code in ('FA', '209', '210') AND siren = '$sSiren'";

        echo $sQuery . "\n";
        return $this->queryPDOResulset($sQuery);
    }

    public function getEtablissementsOuvertsSansNom() {

        $this->getConnexion();
        $sSql = "SELECT siren FROM poi.geosirene WHERE enseigne1etablissement IS NULL AND denominationusuelleetablissement  IS NULL AND num_fic=1 ";
        $resultset = self::$oConnexion->prepare($sSql);
        $resultset->execute();
        return $resultset->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEtablissementsOuvertsSansNomNumFic($numfic) {

        $this->getConnexion();
        $sSql = "SELECT siren FROM poi.geosirene WHERE  enseigne1etablissement IS NULL OR denominationusuelleetablissement  IS NULL AND num_fic= " . $numfic;
        $resultset = self::$oConnexion->prepare($sSql);
        $resultset->execute();
        return $resultset->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUlCesseeBiSIren($sSiren) {
        $db = $this->getConnexion();
        $sSql = "SELECT siren, denominationunitelegale, nomunitelegale, prenom1unitelegale FROM poi.stock_ul_cessees WHERE siren = :siren ";
        $sql = $db->prepare($sSql);
        $sql->bindParam(':siren', $sSiren, PDO::PARAM_STR);
        $sql->execute();
        return $sql->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateDenominationEtab($sDenomination, $sSiren) {

        $db = $this->getConnexion();
        $sSql = "UPDATE poi.geosirene SET enseigne1etablissement=:enseigne1etablissement, denominationusuelleetablissement=:denominationusuelleetablissement WHERE siren =:siren ";
        $sql = $db->prepare($sSql);
        $sql->bindParam(':enseigne1etablissement', $sDenomination, PDO::PARAM_STR);
        $sql->bindParam(':denominationusuelleetablissement', $sDenomination, PDO::PARAM_STR);
        $sql->bindParam(':siren', $sSiren, PDO::PARAM_STR);
        $sql->execute();

        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            die($aErreur[2]);
        }
    }

    public function updateDenominationEtabStock($sDenomination, $sSiren) {

        $db = $this->getConnexion();
        $sSql = "UPDATE poi.sirene_etablissement_n0 SET enseigne1etablissement=:enseigne1etablissement, denominationusuelleetablissement=:enseigne1etablissement WHERE siren =:siren ";
        $sql = $db->prepare($sSql);
        $sql->bindParam(':enseigne1etablissement', $sDenomination, PDO::PARAM_STR);
        $sql->bindParam(':siren', $sSiren, PDO::PARAM_STR);
        $sql->execute();

        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            die($aErreur[2]);
        }
    }

    public function updateGeosireneBpmedBilan($aBilan, $sSiret) {

        $sQuery = "UPDATE poi.geosirene_bpmed SET "
                . "montant_france_n=:montant_france_n , "
                . "montant_export_n=:montant_export_n, "
                . "bilan_total_n=:bilan_total_n, "
                . "bilan_total_n_m1 =:bilan_total_n_m1 "
                . "WHERE siret =:siret";

        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);
        $sql->bindParam(':montant_france_n', $aBilan['m1'], PDO::PARAM_STR);
        $sql->bindParam(':montant_export_n', $aBilan['m2'], PDO::PARAM_STR);
        $sql->bindParam(':bilan_total_n', $aBilan['m3'], PDO::PARAM_STR);
        $sql->bindParam(':bilan_total_n_m1', $aBilan['m4'], PDO::PARAM_STR);
        $sql->bindParam(':siret', $sSiret, PDO::PARAM_STR);

        $sql->execute();

        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            echo $sql->queryString . "\n\n";
            die($aErreur[2]);
        }
    }

    public function updateGeosireneGeoc($aTabBano) {

        $sQuery = "UPDATE poi.geosirene
                        SET  latitude =:latitude, 
                        longitude =:longitude, 
                        dcomiris =:dcomiris, 
                        nom_iris =:nom_iris, 
                        depcom =:depcom, 
                        nom_commune =:nom_commune, 
                        code_departement =:code_departement, 
                        nom_departement =:nom_departement, 
                        the_geom_3857=:the_geom_3857, 
                        streetview=:streetview ,      
                        result_type =:result_type ,
                        codecommuneetablissement =:codecommuneetablissement 
                        WHERE 
                        siret =:siret;";
        $bDejaGeoc = 'TRUE';

        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);
        $sql->bindParam(':latitude', $aTabBano['latitude'], PDO::PARAM_STR);
        $sql->bindParam(':longitude', $aTabBano['longitude'], PDO::PARAM_STR);
        $sql->bindParam(':dcomiris', $aTabBano['dcomiris'], PDO::PARAM_STR);
        $sql->bindParam(':nom_iris', $aTabBano['nom_iris'], PDO::PARAM_STR);
        $sql->bindParam(':depcom', $aTabBano['depcom'], PDO::PARAM_STR);
        $sql->bindParam(':nom_commune', $aTabBano['nom_commune'], PDO::PARAM_STR);
        $sql->bindParam(':code_departement', $aTabBano['code_departement'], PDO::PARAM_STR);
        $sql->bindParam(':nom_departement', $aTabBano['nom_departement'], PDO::PARAM_STR);
        $sql->bindParam(':the_geom_3857', $aTabBano['the_geom_3857'], PDO::PARAM_STR);
        $sql->bindParam(':streetview', $aTabBano['streetview'], PDO::PARAM_STR);
        $sql->bindParam(':result_type', $aTabBano['poi_qualitegeorue'], PDO::PARAM_STR);
        $sql->bindParam(':codecommuneetablissement', $aTabBano['depcom'], PDO::PARAM_STR);

        $sql->bindParam(':siret', $aTabBano['siret'], PDO::PARAM_STR);

        $sql->execute();

        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            echo $sql->queryString . "\n\n";
            die($aErreur[2]);
        }
    }

    public function getLastDateTraiement() {

        $sQuery = "SELECT max(date_integration) FROM poi.geosirene";
        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);
        $sql->execute();
        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            echo $sql->queryString . "\n\n";
            die($aErreur[2]);
        } else {

            $aRes = $sql->fetchAll(PDO::FETCH_ASSOC);
            return $aRes[0]['max'];
        }
    }

    public function updateCreationGeosirene($sSiret) {

        $sCreation = 'TRUE';

        $db = $this->getConnexion();
        $sSql = "UPDATE poi.geosirene_stock SET creation =:creation  WHERE siret=:siret  ";
        $sql = $db->prepare($sSql);
        $sql->bindParam(':creation', $sCreation, PDO::PARAM_STR);
        $sql->bindParam(':siret', $sSiret, PDO::PARAM_STR);
        $sql->execute();

        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            die($aErreur[2]);
        }
    }

    public function getEtabPourPJ() {

        $sQuery = "select siret, adresse, denominationusuelleetablissement from poi.geosirene where num_fic =1 and denominationusuelleetablissement is not null";
        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);
        $sql->execute();
        $aRes = $sql->fetchAll(PDO::FETCH_ASSOC);
        return $aRes;
    }

    public function updateTelGeosirene($gid, $tel, $score_tel) {

        if (!$score_tel) {
            $score_tel = "100";
        }

        $sQuery = "UPDATE poi.geosirene SET tel = :tel , score_tel=:score_tel WHERE gid =:gid";
        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);
        $sql->bindParam(':tel', $tel, PDO::PARAM_STR);
        $sql->bindParam(':gid', $gid, PDO::PARAM_INT);
        $sql->bindParam(':score_tel', $score_tel, PDO::PARAM_INT);
        $sql->execute();

        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            die($aErreur[2]);
        }
    }

    public function updateTelStock($siret, $tel, $score_tel) {

        if (!$score_tel) {
            $score_tel = "100";
        }

        $sQuery = "UPDATE poi.sirene_etablissement_n0 SET tel = :tel , score_tel=:score_tel WHERE siret =:siret";
        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);
        $sql->bindParam(':tel', $tel, PDO::PARAM_STR);
        $sql->bindParam(':siret', $siret, PDO::PARAM_INT);
        $sql->bindParam(':score_tel', $score_tel, PDO::PARAM_INT);
        $sql->execute();

        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            die($aErreur[2]);
        }
    }

    public function insertTablePJ($siret, $tel, $score_tel) {

        if (!$score_tel) {
            $score_tel = "100";
        }
        $sQuery = "INSERT INTO poi.table_pj(siret, tel, score_tel)
	VALUES (:siret, :tel, :score_tel);";
        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);
        $sql->bindParam(':tel', $tel, PDO::PARAM_STR);
        $sql->bindParam(':siret', $siret, PDO::PARAM_INT);
        $sql->bindParam(':score_tel', $score_tel, PDO::PARAM_INT);
        $sql->execute();

        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            die($aErreur[2]);
        }
    }

    public function updateTelGeosireneSiret($siret, $tel, $score_tel) {

        $sQuery = "UPDATE poi.geosirene SET tel = :tel , score_tel=:score_tel WHERE siret =:siret";
        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);
        $sql->bindParam(':tel', $tel, PDO::PARAM_STR);
        $sql->bindParam(':siret', $siret, PDO::PARAM_INT);
        $sql->bindParam(':score_tel', $score_tel, PDO::PARAM_INT);
        $sql->execute();

        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            die($aErreur[2]);
        }
    }

    public function createTableComptables() {
        $sQuery = "CREATE TABLE IF NOT EXISTS copropriete.comptables_france ( `id` INT NULL , `siret` VARCHAR(50) NOT NULL , `tel` VARCHAR(50) NOT NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB;";

        $db = $this->getConnexionMysql();
        $sql = $db->prepare($sQuery);
        $sql->execute();

        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            die($aErreur[2]);
        } else {
            echo "TABLE CREE \n";
        }
    }

    public function insertComptables($siret, $tel) {

        $sQuery = "INSERT INTO copropriete.comptables_france(
	siret, tel)
	VALUES (:siret, :tel);";

        $db = $this->getConnexionMysql();
        $sql = $db->prepare($sQuery);
        $sql->bindParam(':tel', $tel, PDO::PARAM_STR);
        $sql->bindParam(':siret', $siret, PDO::PARAM_STR);
        $sql->execute();

        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            die($aErreur[2]);
        }
    }

    public function insertComptablesPg($siret, $tel) {

        $sQuery = "INSERT INTO poi.comptables_france(
	siret, tel)
	VALUES (:siret, :tel);";

        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);
        $sql->bindParam(':tel', $tel, PDO::PARAM_STR);
        $sql->bindParam(':siret', $siret, PDO::PARAM_STR);
        $sql->execute();

        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            die($aErreur[2]);
        }
    }

    public function searchComptables($siret) {

        $sQuery = "SELECT siret FROM copropriete.comptables_france WHERE siret = :siret;";

        $db = $this->getConnexionMysql();
        $sql = $db->prepare($sQuery);
        $sql->bindParam(':siret', $siret, PDO::PARAM_STR);
        $sql->execute();

        $aErreur = $sql->errorInfo();

        if (strlen($aErreur[2]) > 0) {
            die($aErreur[2]);
        } else {
            return $sql->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    public function sendMailMajNomEtablissement() {

        $sQuery = "SELECT * FROM public.bdf_envoi_mail_recap_maj_denomination()";

        $db = $this->getConnexionMysql();
        $sql = $db->prepare($sQuery);
        $sql->execute();

        $aErreur = $sql->errorInfo();

        if (strlen($aErreur[2]) > 0) {
            die($aErreur[2]);
        }
    }

    public function deleteEtabFermeInStock($siret) {

        $sQuery = "DELETE FROM poi.sirene_etablissement_n0 WHERE siret =:siret";
        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);
        $sql->bindParam(':siret', $siret, PDO::PARAM_STR);
        $sql->execute();

        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            die($aErreur[2]);
        }
    }

    public function cleanStockOuvertFerme() {

        $sQuery = "insert into poi.sirene_etablissement_ferme (gid, siren, nic, siret, 
            statutdiffusionetablissement, datecreationetablissement, trancheeffectifsetablissement, 
            anneeeffectifsetablissement, activiteprincipaleregistremetiersetablissement, datederniertraitementetablissement, 
            etablissementsiege, nombreperiodesetablissement, complementadresseetablissement, numerovoieetablissement, 
            indicerepetitionetablissement, typevoieetablissement, libellevoieetablissement, codepostaletablissement, libellecommuneetablissement, 
            libellecommuneetrangeretablissement, distributionspecialeetablissement, codecommuneetablissement, codecedexetablissement, 
            libellecedexetablissement, codepaysetrangeretablissement, libellepaysetrangeretablissement, complementadresse2etablissement, 
            numerovoie2etablissement, indicerepetition2etablissement, typevoie2etablissement, libellevoie2etablissement, codepostal2etablissement, 
            libellecommune2etablissement, libellecommuneetranger2etablissement, distributionspeciale2etablissement, codecommune2etablissement, 
            codecedex2etablissement, libellecedex2etablissement, codepaysetranger2etablissement, libellepaysetranger2etablissement, datedebut, 
            etatadministratifetablissement, enseigne1etablissement, enseigne2etablissement, enseigne3etablissement, denominationusuelleetablissement, 
            activiteprincipaleetablissement, nomenclatureactiviteprincipaleetablissement, caractereemployeuretablissement, 
            entree_champ_diffusion_commerciale, changement_activiteprincipaleetablissement, demenagement, changement_etat_administratif, 
            modification_tranche_nb_salaries, adresse, latitude, longitude, result_label, result_score, result_type, result_id, result_housenumber, 
            result_name, result_street, result_postcode, result_city, result_context, result_citycode, dcomiris, nom_iris, depcom, nom_commune, 
            code_departement, nom_departement, the_geom_3857, streetview, creation, deja_geoc, code_region, nom_region, section, baf, recup_geocube, 
            tel, score_tel, date_integration, denomination_geoscar)
            select gid, siren, nic, siret, statutdiffusionetablissement, datecreationetablissement, trancheeffectifsetablissement, 
            anneeeffectifsetablissement, activiteprincipaleregistremetiersetablissement, datederniertraitementetablissement, etablissementsiege, 
            nombreperiodesetablissement, complementadresseetablissement, numerovoieetablissement, indicerepetitionetablissement, 
            typevoieetablissement, libellevoieetablissement, codepostaletablissement, libellecommuneetablissement, libellecommuneetrangeretablissement, 
            distributionspecialeetablissement, codecommuneetablissement, codecedexetablissement, libellecedexetablissement, codepaysetrangeretablissement, 
            libellepaysetrangeretablissement, complementadresse2etablissement, numerovoie2etablissement, indicerepetition2etablissement, typevoie2etablissement, 
            libellevoie2etablissement, codepostal2etablissement, libellecommune2etablissement, libellecommuneetranger2etablissement, 
            distributionspeciale2etablissement, codecommune2etablissement, codecedex2etablissement, libellecedex2etablissement, codepaysetranger2etablissement, 
            libellepaysetranger2etablissement, datedebut, etatadministratifetablissement, enseigne1etablissement, enseigne2etablissement, enseigne3etablissement, 
            denominationusuelleetablissement, activiteprincipaleetablissement, nomenclatureactiviteprincipaleetablissement, caractereemployeuretablissement, 
            entree_champ_diffusion_commerciale, changement_activiteprincipaleetablissement, demenagement, changement_etat_administratif, 
            modification_tranche_nb_salaries, adresse, latitude, longitude, result_label, result_score, result_type, result_id, result_housenumber, 
            result_name, result_street, result_postcode, result_city, result_context, result_citycode, dcomiris, nom_iris, depcom, nom_commune, code_departement, 
            nom_departement, the_geom_3857, streetview, creation, deja_geoc, code_region, nom_region, section, baf, recup_geocube, 
            tel, score_tel, date_integration, 
            denomination_geoscar FROM poi.sirene_etablissement_n0
            WHERE etatadministratifetablissement = 'F';";
        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);
        $sql->execute();

        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            die($aErreur[2]);
        }
    }

    public function deleteEtabFermeInStockOuvert() {

        $sQuery = "DELETE FROM poi.sirene_etablissement_n0 WHERE etatadministratifetablissement = 'F'";
        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);
        $sql->execute();

        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            die($aErreur[2]);
        }
    }

//    public function deleteEtabFermeInStockProd($siret) {
//
//        $sQuery = "DELETE FROM poi.sirene_etablissement_n0 WHERE siret =:siret";
//        $db = $this->getConnexionProd();
//        $sql = $db->prepare($sQuery);
//        $sql->bindParam(':siret', $siret, PDO::PARAM_STR);
//        $sql->execute();
//
//        $aErreur = $sql->errorInfo();
//        if (strlen($aErreur[2]) > 0) {
//            die($aErreur[2]);
//        }
//    }

    public function updateStockGeoInsee($date_integration) {

        $sQueryGeosirene = "SELECT * FROM poi.geosirene WHERE date_integration=:date_integration";
        $db = $this->getConnexion();
        $sql = $db->prepare($sQueryGeosirene);
        $sql->bindParam(':date_integration', $date_integration, PDO::PARAM_STR);
        $sql->execute();

        $aErreur = $sql->errorInfo();

        if (strlen($aErreur[2]) > 0) {
            die($aErreur[2]);
        } else {
            $aRes = $sql->fetchAll(PDO::FETCH_ASSOC);
        }
//        echo "ici<br>";
//        echo"COUNT = ".count($aRes);


        for ($i = 0; $i < count($aRes); $i++) {


            $bEntreDiffComm = 'FALSE';
            $bAdresseChange = 'FALSE';
            $bChgtEtatAdmin = 'FALSE';
            $bChgtTrancheEff = 'FALSE';
            $bChgtActivitePrincipale = 'FALSE';
            $bCreation = 'FALSE';
            $bDejaGeoc = 'FALSE';
            $bBaf = 'FALSE';



            if ($aRes[$i]['entree_champ_diffusion_commerciale']) {
                $bEntreDiffComm = 'TRUE';
            }
            if ($aRes[$i]['changement_activiteprincipaleetablissement']) {
                $bChgtActivitePrincipale = 'TRUE';
            }
            if ($aRes[$i]['demenagement']) {
                $bAdresseChange = 'TRUE';
            }
            if ($aRes[$i]['changement_etat_administratif']) {
                $bChgtEtatAdmin = 'TRUE';
            }
            if ($aRes[$i]['modification_tranche_nb_salaries']) {
                $bChgtTrancheEff = 'TRUE';
            }
            if ($aRes[$i]['creation']) {
                $bCreation = 'TRUE';
            }
            /* if ($aRes[$i]['deja_geoc']) {
              $bDejaGeoc = 'TRUE';
              } */
            if ($aRes[$i]['baf']) {
                $bBaf = 'TRUE';
            }

            echo "*************UPDATE GEO INSEE " . $aRes[$i]['siret'] . "******************\n";

            $sQuery = "UPDATE poi.sirene_etablissement_n0 SET 
            entree_champ_diffusion_commerciale=:entree_champ_diffusion_commerciale,
            changement_activiteprincipaleetablissement=:changement_activiteprincipaleetablissement, 
            demenagement=:demenagement, 
            changement_etat_administratif=:changement_etat_administratif, 
            modification_tranche_nb_salaries=:modification_tranche_nb_salaries, 
            adresse=:adresse, 
            latitude=:latitude, 
            longitude=:longitude, 
            result_label=:result_label, 
            result_score=:result_score, 
            result_type=:result_type, 
            result_id=:result_id, 
            result_housenumber=:result_housenumber, 
            result_name=:result_name, 
            result_street=:result_street, 
            result_postcode=:result_postcode, 
            result_city=:result_city, 
            result_context=:result_context, 
            result_citycode=:result_citycode, 
            dcomiris=:dcomiris, 
            nom_iris=:nom_iris, 
            depcom=:depcom, 
            nom_commune=:nom_commune, 
            code_departement=:code_departement, 
            nom_departement=:nom_departement, 
            the_geom_3857=:the_geom_3857, 
            streetview=:streetview, 
            creation=:creation, 
            code_region=:code_region, 
            nom_region=:nom_region, 
            section=:section,  
            baf=:baf
            WHERE siret =:siret;";

            $sql = $db->prepare($sQuery);
            $sql->bindParam(':siret', $aRes[$i]['siret'], PDO::PARAM_STR);
            $sql->bindParam(':entree_champ_diffusion_commerciale', $bEntreDiffComm, PDO::PARAM_STR);
            $sql->bindParam(':changement_activiteprincipaleetablissement', $bChgtActivitePrincipale, PDO::PARAM_STR);
            $sql->bindParam(':demenagement', $bAdresseChange, PDO::PARAM_STR);
            $sql->bindParam(':changement_etat_administratif', $bChgtEtatAdmin, PDO::PARAM_STR);
            $sql->bindParam(':modification_tranche_nb_salaries', $bChgtTrancheEff, PDO::PARAM_STR);
            $sql->bindParam(':adresse', $aRes[$i]['adresse'], PDO::PARAM_STR);
            $sql->bindParam(':latitude', $aRes[$i]['latitude'], PDO::PARAM_STR);
            $sql->bindParam(':longitude', $aRes[$i]['longitude'], PDO::PARAM_STR);
            $sql->bindParam(':result_label', $aRes[$i]['result_label'], PDO::PARAM_STR);
            $sql->bindParam(':result_score', $aRes[$i]['result_score'], PDO::PARAM_STR);
            $sql->bindParam(':result_type', $aRes[$i]['result_type'], PDO::PARAM_STR);
            $sql->bindParam(':result_id', $aRes[$i]['result_id'], PDO::PARAM_STR);
            $sql->bindParam(':result_housenumber', $aRes[$i]['result_housenumber'], PDO::PARAM_STR);
            $sql->bindParam(':result_name', $aRes[$i]['result_name'], PDO::PARAM_STR);
            $sql->bindParam(':result_street', $aRes[$i]['result_street'], PDO::PARAM_STR);
            $sql->bindParam(':result_postcode', $aRes[$i]['result_postcode'], PDO::PARAM_STR);
            $sql->bindParam(':result_city', $aRes[$i]['result_city'], PDO::PARAM_STR);
            $sql->bindParam(':result_context', $aRes[$i]['result_context'], PDO::PARAM_STR);
            $sql->bindParam(':result_citycode', $aRes[$i]['result_citycode'], PDO::PARAM_STR);
            $sql->bindParam(':dcomiris', $aRes[$i]['dcomiris'], PDO::PARAM_STR);
            $sql->bindParam(':nom_iris', $aRes[$i]['nom_iris'], PDO::PARAM_STR);
            $sql->bindParam(':depcom', $aRes[$i]['depcom'], PDO::PARAM_STR);
            $sql->bindParam(':nom_commune', $aRes[$i]['nom_commune'], PDO::PARAM_STR);
            $sql->bindParam(':code_departement', $aRes[$i]['code_departement'], PDO::PARAM_STR);
            $sql->bindParam(':nom_departement', $aRes[$i]['nom_departement'], PDO::PARAM_STR);
            $sql->bindParam(':the_geom_3857', $aRes[$i]['the_geom_3857'], PDO::PARAM_STR);
            $sql->bindParam(':streetview', $aRes[$i]['streetview'], PDO::PARAM_STR);
            $sql->bindParam(':creation', $bCreation, PDO::PARAM_STR);
            $sql->bindParam(':code_region', $aRes[$i]['code_region'], PDO::PARAM_STR);
            $sql->bindParam(':nom_region', $aRes[$i]['nom_region'], PDO::PARAM_STR);
            $sql->bindParam(':section', $aRes[$i]['section'], PDO::PARAM_STR);
            $sql->bindParam(':baf', $bBaf, PDO::PARAM_STR);
            $sql->execute();
//
            $aErreur = $sql->errorInfo();
            if (strlen($aErreur[2]) > 0) {
                echo $sQuery . "\n\n";
                $this->sendMailIncidentQuery($sQuery, $aErreur[2]);
                //die($aErreur[2]);
            }
        }
    }

    public function updateStockFermeGeoInsee($date_integration) {

        $sQueryGeosirene = "SELECT * FROM poi.geosirene WHERE date_integration=:date_integration";
        $db = $this->getConnexion();
        $sql = $db->prepare($sQueryGeosirene);
        $sql->bindParam(':date_integration', $date_integration, PDO::PARAM_STR);
        $sql->execute();

        $aErreur = $sql->errorInfo();

        if (strlen($aErreur[2]) > 0) {
            die($aErreur[2]);
        } else {
            $aRes = $sql->fetchAll(PDO::FETCH_ASSOC);
        }

        for ($i = 0; $i < count($aRes); $i++) {


            $bEntreDiffComm = 'FALSE';
            $bAdresseChange = 'FALSE';
            $bChgtEtatAdmin = 'FALSE';
            $bChgtTrancheEff = 'FALSE';
            $bChgtActivitePrincipale = 'FALSE';
            $bCreation = 'FALSE';
            $bDejaGeoc = 'FALSE';
            $bBaf = 'FALSE';



            if ($aRes[$i]['entree_champ_diffusion_commerciale']) {
                $bEntreDiffComm = 'TRUE';
            }
            if ($aRes[$i]['changement_activiteprincipaleetablissement']) {
                $bChgtActivitePrincipale = 'TRUE';
            }
            if ($aRes[$i]['demenagement']) {
                $bAdresseChange = 'TRUE';
            }
            if ($aRes[$i]['changement_etat_administratif']) {
                $bChgtEtatAdmin = 'TRUE';
            }
            if ($aRes[$i]['modification_tranche_nb_salaries']) {
                $bChgtTrancheEff = 'TRUE';
            }
            if ($aRes[$i]['creation']) {
                $bCreation = 'TRUE';
            }
            /* if ($aRes[$i]['deja_geoc']) {
              $bDejaGeoc = 'TRUE';
              } */
            if ($aRes[$i]['baf']) {
                $bBaf = 'TRUE';
            }

            echo "*************UPDATE GEO INSEE FERMES " . $aRes[$i]['siret'] . "******************\n";

            $sQuery = "UPDATE poi.sirene_etablissement_ferme SET 
            entree_champ_diffusion_commerciale=:entree_champ_diffusion_commerciale,
            changement_activiteprincipaleetablissement=:changement_activiteprincipaleetablissement, 
            demenagement=:demenagement, 
            changement_etat_administratif=:changement_etat_administratif, 
            modification_tranche_nb_salaries=:modification_tranche_nb_salaries, 
            adresse=:adresse, 
            latitude=:latitude, 
            longitude=:longitude, 
            result_label=:result_label, 
            result_score=:result_score, 
            result_type=:result_type, 
            result_id=:result_id, 
            result_housenumber=:result_housenumber, 
            result_name=:result_name, 
            result_street=:result_street, 
            result_postcode=:result_postcode, 
            result_city=:result_city, 
            result_context=:result_context, 
            result_citycode=:result_citycode, 
            dcomiris=:dcomiris, 
            nom_iris=:nom_iris, 
            depcom=:depcom, 
            nom_commune=:nom_commune, 
            code_departement=:code_departement, 
            nom_departement=:nom_departement, 
            the_geom_3857=:the_geom_3857, 
            streetview=:streetview, 
            creation=:creation, 
            code_region=:code_region, 
            nom_region=:nom_region, 
            section=:section,  
            baf=:baf
            WHERE siret =:siret;";

            $sql = $db->prepare($sQuery);
            $sql->bindParam(':siret', $aRes[$i]['siret'], PDO::PARAM_STR);
            $sql->bindParam(':entree_champ_diffusion_commerciale', $bEntreDiffComm, PDO::PARAM_STR);
            $sql->bindParam(':changement_activiteprincipaleetablissement', $bChgtActivitePrincipale, PDO::PARAM_STR);
            $sql->bindParam(':demenagement', $bAdresseChange, PDO::PARAM_STR);
            $sql->bindParam(':changement_etat_administratif', $bChgtEtatAdmin, PDO::PARAM_STR);
            $sql->bindParam(':modification_tranche_nb_salaries', $bChgtTrancheEff, PDO::PARAM_STR);
            $sql->bindParam(':adresse', $aRes[$i]['adresse'], PDO::PARAM_STR);
            $sql->bindParam(':latitude', $aRes[$i]['latitude'], PDO::PARAM_STR);
            $sql->bindParam(':longitude', $aRes[$i]['longitude'], PDO::PARAM_STR);
            $sql->bindParam(':result_label', $aRes[$i]['result_label'], PDO::PARAM_STR);
            $sql->bindParam(':result_score', $aRes[$i]['result_score'], PDO::PARAM_STR);
            $sql->bindParam(':result_type', $aRes[$i]['result_type'], PDO::PARAM_STR);
            $sql->bindParam(':result_id', $aRes[$i]['result_id'], PDO::PARAM_STR);
            $sql->bindParam(':result_housenumber', $aRes[$i]['result_housenumber'], PDO::PARAM_STR);
            $sql->bindParam(':result_name', $aRes[$i]['result_name'], PDO::PARAM_STR);
            $sql->bindParam(':result_street', $aRes[$i]['result_street'], PDO::PARAM_STR);
            $sql->bindParam(':result_postcode', $aRes[$i]['result_postcode'], PDO::PARAM_STR);
            $sql->bindParam(':result_city', $aRes[$i]['result_city'], PDO::PARAM_STR);
            $sql->bindParam(':result_context', $aRes[$i]['result_context'], PDO::PARAM_STR);
            $sql->bindParam(':result_citycode', $aRes[$i]['result_citycode'], PDO::PARAM_STR);
            $sql->bindParam(':dcomiris', $aRes[$i]['dcomiris'], PDO::PARAM_STR);
            $sql->bindParam(':nom_iris', $aRes[$i]['nom_iris'], PDO::PARAM_STR);
            $sql->bindParam(':depcom', $aRes[$i]['depcom'], PDO::PARAM_STR);
            $sql->bindParam(':nom_commune', $aRes[$i]['nom_commune'], PDO::PARAM_STR);
            $sql->bindParam(':code_departement', $aRes[$i]['code_departement'], PDO::PARAM_STR);
            $sql->bindParam(':nom_departement', $aRes[$i]['nom_departement'], PDO::PARAM_STR);
            $sql->bindParam(':the_geom_3857', $aRes[$i]['the_geom_3857'], PDO::PARAM_STR);
            $sql->bindParam(':streetview', $aRes[$i]['streetview'], PDO::PARAM_STR);
            $sql->bindParam(':creation', $bCreation, PDO::PARAM_STR);
            $sql->bindParam(':code_region', $aRes[$i]['code_region'], PDO::PARAM_STR);
            $sql->bindParam(':nom_region', $aRes[$i]['nom_region'], PDO::PARAM_STR);
            $sql->bindParam(':section', $aRes[$i]['section'], PDO::PARAM_STR);
            $sql->bindParam(':baf', $bBaf, PDO::PARAM_STR);
            $sql->execute();
//
            $aErreur = $sql->errorInfo();
            if (strlen($aErreur[2]) > 0) {
                echo $sQuery . "\n\n";
                $this->sendMailIncidentQuery($sQuery, $aErreur[2]);
                //die($aErreur[2]);
            }
        }
    }

//    public function updateStockFermeGeoInseeProd() {
//
//        $sQueryGeosirene = "SELECT * FROM poi.geosirene WHERE num_fic = 1";
//        $db = $this->getConnexionProd();
//        $sql = $db->prepare($sQueryGeosirene);
//        $sql->execute();
//
//        $aErreur = $sql->errorInfo();
//
//        if (strlen($aErreur[2]) > 0) {
//            die($aErreur[2]);
//        } else {
//            $aRes = $sql->fetchAll(PDO::FETCH_ASSOC);
//        }
//
//        for ($i = 0; $i < count($aRes); $i++) {
//
//
//            $bEntreDiffComm = 'FALSE';
//            $bAdresseChange = 'FALSE';
//            $bChgtEtatAdmin = 'FALSE';
//            $bChgtTrancheEff = 'FALSE';
//            $bChgtActivitePrincipale = 'FALSE';
//            $bCreation = 'FALSE';
//            $bDejaGeoc = 'FALSE';
//            $bBaf = 'FALSE';
//
//
//
//            if ($aRes[$i]['entree_champ_diffusion_commerciale']) {
//                $bEntreDiffComm = 'TRUE';
//            }
//            if ($aRes[$i]['changement_activiteprincipaleetablissement']) {
//                $bChgtActivitePrincipale = 'TRUE';
//            }
//            if ($aRes[$i]['demenagement']) {
//                $bAdresseChange = 'TRUE';
//            }
//            if ($aRes[$i]['changement_etat_administratif']) {
//                $bChgtEtatAdmin = 'TRUE';
//            }
//            if ($aRes[$i]['modification_tranche_nb_salaries']) {
//                $bChgtTrancheEff = 'TRUE';
//            }
//            if ($aRes[$i]['creation']) {
//                $bCreation = 'TRUE';
//            }
//            /* if ($aRes[$i]['deja_geoc']) {
//              $bDejaGeoc = 'TRUE';
//              } */
//            if ($aRes[$i]['baf']) {
//                $bBaf = 'TRUE';
//            }
//
//            echo "*************UPDATE GEO INSEE " . $aRes[$i]['siret'] . "******************\n";
//
//            $sQuery = "UPDATE poi.sirene_etablissement_ferme SET 
//            entree_champ_diffusion_commerciale=:entree_champ_diffusion_commerciale,
//            changement_activiteprincipaleetablissement=:changement_activiteprincipaleetablissement, 
//            demenagement=:demenagement, 
//            changement_etat_administratif=:changement_etat_administratif, 
//            modification_tranche_nb_salaries=:modification_tranche_nb_salaries, 
//            adresse=:adresse, 
//            latitude=:latitude, 
//            longitude=:longitude, 
//            result_label=:result_label, 
//            result_score=:result_score, 
//            result_type=:result_type, 
//            result_id=:result_id, 
//            result_housenumber=:result_housenumber, 
//            result_name=:result_name, 
//            result_street=:result_street, 
//            result_postcode=:result_postcode, 
//            result_city=:result_city, 
//            result_context=:result_context, 
//            result_citycode=:result_citycode, 
//            dcomiris=:dcomiris, 
//            nom_iris=:nom_iris, 
//            depcom=:depcom, 
//            nom_commune=:nom_commune, 
//            code_departement=:code_departement, 
//            nom_departement=:nom_departement, 
//            the_geom_3857=:the_geom_3857, 
//            streetview=:streetview, 
//            creation=:creation, 
//            code_region=:code_region, 
//            nom_region=:nom_region, 
//            section=:section,  
//            baf=:baf
//            WHERE siret =:siret;";
//
//            $sql = $db->prepare($sQuery);
//            $sql->bindParam(':siret', $aRes[$i]['siret'], PDO::PARAM_STR);
//            $sql->bindParam(':entree_champ_diffusion_commerciale', $bEntreDiffComm, PDO::PARAM_STR);
//            $sql->bindParam(':changement_activiteprincipaleetablissement', $bChgtActivitePrincipale, PDO::PARAM_STR);
//            $sql->bindParam(':demenagement', $bAdresseChange, PDO::PARAM_STR);
//            $sql->bindParam(':changement_etat_administratif', $bChgtEtatAdmin, PDO::PARAM_STR);
//            $sql->bindParam(':modification_tranche_nb_salaries', $bChgtTrancheEff, PDO::PARAM_STR);
//            $sql->bindParam(':adresse', $aRes[$i]['adresse'], PDO::PARAM_STR);
//            $sql->bindParam(':latitude', $aRes[$i]['latitude'], PDO::PARAM_STR);
//            $sql->bindParam(':longitude', $aRes[$i]['longitude'], PDO::PARAM_STR);
//            $sql->bindParam(':result_label', $aRes[$i]['result_label'], PDO::PARAM_STR);
//            $sql->bindParam(':result_score', $aRes[$i]['result_score'], PDO::PARAM_STR);
//            $sql->bindParam(':result_type', $aRes[$i]['result_type'], PDO::PARAM_STR);
//            $sql->bindParam(':result_id', $aRes[$i]['result_id'], PDO::PARAM_STR);
//            $sql->bindParam(':result_housenumber', $aRes[$i]['result_housenumber'], PDO::PARAM_STR);
//            $sql->bindParam(':result_name', $aRes[$i]['result_name'], PDO::PARAM_STR);
//            $sql->bindParam(':result_street', $aRes[$i]['result_street'], PDO::PARAM_STR);
//            $sql->bindParam(':result_postcode', $aRes[$i]['result_postcode'], PDO::PARAM_STR);
//            $sql->bindParam(':result_city', $aRes[$i]['result_city'], PDO::PARAM_STR);
//            $sql->bindParam(':result_context', $aRes[$i]['result_context'], PDO::PARAM_STR);
//            $sql->bindParam(':result_citycode', $aRes[$i]['result_citycode'], PDO::PARAM_STR);
//            $sql->bindParam(':dcomiris', $aRes[$i]['dcomiris'], PDO::PARAM_STR);
//            $sql->bindParam(':nom_iris', $aRes[$i]['nom_iris'], PDO::PARAM_STR);
//            $sql->bindParam(':depcom', $aRes[$i]['depcom'], PDO::PARAM_STR);
//            $sql->bindParam(':nom_commune', $aRes[$i]['nom_commune'], PDO::PARAM_STR);
//            $sql->bindParam(':code_departement', $aRes[$i]['code_departement'], PDO::PARAM_STR);
//            $sql->bindParam(':nom_departement', $aRes[$i]['nom_departement'], PDO::PARAM_STR);
//            $sql->bindParam(':the_geom_3857', $aRes[$i]['the_geom_3857'], PDO::PARAM_STR);
//            $sql->bindParam(':streetview', $aRes[$i]['streetview'], PDO::PARAM_STR);
//            $sql->bindParam(':creation', $bCreation, PDO::PARAM_STR);
//            $sql->bindParam(':code_region', $aRes[$i]['code_region'], PDO::PARAM_STR);
//            $sql->bindParam(':nom_region', $aRes[$i]['nom_region'], PDO::PARAM_STR);
//            $sql->bindParam(':section', $aRes[$i]['section'], PDO::PARAM_STR);
//            $sql->bindParam(':baf', $bBaf, PDO::PARAM_STR);
//            $sql->execute();
//
//            $aErreur = $sql->errorInfo();
//            if (strlen($aErreur[2]) > 0) {
//                die($aErreur[2]);
//            }
//        }
//    }
//    public function updateStockGeoInseeProd() {
//
//        $sQueryGeosirene = "SELECT * FROM poi.geosirene WHERE num_fic = 1";
//        $db = $this->getConnexionProd();
//        $sql = $db->prepare($sQueryGeosirene);
//        $sql->execute();
//
//        $aErreur = $sql->errorInfo();
//
//        if (strlen($aErreur[2]) > 0) {
//            die($aErreur[2]);
//        } else {
//            $aRes = $sql->fetchAll(PDO::FETCH_ASSOC);
//        }
//
//        for ($i = 0; $i < count($aRes); $i++) {
//
//
//            $bEntreDiffComm = 'FALSE';
//            $bAdresseChange = 'FALSE';
//            $bChgtEtatAdmin = 'FALSE';
//            $bChgtTrancheEff = 'FALSE';
//            $bChgtActivitePrincipale = 'FALSE';
//            $bCreation = 'FALSE';
//            $bDejaGeoc = 'FALSE';
//            $bBaf = 'FALSE';
//
//
//
//            if ($aRes[$i]['entree_champ_diffusion_commerciale']) {
//                $bEntreDiffComm = 'TRUE';
//            }
//            if ($aRes[$i]['changement_activiteprincipaleetablissement']) {
//                $bChgtActivitePrincipale = 'TRUE';
//            }
//            if ($aRes[$i]['demenagement']) {
//                $bAdresseChange = 'TRUE';
//            }
//            if ($aRes[$i]['changement_etat_administratif']) {
//                $bChgtEtatAdmin = 'TRUE';
//            }
//            if ($aRes[$i]['modification_tranche_nb_salaries']) {
//                $bChgtTrancheEff = 'TRUE';
//            }
//            if ($aRes[$i]['creation']) {
//                $bCreation = 'TRUE';
//            }
//            /* if ($aRes[$i]['deja_geoc']) {
//              $bDejaGeoc = 'TRUE';
//              } */
//            if ($aRes[$i]['baf']) {
//                $bBaf = 'TRUE';
//            }
//
//            echo "*************UPDATE GEO INSEE " . $aRes[$i]['siret'] . "******************\n";
//
//            $sQuery = "UPDATE poi.sirene_etablissement_n0 SET 
//            entree_champ_diffusion_commerciale=:entree_champ_diffusion_commerciale,
//            changement_activiteprincipaleetablissement=:changement_activiteprincipaleetablissement, 
//            demenagement=:demenagement, 
//            changement_etat_administratif=:changement_etat_administratif, 
//            modification_tranche_nb_salaries=:modification_tranche_nb_salaries, 
//            adresse=:adresse, 
//            latitude=:latitude, 
//            longitude=:longitude, 
//            result_label=:result_label, 
//            result_score=:result_score, 
//            result_type=:result_type, 
//            result_id=:result_id, 
//            result_housenumber=:result_housenumber, 
//            result_name=:result_name, 
//            result_street=:result_street, 
//            result_postcode=:result_postcode, 
//            result_city=:result_city, 
//            result_context=:result_context, 
//            result_citycode=:result_citycode, 
//            dcomiris=:dcomiris, 
//            nom_iris=:nom_iris, 
//            depcom=:depcom, 
//            nom_commune=:nom_commune, 
//            code_departement=:code_departement, 
//            nom_departement=:nom_departement, 
//            the_geom_3857=:the_geom_3857, 
//            streetview=:streetview, 
//            creation=:creation, 
//            code_region=:code_region, 
//            nom_region=:nom_region, 
//            section=:section,  
//            baf=:baf
//            WHERE siret =:siret;";
//
//            $sql = $db->prepare($sQuery);
//            $sql->bindParam(':siret', $aRes[$i]['siret'], PDO::PARAM_STR);
//            $sql->bindParam(':entree_champ_diffusion_commerciale', $bEntreDiffComm, PDO::PARAM_STR);
//            $sql->bindParam(':changement_activiteprincipaleetablissement', $bChgtActivitePrincipale, PDO::PARAM_STR);
//            $sql->bindParam(':demenagement', $bAdresseChange, PDO::PARAM_STR);
//            $sql->bindParam(':changement_etat_administratif', $bChgtEtatAdmin, PDO::PARAM_STR);
//            $sql->bindParam(':modification_tranche_nb_salaries', $bChgtTrancheEff, PDO::PARAM_STR);
//            $sql->bindParam(':adresse', $aRes[$i]['adresse'], PDO::PARAM_STR);
//            $sql->bindParam(':latitude', $aRes[$i]['latitude'], PDO::PARAM_STR);
//            $sql->bindParam(':longitude', $aRes[$i]['longitude'], PDO::PARAM_STR);
//            $sql->bindParam(':result_label', $aRes[$i]['result_label'], PDO::PARAM_STR);
//            $sql->bindParam(':result_score', $aRes[$i]['result_score'], PDO::PARAM_STR);
//            $sql->bindParam(':result_type', $aRes[$i]['result_type'], PDO::PARAM_STR);
//            $sql->bindParam(':result_id', $aRes[$i]['result_id'], PDO::PARAM_STR);
//            $sql->bindParam(':result_housenumber', $aRes[$i]['result_housenumber'], PDO::PARAM_STR);
//            $sql->bindParam(':result_name', $aRes[$i]['result_name'], PDO::PARAM_STR);
//            $sql->bindParam(':result_street', $aRes[$i]['result_street'], PDO::PARAM_STR);
//            $sql->bindParam(':result_postcode', $aRes[$i]['result_postcode'], PDO::PARAM_STR);
//            $sql->bindParam(':result_city', $aRes[$i]['result_city'], PDO::PARAM_STR);
//            $sql->bindParam(':result_context', $aRes[$i]['result_context'], PDO::PARAM_STR);
//            $sql->bindParam(':result_citycode', $aRes[$i]['result_citycode'], PDO::PARAM_STR);
//            $sql->bindParam(':dcomiris', $aRes[$i]['dcomiris'], PDO::PARAM_STR);
//            $sql->bindParam(':nom_iris', $aRes[$i]['nom_iris'], PDO::PARAM_STR);
//            $sql->bindParam(':depcom', $aRes[$i]['depcom'], PDO::PARAM_STR);
//            $sql->bindParam(':nom_commune', $aRes[$i]['nom_commune'], PDO::PARAM_STR);
//            $sql->bindParam(':code_departement', $aRes[$i]['code_departement'], PDO::PARAM_STR);
//            $sql->bindParam(':nom_departement', $aRes[$i]['nom_departement'], PDO::PARAM_STR);
//            $sql->bindParam(':the_geom_3857', $aRes[$i]['the_geom_3857'], PDO::PARAM_STR);
//            $sql->bindParam(':streetview', $aRes[$i]['streetview'], PDO::PARAM_STR);
//            $sql->bindParam(':creation', $bCreation, PDO::PARAM_STR);
//            $sql->bindParam(':code_region', $aRes[$i]['code_region'], PDO::PARAM_STR);
//            $sql->bindParam(':nom_region', $aRes[$i]['nom_region'], PDO::PARAM_STR);
//            $sql->bindParam(':section', $aRes[$i]['section'], PDO::PARAM_STR);
//            $sql->bindParam(':baf', $bBaf, PDO::PARAM_STR);
//            $sql->execute();
//
//            $aErreur = $sql->errorInfo();
//            if (strlen($aErreur[2]) > 0) {
//                die($aErreur[2]);
//            }
//        }
//    }


    public function getGeosireneNonGeoc() {

        $sQuery = "select * from  poi.geosirene where latitude is null";
        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);
        $sql->execute();
        $aErreur = $sql->errorInfo();

        if (strlen($aErreur[2]) > 0) {
            die($aErreur[2]);
        } else {
            return $sql->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    public function getGeosireneNumFic1() {

        //if ($gid != 0) {

        $sQuery = "SELECT * FROM poi.geosirene WHERE num_fic = 1 AND tel IS NULL AND baf IS true AND creation IS true "
                . "AND denomination_geoscar IS NOT null AND adresse IS NOT null ORDER BY gid";

        echo $sQuery . "\n";
        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);
        //$sql->bindParam(':gid', $gid, PDO::PARAM_INT);
        $sql->execute();
        $aErreur = $sql->errorInfo();

        if (strlen($aErreur[2]) > 0) {
            die($aErreur[2]);
        } else {
            return $sql->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    public function getGeosireneNumFic2() {

        //if ($gid != 0) {

        $sQuery = "SELECT * FROM poi.geosirene WHERE num_fic = 2 AND tel IS NULL AND baf IS true AND creation IS true "
                . "AND denomination_geoscar IS NOT null AND adresse IS NOT null ORDER BY gid";

        echo $sQuery . "\n";
        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);
        //$sql->bindParam(':gid', $gid, PDO::PARAM_INT);
        $sql->execute();
        $aErreur = $sql->errorInfo();

        if (strlen($aErreur[2]) > 0) {
            die($aErreur[2]);
        } else {
            return $sql->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    public function getGeosireneNumFic1et2() {

        //if ($gid != 0) {

        $sQuery = "SELECT * FROM poi.geosirene WHERE num_fic IN (1,2) AND tel IS NULL AND baf IS true AND creation IS true "
                . "AND denomination_geoscar IS NOT null AND adresse IS NOT null ORDER BY gid";

        echo $sQuery . "\n";
        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);
        //$sql->bindParam(':gid', $gid, PDO::PARAM_INT);
        $sql->execute();
        $aErreur = $sql->errorInfo();

        if (strlen($aErreur[2]) > 0) {
            die($aErreur[2]);
        } else {
            return $sql->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    public function getStockByNaf($naf) {

        $sQuery = "SELECT * FROM poi.sirene_etablissement_n0 "
                . "WHERE activiteprincipaleetablissement =:activiteprincipaleetablissement";
        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);
        $sql->bindParam(':activiteprincipaleetablissement', $naf, PDO::PARAM_INT);
        $sql->execute();
        return $sql->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getGeosireneAvecTel() {

        $sQuery = "SELECT siret, tel, score_tel FROM poi.geosirene WHERE tel IS NOT NULL";
        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);
        $sql->execute();
        $aErreur = $sql->errorInfo();

        if (strlen($aErreur[2]) > 0) {
            die($aErreur[2]);
        } else {
            return $sql->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    public function ajoutFichierPagesJaunes($siret, $tel, $score) {
        echo "*******************************ajoutFichierPagesJaunes \n";


        // SI FICHIER N'EXISTE PAS ON CREE LE FICHIER ET LES ENTETES
        //if (!file_exists(FILE_RESULT_POUR_PJ)) {
        //$fp = fopen(FILE_RESULT_POUR_PJ, 'a+');
        //fputcsv($fp, $siret.";". $tel.";". $score, ';', "*");
        //} else {

        $aArrayValeurs = array($siret, $tel, $score);
        //$sField = $siret.";". $tel.";". $score;
        echo $tel . "\n";
        $fp = fopen(FILE_RESULT_POUR_PJ, 'a+');
        if ($fp) {
            fputcsv($fp, $aArrayValeurs, "*");
        } else {
            die("impossible d'ouvrir le fichier");
        }

        //}
        fclose($fp);
        // REMPLACEMENT DES * MISES EN ENCLOSURE OBLIGATOIRE DE fputcsv
        $replace = str_replace("*", ';', file_get_contents(FILE_RESULT_POUR_PJ));
        //$replace = str_replace('"', '', file_get_contents(FILE_RESULT_POUR_PJ));
        file_put_contents(FILE_RESULT_POUR_PJ, $replace);
    }

    public function sendFileFTPPagesJaunes() {

        $conn_id = ftp_ssl_connect(ADR_FTP);

        $login_result = ftp_login($conn_id, 'ftp_mada', 'Mada#44');
        if (!$login_result) {
            // PHP aura déjà soulevé un message de niveau E_WARNING dans ce cas
            die("can't login");
        }

        $sDate = strftime("%Y-%m-%d", mktime(0, 0, 0, date('m'), date('d'), date('Y')));
        if ($login_result) {
            //Set passive mode
            ftp_pasv($conn_id, true);
            // Transfer file
            $transfer_result = ftp_put($conn_id, "PJ/sirene_pour_pj_$sDate.csv", FILE_RESULT_POUR_PJ, FTP_BINARY);

            //Verify if transfer was successfully made
            if ($transfer_result) {
                echo "Success";
            } else {
                echo "An error occured";
            }
        }
        ftp_close($conn_id);
        echo "*************************************FIN TRAITEMENT \n";
    }

    public function envoiMailFinTraitement() {

        $sQuery = "SELECT public.bdf_envoi_mail_recap_maj_fin_insee()";
        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);
        $sql->execute();
    }
    
    
    
    public function dropTableStockOuvertSansGeo()
    {
        $sQuery = "DROP TABLE IF EXISTS poi.sirene_etablissement_n0_sans_geo ";
        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);
        $sql->execute();
    }
    
    
    public function dropTableStockFermeSansGeo()
    {
        $sQuery = "DROP TABLE IF EXISTS poi.sirene_etab_ferme_sans_geo ";
        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);
        $sql->execute();
    }
    
    public function createTableStockOuvertSansGeo()
    {
        $sQuery = "create table poi.sirene_etablissement_n0_sans_geo as select
	gid, siren, nic, siret, statutdiffusionetablissement, datecreationetablissement, 
    trancheeffectifsetablissement, anneeeffectifsetablissement, activiteprincipaleregistremetiersetablissement, 
    datederniertraitementetablissement, etablissementsiege, nombreperiodesetablissement, 
    complementadresseetablissement, numerovoieetablissement, indicerepetitionetablissement, 
    typevoieetablissement, libellevoieetablissement, codepostaletablissement, libellecommuneetablissement, 
    libellecommuneetrangeretablissement, distributionspecialeetablissement, codecommuneetablissement, codecedexetablissement, libellecedexetablissement, 
    codepaysetrangeretablissement, libellepaysetrangeretablissement, complementadresse2etablissement, numerovoie2etablissement, indicerepetition2etablissement, 
    typevoie2etablissement, libellevoie2etablissement, codepostal2etablissement, libellecommune2etablissement, libellecommuneetranger2etablissement, 
    distributionspeciale2etablissement, codecommune2etablissement, codecedex2etablissement, libellecedex2etablissement, codepaysetranger2etablissement, 
    libellepaysetranger2etablissement, datedebut, etatadministratifetablissement, enseigne1etablissement, enseigne2etablissement, enseigne3etablissement, 
    denominationusuelleetablissement, activiteprincipaleetablissement, nomenclatureactiviteprincipaleetablissement, caractereemployeuretablissement, 
    entree_champ_diffusion_commerciale, changement_activiteprincipaleetablissement, demenagement, changement_etat_administratif, modification_tranche_nb_salaries,
    adresse, latitude, longitude, result_label, result_score, result_type, result_id, result_housenumber, result_name, result_street, 
    result_postcode, result_city, result_context, result_citycode, dcomiris, nom_iris, depcom, nom_commune, code_departement, nom_departement, 
    streetview, creation, deja_geoc, code_region, nom_region, section, baf, recup_geocube, tel, score_tel, date_integration, denomination_geoscar, cle_adresse
    from poi.sirene_etablissement_n0 ";
        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);
        $sql->execute();
    }
    
    
    public function createTableStockFermeSansGeo()
    {
        $sQuery = "create table poi.sirene_etab_ferme_sans_geo as select
	gid, siren, nic, siret, statutdiffusionetablissement, datecreationetablissement, 
    trancheeffectifsetablissement, anneeeffectifsetablissement, activiteprincipaleregistremetiersetablissement, 
    datederniertraitementetablissement, etablissementsiege, nombreperiodesetablissement, 
    complementadresseetablissement, numerovoieetablissement, indicerepetitionetablissement, 
    typevoieetablissement, libellevoieetablissement, codepostaletablissement, libellecommuneetablissement, 
    libellecommuneetrangeretablissement, distributionspecialeetablissement, codecommuneetablissement, codecedexetablissement, libellecedexetablissement, 
    codepaysetrangeretablissement, libellepaysetrangeretablissement, complementadresse2etablissement, numerovoie2etablissement, indicerepetition2etablissement, 
    typevoie2etablissement, libellevoie2etablissement, codepostal2etablissement, libellecommune2etablissement, libellecommuneetranger2etablissement, 
    distributionspeciale2etablissement, codecommune2etablissement, codecedex2etablissement, libellecedex2etablissement, codepaysetranger2etablissement, 
    libellepaysetranger2etablissement, datedebut, etatadministratifetablissement, enseigne1etablissement, enseigne2etablissement, enseigne3etablissement, 
    denominationusuelleetablissement, activiteprincipaleetablissement, nomenclatureactiviteprincipaleetablissement, caractereemployeuretablissement, 
    entree_champ_diffusion_commerciale, changement_activiteprincipaleetablissement, demenagement, changement_etat_administratif, modification_tranche_nb_salaries,
    adresse, latitude, longitude, result_label, result_score, result_type, result_id, result_housenumber, result_name, result_street, 
    result_postcode, result_city, result_context, result_citycode, dcomiris, nom_iris, depcom, nom_commune, code_departement, nom_departement, 
    streetview, creation, deja_geoc, code_region, nom_region, section, baf, recup_geocube, tel, score_tel, date_integration, denomination_geoscar, cle_adresse
    from poi.sirene_etablissement_ferme ";
        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);
        $sql->execute();
    }

    public function vaccumFullGeosirene() {

        $sQuery = "VACUUM FULL poi.geosirene;";
        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);
        $sql->execute();
    }

    public function vaccumGeosirene() {

        $sQuery = "VACUUM ANALYZE poi.geosirene;";
        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);
        $sql->execute();
    }

    public function vaccumFullStock() {

        $sQuery = "VACUUM FULL poi.sirene_etablissement_n0;";
        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);
        $sql->execute();
    }

    public function vaccumStock() {

        $sQuery = "VACUUM ANALYZE poi.sirene_etablissement_n0;";
        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);
        $sql->execute();
    }

    public function getIntituleNaf($naf) {

        $sQuery = "SELECT * FROM poi.codeape WHERE codenaf =:naf ";

        $cn = $this->getConnexion();
        $sql = $cn->prepare($sQuery);
        $sql->bindParam(':naf', $naf, PDO::PARAM_STR);
        $sql->execute();

        $aErreur = $sql->errorInfo();

        if (strlen($aErreur[2]) > 0) {

            die($aErreur[2]);
        } else {

            return $sql->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    public function getGeoscarSansDenomination($iOffset) {

        $this->getConnexion();
        $sSql = "SELECT * FROM poi.geosirene "
                . "WHERE denomination_geoscar IS null OR denomination_geoscar ='' ORDER BY gid LIMIT 5000 OFFSET " . $iOffset;
        $resultset = self::$oConnexion->prepare($sSql);
        $resultset->execute();
        return $resultset->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUlBiSIren($sSiren) {

        $this->getConnexion();
        $sSql = "SELECT * FROM poi.stock_ul WHERE siren = '$sSiren' ";
        $resultset = self::$oConnexion->prepare($sSql);
        $resultset->execute();
        return $resultset->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateDenominationGeoscar($sDenomination, $sSiret) {

        $db = $this->getConnexion();
        $sSql = "UPDATE poi.geosirene SET denomination_geoscar=:denomination_geoscar"
                . " WHERE siret =:siret ";
        $sql = $db->prepare($sSql);
        $sql->bindParam(':denomination_geoscar', $sDenomination, PDO::PARAM_STR);
        $sql->bindParam(':siret', $sSiret, PDO::PARAM_STR);
        $sql->execute();
        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            echo $sSql . "\n\n";
            $this->sendMailIncidentQuery($sSql, $aErreur[2]);
            //die($aErreur[2]);
        }
    }

    public function updateDenominationGeoscarStock($sDenomination, $sSiret) {

        $db = $this->getConnexion();
        $sSql = "UPDATE poi.sirene_etablissement_n0 SET denomination_geoscar=:denomination_geoscar"
                . " WHERE siret =:siret ";
        $sql = $db->prepare($sSql);
        $sql->bindParam(':denomination_geoscar', $sDenomination, PDO::PARAM_STR);
        $sql->bindParam(':siret', $sSiret, PDO::PARAM_STR);
        $sql->execute();
        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            echo $sSql . "\n\n";
            $this->sendMailIncidentQuery($sSql, $aErreur[2]);
            //die($aErreur[2]);
        }
    }

    public function truncateStockUlTmp() {

        $sQuery = "TRUNCATE table poi.stock_ul_tmp";

        $this->queryPDO($sQuery);
    }

    public function insertSTockUlTmp($oInsee) {

        if (!$oInsee->periodesUniteLegale[0]->changementEtatAdministratifUniteLegale) {
            $bchangementEtatAdministratifUniteLegale = 'FALSE';
        } else {
            $bchangementEtatAdministratifUniteLegale = 'TRUE';
        }

        if (!$oInsee->periodesUniteLegale[0]->changementNomUniteLegale) {
            $bchangementnomunitelegale = 'FALSE';
        } else {
            $bchangementnomunitelegale = 'TRUE';
        }

        if (!$oInsee->periodesUniteLegale[0]->changementNomUsageUniteLegale) {
            $bchangementNomUsageUniteLegale = 'FALSE';
        } else {
            $bchangementNomUsageUniteLegale = 'TRUE';
        }

        if (!$oInsee->periodesUniteLegale[0]->changementDenominationUniteLegale) {
            $bchangementDenominationUniteLegale = 'FALSE';
        } else {
            $bchangementDenominationUniteLegale = 'TRUE';
        }

        if (!$oInsee->periodesUniteLegale[0]->changementDenominationUsuelleUniteLegale) {
            $bchangementDenominationUsuelleUniteLegale = 'FALSE';
        } else {
            $bchangementDenominationUsuelleUniteLegale = 'TRUE';
        }

        if (!$oInsee->periodesUniteLegale[0]->changementCategorieJuridiqueUniteLegale) {
            $bchangementCategorieJuridiqueUniteLegale = 'FALSE';
        } else {
            $bchangementCategorieJuridiqueUniteLegale = 'TRUE';
        }

        if (!$oInsee->periodesUniteLegale[0]->changementActivitePrincipaleUniteLegale) {
            $bchangementActivitePrincipaleUniteLegale = 'FALSE';
        } else {
            $bchangementActivitePrincipaleUniteLegale = 'TRUE';
        }

        if (!$oInsee->periodesUniteLegale[0]->changementNicSiegeUniteLegale) {
            $bchangementNicSiegeUniteLegale = 'FALSE';
        } else {
            $bchangementNicSiegeUniteLegale = 'TRUE';
        }

        if (!$oInsee->periodesUniteLegale[0]->changementEconomieSocialeSolidaireUniteLegale) {
            $bchangementEconomieSocialeSolidaireUniteLegale = 'FALSE';
        } else {
            $bchangementEconomieSocialeSolidaireUniteLegale = 'TRUE';
        }

        if (!$oInsee->periodesUniteLegale[0]->changementCaractereEmployeurUniteLegale) {
            $bchangementCaractereEmployeurUniteLegale = 'FALSE';
        } else {
            $bchangementCaractereEmployeurUniteLegale = 'TRUE';
        }






        $sQuery = "INSERT INTO poi.stock_ul_tmp(
	siren, statutdiffusionunitelegale, datecreationunitelegale, sigleunitelegale, 
        sexeunitelegale, prenom1unitelegale, prenom2unitelegale, 
        prenom3unitelegale, prenom4unitelegale, prenomusuelunitelegale, 
        pseudonymeunitelegale, identifiantassociationunitelegale, 
        trancheeffectifsunitelegale, anneeeffectifsunitelegale, 
        datederniertraitementunitelegale, nombreperiodesunitelegale, 
        categorieentreprise, anneecategorieentreprise, datefin, 
        datedebut, etatadministratifunitelegale, changementetatadministratifunitelegale, 
        nomunitelegale, changementnomunitelegale, nomusageunitelegale, changementnomusageunitelegale, 
        denominationunitelegale, changementdenominationunitelegale, denominationusuelle1unitelegale, 
        denominationusuelle2unitelegale, denominationusuelle3unitelegale, changementdenominationusuelleunitelegale, 
        categoriejuridiqueunitelegale, changementcategoriejuridiqueunitelegale, activiteprincipaleunitelegale, 
        nomenclatureactiviteprincipaleunitelegale, changementactiviteprincipaleunitelegale, nicsiegeunitelegale, 
        changementnicsiegeunitelegale, economiesocialesolidaireunitelegale, changementeconomiesocialesolidaireunitelegale, 
        caractereemployeurunitelegale, changementcaractereemployeurunitelegale)
	VALUES (:siren, :statutdiffusionunitelegale, :datecreationunitelegale, :sigleunitelegale, 
        :sexeunitelegale, :prenom1unitelegale, :prenom2unitelegale, 
        :prenom3unitelegale, :prenom4unitelegale, :prenomusuelunitelegale, 
        :pseudonymeunitelegale, :identifiantassociationunitelegale, 
        :trancheeffectifsunitelegale, :anneeeffectifsunitelegale, 
        :datederniertraitementunitelegale, :nombreperiodesunitelegale, 
        :categorieentreprise, :anneecategorieentreprise, :datefin, 
        :datedebut, :etatadministratifunitelegale, :changementetatadministratifunitelegale, 
        :nomunitelegale, :changementnomunitelegale, :nomusageunitelegale, :changementnomusageunitelegale, 
        :denominationunitelegale, :changementdenominationunitelegale, :denominationusuelle1unitelegale, 
        :denominationusuelle2unitelegale, :denominationusuelle3unitelegale, :changementdenominationusuelleunitelegale, 
        :categoriejuridiqueunitelegale, :changementcategoriejuridiqueunitelegale, :activiteprincipaleunitelegale, 
        :nomenclatureactiviteprincipaleunitelegale, :changementactiviteprincipaleunitelegale, :nicsiegeunitelegale, 
        :changementnicsiegeunitelegale, :economiesocialesolidaireunitelegale, :changementeconomiesocialesolidaireunitelegale, 
        :caractereemployeurunitelegale, :changementcaractereemployeurunitelegale);";

        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);

        $sql->bindParam(':siren', $oInsee->siren, PDO::PARAM_STR);
        $sql->bindParam(':statutdiffusionunitelegale', $oInsee->statutDiffusionUniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':datecreationunitelegale', $oInsee->dateCreationUniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':sigleunitelegale', $oInsee->sigleUniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':sexeunitelegale', $oInsee->sexeUniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':prenom1unitelegale', $oInsee->prenom1UniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':prenom2unitelegale', $oInsee->prenom2UniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':prenom3unitelegale', $oInsee->prenom3UniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':prenom4unitelegale', $oInsee->prenom4UniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':prenomusuelunitelegale', $oInsee->prenomUsuelUniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':pseudonymeunitelegale', $oInsee->pseudonymeUniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':identifiantassociationunitelegale', $oInsee->identifiantAssociationUniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':trancheeffectifsunitelegale', $oInsee->trancheEffectifsUniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':anneeeffectifsunitelegale', $oInsee->anneeEffectifsUniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':datederniertraitementunitelegale', $oInsee->dateDernierTraitementUniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':nombreperiodesunitelegale', $oInsee->nombrePeriodesUniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':categorieentreprise', $oInsee->categorieEntreprise, PDO::PARAM_STR);
        $sql->bindParam(':anneecategorieentreprise', $oInsee->anneeCategorieEntreprise, PDO::PARAM_STR);
        $sql->bindParam(':datefin', $oInsee->periodesUniteLegale[0]->dateFin, PDO::PARAM_STR);
        $sql->bindParam(':datedebut', $oInsee->periodesUniteLegale[0]->dateDebut, PDO::PARAM_STR);
        $sql->bindParam(':etatadministratifunitelegale', $oInsee->periodesUniteLegale[0]->etatAdministratifUniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':changementetatadministratifunitelegale', $bchangementEtatAdministratifUniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':nomunitelegale', $oInsee->periodesUniteLegale[0]->nomUniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':changementnomunitelegale', $bchangementnomunitelegale, PDO::PARAM_STR);
        $sql->bindParam(':nomusageunitelegale', $oInsee->periodesUniteLegale[0]->nomUsageUniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':changementnomusageunitelegale', $bchangementNomUsageUniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':denominationunitelegale', $oInsee->periodesUniteLegale[0]->denominationUniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':changementdenominationunitelegale', $bchangementDenominationUniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':denominationusuelle1unitelegale', $oInsee->periodesUniteLegale[0]->denominationUsuelle1UniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':denominationusuelle2unitelegale', $oInsee->periodesUniteLegale[0]->denominationUsuelle2UniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':denominationusuelle3unitelegale', $oInsee->periodesUniteLegale[0]->denominationUsuelle3UniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':changementdenominationusuelleunitelegale', $bchangementDenominationUsuelleUniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':categoriejuridiqueunitelegale', $oInsee->periodesUniteLegale[0]->categorieJuridiqueUniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':changementcategoriejuridiqueunitelegale', $bchangementCategorieJuridiqueUniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':activiteprincipaleunitelegale', $oInsee->periodesUniteLegale[0]->activitePrincipaleUniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':nomenclatureactiviteprincipaleunitelegale', $oInsee->periodesUniteLegale[0]->nomenclatureActivitePrincipaleUniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':changementactiviteprincipaleunitelegale', $bchangementActivitePrincipaleUniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':nicsiegeunitelegale', $oInsee->periodesUniteLegale[0]->nicSiegeUniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':changementnicsiegeunitelegale', $bchangementNicSiegeUniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':economiesocialesolidaireunitelegale', $oInsee->periodesUniteLegale[0]->economieSocialeSolidaireUniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':changementeconomiesocialesolidaireunitelegale', $bchangementEconomieSocialeSolidaireUniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':caractereemployeurunitelegale', $oInsee->periodesUniteLegale[0]->caractereEmployeurUniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':changementcaractereemployeurunitelegale', $bchangementCaractereEmployeurUniteLegale, PDO::PARAM_STR);

        $sql->execute();
//
        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            echo $sQuery . "\n\n";
            $this->sendMailIncidentQuery($sQuery, $aErreur[2]);
            //die($aErreur[2]);
        }
    }

    public function getTmpStockUl($iOffset) {

        $sQuery = "SELECT * FROM poi.stock_ul_tmp LIMIT 5000 OFFSET " . $iOffset;
        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);

        $sql->execute();

        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            echo $sQuery . "<br>";
            die($aErreur[2]);
        } else {
            return $sql->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    public function searchStockUlBySiren($sSiren) {

        $sQuery = "SELECT * FROM poi.stock_ul WHERE siren =:siren";
        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);

        $sql->bindParam(':siren', $sSiren, PDO::PARAM_STR);

        $sql->execute();

        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            echo $sQuery . "<br>";
            die($aErreur[2]);
        } else {
            return $sql->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    public function deleteStockBySiren($sSiren) {
        $sQuery = "DELETE FROM poi.stock_ul WHERE siren ='$sSiren';";
        $this->queryPDO($sQuery);
    }

    public function deleteStockCesseesBySiren($sSiren) {
        $sQuery = "DELETE FROM poi.stock_ul_cessees WHERE siren ='$sSiren';";
        $this->queryPDO($sQuery);
    }

    public function insertSTockUlCessees($aStock, $sDateFormat) {

        //var_dump($aStock);

        $siren = $aStock['siren'];
        $statutdiffusionunitelegale = $aStock['statutdiffusionunitelegale'];
        $datecreationunitelegale = $aStock['datecreationunitelegale'];
        $sigleunitelegale = $aStock['sigleunitelegale'];
        $sexeunitelegale = $aStock['sexeunitelegale'];
        $prenom1unitelegale = $aStock['prenom1unitelegale'];
        $prenom2unitelegale = $aStock['prenom2unitelegale'];
        $prenom3unitelegale = $aStock['prenom3unitelegale'];
        $prenom4unitelegale = $aStock['prenom4unitelegale'];
        $prenomusuelunitelegale = $aStock['prenomusuelunitelegale'];
        $pseudonymeunitelegale = $aStock['pseudonymeunitelegale'];
        $identifiantassociationunitelegale = $aStock['identifiantassociationunitelegale'];
        $trancheeffectifsunitelegale = $aStock['trancheeffectifsunitelegale'];
        $anneeeffectifsunitelegale = $aStock['anneeeffectifsunitelegale'];
        $datederniertraitementunitelegale = $aStock['datederniertraitementunitelegale'];
        $nombreperiodesunitelegale = $aStock['nombreperiodesunitelegale'];
        $categorieentreprise = $aStock['categorieentreprise'];
        $anneecategorieentreprise = $aStock['anneecategorieentreprise'];
        $datefin = $aStock['datefin'];
        $datedebut = $aStock['datedebut'];
        $etatadministratifunitelegale = $aStock['etatadministratifunitelegale'];
        $nomunitelegale = $aStock['nomunitelegale'];
        $nomusageunitelegale = $aStock['nomusageunitelegale'];
        $denominationunitelegale = $aStock['denominationunitelegale'];
        $denominationusuelle1unitelegale = $aStock['denominationusuelle1unitelegale'];
        $denominationusuelle2unitelegale = $aStock['denominationusuelle2unitelegale'];
        $denominationusuelle3unitelegale = $aStock['denominationusuelle3unitelegale'];
        $categoriejuridiqueunitelegale = $aStock['categoriejuridiqueunitelegale'];
        $activiteprincipaleunitelegale = $aStock['activiteprincipaleunitelegale'];
        $nomenclatureactiviteprincipaleunitelegale = $aStock['nomenclatureactiviteprincipaleunitelegale'];
        $nicsiegeunitelegale = $aStock['nicsiegeunitelegale'];
        $economiesocialesolidaireunitelegale = $aStock['economiesocialesolidaireunitelegale'];
        $caractereemployeurunitelegale = $aStock['caractereemployeurunitelegale'];

        if (!$aStock['changementetatadministratifunitelegale']) {
            $bchangementEtatAdministratifUniteLegale = 'FALSE';
        } else {
            $bchangementEtatAdministratifUniteLegale = 'TRUE';
        }

        if (!$aStock['changementnomunitelegale']) {
            $bchangementnomunitelegale = 'FALSE';
        } else {
            $bchangementnomunitelegale = 'TRUE';
        }

        if (!$aStock['changementnomusageunitelegale']) {
            $bchangementNomUsageUniteLegale = 'FALSE';
        } else {
            $bchangementNomUsageUniteLegale = 'TRUE';
        }

        if (!$aStock['changementdenominationunitelegale']) {
            $bchangementDenominationUniteLegale = 'FALSE';
        } else {
            $bchangementDenominationUniteLegale = 'TRUE';
        }

        if (!$aStock['changementdenominationusuelleunitelegale']) {
            $bchangementDenominationUsuelleUniteLegale = 'FALSE';
        } else {
            $bchangementDenominationUsuelleUniteLegale = 'TRUE';
        }

        if (!$aStock['changementcategoriejuridiqueunitelegale']) {
            $bchangementCategorieJuridiqueUniteLegale = 'FALSE';
        } else {
            $bchangementCategorieJuridiqueUniteLegale = 'TRUE';
        }

        if (!$aStock['changementactiviteprincipaleunitelegale']) {
            $bchangementActivitePrincipaleUniteLegale = 'FALSE';
        } else {
            $bchangementActivitePrincipaleUniteLegale = 'TRUE';
        }

        if (!$aStock['changementnicsiegeunitelegale']) {
            $bchangementNicSiegeUniteLegale = 'FALSE';
        } else {
            $bchangementNicSiegeUniteLegale = 'TRUE';
        }

        if (!$aStock['changementeconomiesocialesolidaireunitelegale']) {
            $bchangementEconomieSocialeSolidaireUniteLegale = 'FALSE';
        } else {
            $bchangementEconomieSocialeSolidaireUniteLegale = 'TRUE';
        }

        if (!$aStock['changementcaractereemployeurunitelegale']) {
            $bchangementCaractereEmployeurUniteLegale = 'FALSE';
        } else {
            $bchangementCaractereEmployeurUniteLegale = 'TRUE';
        }


        $sQuery = "INSERT INTO poi.stock_ul_cessees(
	siren, statutdiffusionunitelegale, datecreationunitelegale,
        sigleunitelegale, sexeunitelegale, prenom1unitelegale, 
        prenom2unitelegale, prenom3unitelegale, prenom4unitelegale, 
        prenomusuelunitelegale, pseudonymeunitelegale, identifiantassociationunitelegale, 
        trancheeffectifsunitelegale, anneeeffectifsunitelegale, 
        datederniertraitementunitelegale, nombreperiodesunitelegale, 
        categorieentreprise, anneecategorieentreprise,datefin, 
        datedebut, etatadministratifunitelegale, changementetatadministratifunitelegale, 
        nomunitelegale, changementnomunitelegale, nomusageunitelegale, changementnomusageunitelegale, 
        denominationunitelegale, changementdenominationunitelegale, denominationusuelle1unitelegale, 
        denominationusuelle2unitelegale, denominationusuelle3unitelegale, changementdenominationusuelleunitelegale, 
        categoriejuridiqueunitelegale, changementcategoriejuridiqueunitelegale, activiteprincipaleunitelegale, 
        nomenclatureactiviteprincipaleunitelegale, changementactiviteprincipaleunitelegale, nicsiegeunitelegale, 
        changementnicsiegeunitelegale, economiesocialesolidaireunitelegale, changementeconomiesocialesolidaireunitelegale, 
        caractereemployeurunitelegale, changementcaractereemployeurunitelegale, date_integration)
	VALUES (:siren, :statutdiffusionunitelegale, :datecreationunitelegale,
        :sigleunitelegale, :sexeunitelegale, :prenom1unitelegale, 
        :prenom2unitelegale, :prenom3unitelegale, :prenom4unitelegale, 
        :prenomusuelunitelegale, :pseudonymeunitelegale, :identifiantassociationunitelegale, 
        :trancheeffectifsunitelegale, :anneeeffectifsunitelegale, 
        :datederniertraitementunitelegale, :nombreperiodesunitelegale, 
        :categorieentreprise, :anneecategorieentreprise,:datefin, 
        :datedebut, :etatadministratifunitelegale, :changementetatadministratifunitelegale, 
        :nomunitelegale, :changementnomunitelegale, :nomusageunitelegale, :changementnomusageunitelegale, 
        :denominationunitelegale, :changementdenominationunitelegale, :denominationusuelle1unitelegale, 
        :denominationusuelle2unitelegale, :denominationusuelle3unitelegale, :changementdenominationusuelleunitelegale, 
        :categoriejuridiqueunitelegale, :changementcategoriejuridiqueunitelegale, :activiteprincipaleunitelegale, 
        :nomenclatureactiviteprincipaleunitelegale, :changementactiviteprincipaleunitelegale, :nicsiegeunitelegale, 
        :changementnicsiegeunitelegale, :economiesocialesolidaireunitelegale, :changementeconomiesocialesolidaireunitelegale, 
        :caractereemployeurunitelegale, :changementcaractereemployeurunitelegale, :date_integration);";

        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);

        $sql->bindParam(':siren', $siren, PDO::PARAM_STR);
        $sql->bindParam(':statutdiffusionunitelegale', $statutdiffusionunitelegale, PDO::PARAM_STR);
        $sql->bindParam(':datecreationunitelegale', $datecreationunitelegale, PDO::PARAM_STR);
        $sql->bindParam(':sigleunitelegale', $sigleunitelegale, PDO::PARAM_STR);
        $sql->bindParam(':sexeunitelegale', $sexeunitelegale, PDO::PARAM_STR);
        $sql->bindParam(':prenom1unitelegale', $prenom1unitelegale, PDO::PARAM_STR);
        $sql->bindParam(':prenom2unitelegale', $prenom2unitelegale, PDO::PARAM_STR);
        $sql->bindParam(':prenom3unitelegale', $prenom3unitelegale, PDO::PARAM_STR);
        $sql->bindParam(':prenom4unitelegale', $prenom4unitelegale, PDO::PARAM_STR);
        $sql->bindParam(':prenomusuelunitelegale', $prenomusuelunitelegale, PDO::PARAM_STR);
        $sql->bindParam(':pseudonymeunitelegale', $pseudonymeunitelegale, PDO::PARAM_STR);
        $sql->bindParam(':identifiantassociationunitelegale', $identifiantassociationunitelegale, PDO::PARAM_STR);
        $sql->bindParam(':trancheeffectifsunitelegale', $trancheeffectifsunitelegale, PDO::PARAM_STR);
        $sql->bindParam(':anneeeffectifsunitelegale', $anneeeffectifsunitelegale, PDO::PARAM_STR);
        $sql->bindParam(':datederniertraitementunitelegale', $datederniertraitementunitelegale, PDO::PARAM_STR);
        $sql->bindParam(':nombreperiodesunitelegale', $nombreperiodesunitelegale, PDO::PARAM_INT);
        $sql->bindParam(':categorieentreprise', $categorieentreprise, PDO::PARAM_INT);
        $sql->bindParam(':anneecategorieentreprise', $anneecategorieentreprise, PDO::PARAM_INT);
        $sql->bindParam(':datefin', $datefin, PDO::PARAM_STR);
        $sql->bindParam(':datedebut', $datedebut, PDO::PARAM_STR);
        $sql->bindParam(':etatadministratifunitelegale', $etatadministratifunitelegale, PDO::PARAM_STR);
        $sql->bindParam(':changementetatadministratifunitelegale', $bchangementEtatAdministratifUniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':nomunitelegale', $nomunitelegale, PDO::PARAM_STR);
        $sql->bindParam(':changementnomunitelegale', $bchangementnomunitelegale, PDO::PARAM_STR);
        $sql->bindParam(':nomusageunitelegale', $nomusageunitelegale, PDO::PARAM_STR);
        $sql->bindParam(':changementnomusageunitelegale', $bchangementNomUsageUniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':denominationunitelegale', $denominationunitelegale, PDO::PARAM_STR);
        $sql->bindParam(':changementdenominationunitelegale', $bchangementDenominationUniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':denominationusuelle1unitelegale', $denominationusuelle1unitelegale, PDO::PARAM_STR);
        $sql->bindParam(':denominationusuelle2unitelegale', $denominationusuelle2unitelegale, PDO::PARAM_STR);
        $sql->bindParam(':denominationusuelle3unitelegale', $denominationusuelle3unitelegale, PDO::PARAM_STR);
        $sql->bindParam(':changementdenominationusuelleunitelegale', $bchangementDenominationUsuelleUniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':categoriejuridiqueunitelegale', $categoriejuridiqueunitelegale, PDO::PARAM_STR);
        $sql->bindParam(':changementcategoriejuridiqueunitelegale', $bchangementCategorieJuridiqueUniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':activiteprincipaleunitelegale', $activiteprincipaleunitelegale, PDO::PARAM_STR);
        $sql->bindParam(':nomenclatureactiviteprincipaleunitelegale', $nomenclatureactiviteprincipaleunitelegale, PDO::PARAM_STR);
        $sql->bindParam(':changementactiviteprincipaleunitelegale', $bchangementActivitePrincipaleUniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':nicsiegeunitelegale', $nicsiegeunitelegale, PDO::PARAM_STR);
        $sql->bindParam(':changementnicsiegeunitelegale', $bchangementNicSiegeUniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':economiesocialesolidaireunitelegale', $economiesocialesolidaireunitelegale, PDO::PARAM_STR);
        $sql->bindParam(':changementeconomiesocialesolidaireunitelegale', $bchangementEconomieSocialeSolidaireUniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':caractereemployeurunitelegale', $caractereemployeurunitelegale, PDO::PARAM_STR);
        $sql->bindParam(':changementcaractereemployeurunitelegale', $bchangementCaractereEmployeurUniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':date_integration', $sDateFormat, PDO::PARAM_STR);


        $sql->execute();
//
        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            echo $sQuery . "\n\n";
            $this->sendMailIncidentQuery($sQuery, $aErreur[2]);
            //die($aErreur[2]);
        }
    }

    public function insertSTockUl($aStock, $sDateFormat) {

        if (!$aStock['changementetatadministratifunitelegale']) {
            $bchangementEtatAdministratifUniteLegale = 'FALSE';
        } else {
            $bchangementEtatAdministratifUniteLegale = 'TRUE';
        }

        if (!$aStock['changementnomunitelegale']) {
            $bchangementnomunitelegale = 'FALSE';
        } else {
            $bchangementnomunitelegale = 'TRUE';
        }

        if (!$aStock['changementnomusageunitelegale']) {
            $bchangementNomUsageUniteLegale = 'FALSE';
        } else {
            $bchangementNomUsageUniteLegale = 'TRUE';
        }

        if (!$aStock['changementdenominationunitelegale']) {
            $bchangementDenominationUniteLegale = 'FALSE';
        } else {
            $bchangementDenominationUniteLegale = 'TRUE';
        }

        if (!$aStock['changementdenominationusuelleunitelegale']) {
            $bchangementDenominationUsuelleUniteLegale = 'FALSE';
        } else {
            $bchangementDenominationUsuelleUniteLegale = 'TRUE';
        }

        if (!$aStock['changementcategoriejuridiqueunitelegale']) {
            $bchangementCategorieJuridiqueUniteLegale = 'FALSE';
        } else {
            $bchangementCategorieJuridiqueUniteLegale = 'TRUE';
        }

        if (!$aStock['changementactiviteprincipaleunitelegale']) {
            $bchangementActivitePrincipaleUniteLegale = 'FALSE';
        } else {
            $bchangementActivitePrincipaleUniteLegale = 'TRUE';
        }

        if (!$aStock['changementnicsiegeunitelegale']) {
            $bchangementNicSiegeUniteLegale = 'FALSE';
        } else {
            $bchangementNicSiegeUniteLegale = 'TRUE';
        }

        if (!$aStock['changementeconomiesocialesolidaireunitelegale']) {
            $bchangementEconomieSocialeSolidaireUniteLegale = 'FALSE';
        } else {
            $bchangementEconomieSocialeSolidaireUniteLegale = 'TRUE';
        }

        if (!$aStock['changementcaractereemployeurunitelegale']) {
            $bchangementCaractereEmployeurUniteLegale = 'FALSE';
        } else {
            $bchangementCaractereEmployeurUniteLegale = 'TRUE';
        }


        $sQuery = "INSERT INTO poi.stock_ul(
	siren, statutdiffusionunitelegale, datecreationunitelegale,
        sigleunitelegale, sexeunitelegale, prenom1unitelegale, 
        prenom2unitelegale, prenom3unitelegale, prenom4unitelegale, 
        prenomusuelunitelegale, pseudonymeunitelegale, identifiantassociationunitelegale, 
        trancheeffectifsunitelegale, anneeeffectifsunitelegale, 
        datederniertraitementunitelegale, nombreperiodesunitelegale, 
        categorieentreprise, anneecategorieentreprise,datefin, 
        datedebut, etatadministratifunitelegale, changementetatadministratifunitelegale, 
        nomunitelegale, changementnomunitelegale, nomusageunitelegale, changementnomusageunitelegale, 
        denominationunitelegale, changementdenominationunitelegale, denominationusuelle1unitelegale, 
        denominationusuelle2unitelegale, denominationusuelle3unitelegale, changementdenominationusuelleunitelegale, 
        categoriejuridiqueunitelegale, changementcategoriejuridiqueunitelegale, activiteprincipaleunitelegale, 
        nomenclatureactiviteprincipaleunitelegale, changementactiviteprincipaleunitelegale, nicsiegeunitelegale, 
        changementnicsiegeunitelegale, economiesocialesolidaireunitelegale, changementeconomiesocialesolidaireunitelegale, 
        caractereemployeurunitelegale, changementcaractereemployeurunitelegale, date_integration)
	VALUES (:siren, :statutdiffusionunitelegale, :datecreationunitelegale,
        :sigleunitelegale, :sexeunitelegale, :prenom1unitelegale, 
        :prenom2unitelegale, :prenom3unitelegale, :prenom4unitelegale, 
        :prenomusuelunitelegale, :pseudonymeunitelegale, :identifiantassociationunitelegale, 
        :trancheeffectifsunitelegale, :anneeeffectifsunitelegale, 
        :datederniertraitementunitelegale, :nombreperiodesunitelegale, 
        :categorieentreprise, :anneecategorieentreprise,:datefin, 
        :datedebut, :etatadministratifunitelegale, :changementetatadministratifunitelegale, 
        :nomunitelegale, :changementnomunitelegale, :nomusageunitelegale, :changementnomusageunitelegale, 
        :denominationunitelegale, :changementdenominationunitelegale, :denominationusuelle1unitelegale, 
        :denominationusuelle2unitelegale, :denominationusuelle3unitelegale, :changementdenominationusuelleunitelegale, 
        :categoriejuridiqueunitelegale, :changementcategoriejuridiqueunitelegale, :activiteprincipaleunitelegale, 
        :nomenclatureactiviteprincipaleunitelegale, :changementactiviteprincipaleunitelegale, :nicsiegeunitelegale, 
        :changementnicsiegeunitelegale, :economiesocialesolidaireunitelegale, :changementeconomiesocialesolidaireunitelegale, 
        :caractereemployeurunitelegale, :changementcaractereemployeurunitelegale, :date_integration);";

        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);

        $sql->bindParam(':siren', $aStock['siren'], PDO::PARAM_STR);
        $sql->bindParam(':statutdiffusionunitelegale', $aStock['statutdiffusionunitelegale'], PDO::PARAM_STR);
        $sql->bindParam(':datecreationunitelegale', $aStock['datecreationunitelegale'], PDO::PARAM_STR);
        $sql->bindParam(':sigleunitelegale', $aStock['sigleunitelegale'], PDO::PARAM_STR);
        $sql->bindParam(':sexeunitelegale', $aStock['sexeunitelegale'], PDO::PARAM_STR);
        $sql->bindParam(':prenom1unitelegale', $aStock['prenom1unitelegale'], PDO::PARAM_STR);
        $sql->bindParam(':prenom2unitelegale', $aStock['prenom2unitelegale'], PDO::PARAM_STR);
        $sql->bindParam(':prenom3unitelegale', $aStock['prenom3unitelegale'], PDO::PARAM_STR);
        $sql->bindParam(':prenom4unitelegale', $aStock['prenom4unitelegale'], PDO::PARAM_STR);
        $sql->bindParam(':prenomusuelunitelegale', $aStock['prenomusuelunitelegale'], PDO::PARAM_STR);
        $sql->bindParam(':pseudonymeunitelegale', $aStock['pseudonymeunitelegale'], PDO::PARAM_STR);
        $sql->bindParam(':identifiantassociationunitelegale', $aStock['identifiantassociationunitelegale'], PDO::PARAM_STR);
        $sql->bindParam(':trancheeffectifsunitelegale', $aStock['trancheeffectifsunitelegale'], PDO::PARAM_STR);
        $sql->bindParam(':anneeeffectifsunitelegale', $aStock['anneeeffectifsunitelegale'], PDO::PARAM_STR);
        $sql->bindParam(':datederniertraitementunitelegale', $aStock['datederniertraitementunitelegale'], PDO::PARAM_STR);
        $sql->bindParam(':nombreperiodesunitelegale', $aStock['nombreperiodesunitelegale'], PDO::PARAM_INT);
        $sql->bindParam(':categorieentreprise', $aStock['categorieentreprise'], PDO::PARAM_INT);
        $sql->bindParam(':anneecategorieentreprise', $aStock['anneecategorieentreprise'], PDO::PARAM_INT);
        $sql->bindParam(':datefin', $aStock['datefin'], PDO::PARAM_STR);
        $sql->bindParam(':datedebut', $aStock['datedebut'], PDO::PARAM_STR);
        $sql->bindParam(':etatadministratifunitelegale', $aStock['etatadministratifunitelegale'], PDO::PARAM_STR);
        $sql->bindParam(':changementetatadministratifunitelegale', $bchangementEtatAdministratifUniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':nomunitelegale', $aStock['nomunitelegale'], PDO::PARAM_STR);
        $sql->bindParam(':changementnomunitelegale', $bchangementnomunitelegale, PDO::PARAM_STR);
        $sql->bindParam(':nomusageunitelegale', $aStock['nomusageunitelegale'], PDO::PARAM_STR);
        $sql->bindParam(':changementnomusageunitelegale', $bchangementNomUsageUniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':denominationunitelegale', $aStock['denominationunitelegale'], PDO::PARAM_STR);
        $sql->bindParam(':changementdenominationunitelegale', $bchangementDenominationUniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':denominationusuelle1unitelegale', $aStock['denominationusuelle1unitelegale'], PDO::PARAM_STR);
        $sql->bindParam(':denominationusuelle2unitelegale', $aStock['denominationusuelle2unitelegale'], PDO::PARAM_STR);
        $sql->bindParam(':denominationusuelle3unitelegale', $aStock['denominationusuelle3unitelegale'], PDO::PARAM_STR);
        $sql->bindParam(':changementdenominationusuelleunitelegale', $bchangementDenominationUsuelleUniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':categoriejuridiqueunitelegale', $aStock['categoriejuridiqueunitelegale'], PDO::PARAM_STR);
        $sql->bindParam(':changementcategoriejuridiqueunitelegale', $bchangementCategorieJuridiqueUniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':activiteprincipaleunitelegale', $aStock['activiteprincipaleunitelegale'], PDO::PARAM_STR);
        $sql->bindParam(':nomenclatureactiviteprincipaleunitelegale', $aStock['nomenclatureactiviteprincipaleunitelegale'], PDO::PARAM_STR);
        $sql->bindParam(':changementactiviteprincipaleunitelegale', $bchangementActivitePrincipaleUniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':nicsiegeunitelegale', $aStock['nicsiegeunitelegale'], PDO::PARAM_STR);
        $sql->bindParam(':changementnicsiegeunitelegale', $bchangementNicSiegeUniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':economiesocialesolidaireunitelegale', $aStock['economiesocialesolidaireunitelegale'], PDO::PARAM_STR);
        $sql->bindParam(':changementeconomiesocialesolidaireunitelegale', $bchangementEconomieSocialeSolidaireUniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':caractereemployeurunitelegale', $aStock['caractereemployeurunitelegale'], PDO::PARAM_STR);
        $sql->bindParam(':changementcaractereemployeurunitelegale', $bchangementCaractereEmployeurUniteLegale, PDO::PARAM_STR);
        $sql->bindParam(':date_integration', $sDateFormat, PDO::PARAM_STR);

        $sql->execute();
//
        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            echo $sQuery . "\n\n";
            $this->sendMailIncidentQuery($sQuery, $aErreur[2]);
            //die($aErreur[2]);
        }
    }

    public function sendMailFinDenoGeoscar() {

        $sQuery = "select public.bdf_envoi_mail_fin_maj_deno();";
        $this->queryPDO($sQuery);
    }

    public function sendMailIncidentQuery($requete, $message) {

        $sQuery = "SELECT public.bdf_alerte_envoi_mail_error_query('" . $requete . "','" . $message . "')";
        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);
        $sql->execute();
    }

    public function updateDateIntNumFic($date, $numfic) {
        
        $sQuery = "UPDATE poi.geosirene SET num_fic=:num_fic WHERE date_integration=:date_integration";
        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);
        $sql->bindParam(':date_integration', $date, PDO::PARAM_STR);
        $sql->bindParam(':num_fic', $numfic, PDO::PARAM_STR);

        $sql->execute();
    }

}
