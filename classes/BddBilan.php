<?php

/**
 * Description of BddBilan
 *
 * @author sleco
 */
class BddBilan {

    public static $oConnexion;
    public static $oConnexionProd;

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
                //echo "connexion geocube OK <br><br>";
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
            echo $sQuery . "<br>";
            die($aErreur[2]);
        }
    }

    public function queryPDOResulset($sQuery) {

        $this->getConnexion();
        $resultset = self::$oConnexion->prepare($sQuery);
        $resultset->execute();
        $aErreur = $resultset->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            echo $sQuery . "<br>";
            die($aErreur[2]);
        } else {
            return $resultset->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    public function getBilanBySiren($sSiren) {
        $sQuery = "SELECT * FROM poi.siren_bilans WHERE siren =:siren";
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

    public function getBilanLiasseBySiren($sSiren) {
        echo "SELECT * FROM poi.siren_liasse WHERE siren ='$sSiren' AND liasse_code in ('FJ', '232') ORDER BY date_cloture_exercice desc limit 1<br>";
        $sQuery = "SELECT * FROM poi.siren_liasse WHERE siren =:siren AND liasse_code in ('FJ', '232') ORDER BY date_cloture_exercice desc limit 1";

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

    public function getCodeMotif($id_code) {
        //echo "CODE = ".$id_code."<br>";
        $sQuery = "SELECT libelle_code FROM poi.code_motif WHERE id_code =:id_code ";
        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);
        $sql->bindParam(':id_code', $id_code, PDO::PARAM_STR);
        $sql->execute();
        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            echo $sQuery . "<br>";
            die($aErreur[2]);
        } else {
            $aRes = $sql->fetchAll(PDO::FETCH_ASSOC);
        }

        return $aRes[0]['libelle_code'];
    }

    public function getIntituleLiasse($code_liasse) {

        if ($code_liasse == '232') {
            echo "SELECT * FROM poi.intitule_liasse 
                    WHERE code_liasse ='$code_liasse' and code_type_bilan='S'<br>";

            $sQuery = "SELECT * FROM poi.intitule_liasse 
                    WHERE code_liasse = :code_liasse and code_type_bilan='S'";
        } else {
            echo "SELECT * FROM poi.intitule_liasse 
                    WHERE code_liasse ='$code_liasse' and code_type_bilan='C'<br>";

            $sQuery = "SELECT * FROM poi.intitule_liasse 
                    WHERE code_liasse = :code_liasse and code_type_bilan='C'";
        }


        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);
        $sql->bindParam(':code_liasse', $code_liasse, PDO::PARAM_STR);
        $sql->execute();
        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            echo $sQuery . "<br>";
            die($aErreur[2]);
        } else {
            return $sql->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    /**
     * 
     */
    public function getInfosLiasse($num_page, $code_liasse) {
        $_numPage = '0' . $num_page;

        echo "select p.sous_type, p.m1, p.m2, p.m3, p.m4, p.code_type_bilan from poi.intitule_pages_bilan p, poi.intitule_liasse l
        where l.code_type_bilan = p.code_type_bilan and num_page ='$_numPage' and p.code_type_bilan='C'
        and code_liasse = '$code_liasse' <br>";

        $sQuery = "select p.sous_type, p.m1, p.m2, p.m3, p.m4, p.code_type_bilan from poi.intitule_pages_bilan p, poi.intitule_liasse l
        where l.code_type_bilan = p.code_type_bilan and num_page =:num_page and p.code_type_bilan='C'
        and code_liasse = :code_liasse";

        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);
        $sql->bindParam(':code_liasse', $code_liasse, PDO::PARAM_STR);
        $sql->bindParam(':num_page', $_numPage, PDO::PARAM_STR);
        $sql->execute();
        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            echo $sQuery . "<br>";
            die($aErreur[2]);
        } else {
            return $sql->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    public function getCodeGreffe($code_greffe) {
        $sQuery = "SELECT * FROM poi.code_greffe WHERE code_greffe =:code_greffe ";
        $db = $this->getConnexion();
        $sql = $db->prepare($sQuery);
        $sql->bindParam(':code_greffe', $code_greffe, PDO::PARAM_STR);
        $sql->execute();
        $aErreur = $sql->errorInfo();
        if (strlen($aErreur[2]) > 0) {
            echo $sQuery . "<br>";
            die($aErreur[2]);
        } else {
            return $sql->fetchAll(PDO::FETCH_ASSOC);
        }
    }

}
