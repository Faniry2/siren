<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ConnectGeocube
 *
 * @author sleco
 */
//include 'config.php';
//include 'util.php';


class ConnectGeocube {

    public static $oConnexion;

    function getConnexion() {

        if (!self::$oConnexion) {
            try {
                $dbName = DB_NAME_CUBE;
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

    public function getSirenN0BySiret($sSiret) {

        $sQuery = "SELECT * FROM poi.sirene_n0 WHERE siret =:siret";
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

}
