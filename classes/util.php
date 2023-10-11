<?php

//require_once('classes\PHPExcel-1.8\Classes\PHPExcel.php');
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class Util {

    public static $aArrayAnnee;

    public static function arrayDebug($aArray) {

        foreach ($aArray as $key => $value) {

            echo "<pre>";
            var_dump($aArray);
            echo "</pre>";
        }
    }

    function arrayDebugLimit($aArray, $iLimit) {

        foreach ($aArray as $key => $value) {
            echo "key = " . $key . " limit = 10 <br>";
            if ($key < $iLimit) {
                echo "<pre>";
                print_r($aArray);
                echo "</pre>";
            }
        }
    }

    function csvToArray($filename, $delimiter = ';') {

        $data = Array();

        $type = PHPExcel_IOFactory::identify($filename);
        $objReader = PHPExcel_IOFactory::createReader($type);
        $objPHPExcel = $objReader->load($filename);
        $rowIterator = $objPHPExcel->getActiveSheet()->getRowIterator();

        foreach ($rowIterator as $row) {
            $cellIterator = $row->getCellIterator();
            foreach ($cellIterator as $cell) {
                $data[$row->getRowIndex()][$cell->columnIndexFromString($cell->getColumn())] = $cell->getCalculatedValue();

                if ($cell->columnIndexFromString($cell->getColumn()) === 10 && $row->getRowIndex() > 1) {
                    //CONSTRUCTION DU TABLEAU CONTENANT LES DATES INDEX DEBUTE A 1 
                    self::$aArrayAnnee[$row->getRowIndex() - 1]['pan_code'] = $cell->getCalculatedValue();
                }
            }
        }
        return $data;
    }

    function txtToJson($filename, $delimiter = '   ') {

        $file = fopen($filename, "r");
        $i = 0;
        while (!feof($file)) {

            $line_of_text = fgets($file);
            $members = explode('    ', $line_of_text);
            fclose($file);
        }
        $contenu_json = json_encode($members);
        return $members;
    }

//    public static function recast($className, stdClass &$object) {
//        if (!class_exists($className))
//            throw new InvalidArgumentException(sprintf('Inexistant class %s.', $className));
//
//        $new = new $className();
//
//        foreach ($object as $property => &$value) {
//            $property = strtolower($property);
//            $new->$property = &$value;
//            unset($object->$property);
//        }
//        unset($value);
//        $object = (unset) $object;
//        return $new;
//    }

    public function compareModifsEtablissement($aEtabAPI, $aEtabStock) {

        //echo '-------------------------------------------------------------compareModifsEtablissement <br>';
        // TRANSFORME L'OBJET EN TABLEAU
        $aArrayAPi = self::objectToArray($aEtabAPI);
        // VA CONTENIR LES CLES QUI ON SUBI DES CHNAGEMENTS
        $aArrayResult = array();
        // CONTIENT LES CLES A VERIFIER
        $aArraySearch = array('activiteprincipaleetablissement', 'etatadministratifetablissement', 'numerovoieetablissement', 'typevoieetablissement', 'libellevoieetablissement', 'codepostaletablissement', 'libellecommuneetablissement', 'trancheeffectifsetablissement');

        if (isset($aEtabStock)) {

            foreach ($aEtabStock as $keyStok => $valueStock) {
                // BOUCLE SUR RESULTAT API MULTIDIMMENSION
                foreach ($aArrayAPi as $key => $value) {


                    // DEBUG
                    //if (strtolower($key) == $keyStok && $value != $valueStock) {                                                
                    //}
                    
                    if ($keyStok == "activiteprincipaleetablissement") {
                            $value = str_replace(".", "", $value);
                            $valueStock = str_replace(".", "", $valueStock);
                        }

                    // ON CHERCHE SI LES VALEURS SONT DIFFERENTES ET PRESENTES DANS $aArraySearch
                    if (strtolower($key) == $keyStok && in_array($key, $aArraySearch) && $value != $valueStock) {
                        echo "------------------------------------------MODIF = " . $keyStok . " valeurs => Insee " . $value . "/ Stock " . $valueStock . "\n";
                        array_push($aArrayResult, $keyStok);
                    }
                }
            }
        } else {
            echo "--------------------------------------LIGNE VIDE \n";
        }

        return $aArrayResult;
    }
    
    
    public function compareModifsEtablissementStock($aEtabAPI, $aEtabStock) {

       $sAdresse1 = $aEtabAPI['numvoie'] ." " .$aEtabAPI['typevoie']. " " . $aEtabAPI['nomvoie'] . " " . $aEtabAPI['codpost'] . " " . $aEtabAPI['villenorm'];
       $sAdresse2 = $aEtabStock['numerovoieetablissement'] ." " .$aEtabStock['typevoieetablissement']. " " . $aEtabStock['libellevoieetablissement'] . " " . $aEtabStock['codepostaletablissement'] . " " . $aEtabStock['libellecommuneetablissement'];

       if ($sAdresse1 != $sAdresse2) {
           return TRUE;
       } else {
           return FALSE;
       }
       
        
    }

    public static function objectToArray($obj, &$arr=null) {

        if (!is_object($obj) && !is_array($obj)) {
            $arr = $obj;
            return $arr;
        }

        foreach ($obj as $key => $value) {
            if (!empty($value)) {
                $arr[$key] = array();
                self::objectToArray($value, $arr[$key]);
            } else {
                $arr[$key] = $value;
            }
        }
        return $arr;
    }

    public function createJsonFileStat($oJson) {


        if($fp = @fopen("C:\Users\sleco\Documents\GEOSIRENE\stats.json", "a+")) {
            echo "ouverture du fichier";
        } else {
            echo "Impossible d'ouvrir le fichier";
        }





        /*echo "<pre>";
        var_dump($oJson);
        echo "</pre>";*/

        $oJsonResult = new stdClass();

        $oJsonResult->name = "France";

        $oJsonResult->children= array();


        foreach ($oJson as $object) {

            $oDepartement = new stdClass();
            $oDepartement->name = $object->dep;
            $oDepartement->count = $object->count;
            $oDepartement->siege = $object->etablissementsiege;

            $oRegion = new stdClass();
            $oRegion->name = $object->region;
            $oRegion->children = array();

            if (!in_array($oRegion,$oJsonResult->children )) {

               array_push( $oJsonResult->children , $oRegion);

               /*if (!in_array($oDepartement,$oRegion->children )) {
                    array_push( $oRegion->children , $oDepartement);
                }*/

            }

        }

        echo "<pre>";
        print_r($oJson);
        echo "</pre>";

       /* echo "<pre>";
       var_dump($oJson);
       echo "</pre>";*/
    }
    
    
    public static function logMajGeosirene($sTexte) {

//        $sDate = date('d/m/Y H:i:s');
//        $sFile = FILE_LOG_MAJ;
//        $fp = fopen($sFile, 'a+');
//        if ($fp) {
//            fwrite($fp, " operation = " . $sTexte);
//            fwrite($fp, "\n");
//           
//        }
    }
    
    public static function getTypeBilan($code) {
        
        $resultat = "";
        switch ($code) {
            case 'C':
                $resultat = "Compte annuel complet";
                break;
            case 'S':
                $resultat = "Compte annuel simplifié";
                break;
            case 'K':
                $resultat = "Compte annuel consolidé";
                break;
            case 'A':
                $resultat = "Compte annuel assurance";
                break;
            case 'B':
                $resultat = "Compte annuel banque";
                break;
        }
        
        return $resultat;
    }
    
    public static function formatDateFrancais($sDate) {

        //echo 'date = '.$sDate.'<br>';
         $iYear = substr($sDate, 0, 4);
        $iMonth = substr($sDate, 5, 2);
        $iDay = substr($sDate, 8, 4);
        
        return utf8_encode(strftime("%A %d %B %Y", mktime(0, 0, 0, $iMonth, $iDay, $iYear)));
    }

}
