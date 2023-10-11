<?php

/**
 * Created by PhpStorm.
 * User: sleco
 * Date: 25/10/2018
 * Time: 11:20
 */
set_time_limit(0);

class apiBano {

    private function sendInformationBano() {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.insee.fr/entreprises/sirene/V3/informations",
            ///search/csv/
            /// CURLOPT_URL => "https://api-adresse.data.gouv.fr/search?q=".$param,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Postman-Token: d402ba56-221c-469d-87ab-65e230bafa9f",
                "cache-control: no-cache"
            ),
        ));

        $result = $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            echo $response;
        }
        return $result;
    }
    
    public function testBanoOK() {
        $param = '10+PAUL+ROCA+66000+PERPIGNAN';
        
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_PORT => "7878",
            CURLOPT_URL => ADR_BANO."search?q=".$param."&limit=1",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_POSTFIELDS => "",
            CURLOPT_HTTPHEADER => array(
                "Accept: */*",
                "Cache-Control: no-cache",
                "Connection: keep-alive",
                "Host: 192.168.1.202:7878",
                "Postman-Token: 7b6b20d6-5e95-474f-ab10-a5443b87e395,42d238b9-0c06-4380-8abd-3b92ebd31ab2",
                "User-Agent: PostmanRuntime/7.11.0",
                "accept-encoding: gzip, deflate",
                "cache-control: no-cache"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);


        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            return json_decode($response);
        }
    }

    public function sendRequest($param) {

        //echo ADR_BANO."search?q=" . $param . "\n";

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_PORT => "7878",
            CURLOPT_URL => ADR_BANO."search?q=".$param."&limit=1",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_POSTFIELDS => "",
            CURLOPT_HTTPHEADER => array(
                "Accept: */*",
                "Cache-Control: no-cache",
                "Connection: keep-alive",
                "Host: 192.168.1.202:7878",
                "Postman-Token: 7b6b20d6-5e95-474f-ab10-a5443b87e395,42d238b9-0c06-4380-8abd-3b92ebd31ab2",
                "User-Agent: PostmanRuntime/7.11.0",
                "accept-encoding: gzip, deflate",
                "cache-control: no-cache"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);


        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            //echo $response;
            return json_decode($response);
        }
        
    }

    function buildMultiPartRequest($ch, $boundary, $fields, $files) {
        $delimiter = '-------------' . $boundary;
        $data = '';

        foreach ($fields as $name => $content) {
            $data .= "--" . $delimiter . "\r\n"
                    . 'Content-Disposition: form-data; name="' . $name . "\"\r\n\r\n"
                    . $content . "\r\n";
        }


        foreach ($files as $name => $content) {
            $data .= "--" . $delimiter . "\r\n"
                    . 'Content-Disposition: form-data; name="' . $name . '"; filename="' . $content['fileName'] . '"' . "\r\n\r\n"
                    . $content['fileContent'] . "\r\n";
        }

        $data .= "--" . $delimiter . "--\r\n";
        //echo "DATA = ".$data."<br><br><br>";
        curl_setopt_array($ch, [
            CURLOPT_POST => TRUE,
            CURLOPT_HTTPHEADER => [
                'Content-Type: multipart/form-data; boundary=' . $delimiter,
                'Content-Length: ' . strlen($data)
            ],
            CURLOPT_POSTFIELDS => $data
        ]);

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        return $ch;
    }

    public function sendFileBano($sFichierEntree, $sFichierSortie) {

        error_reporting(E_ALL);
        ini_set('error_reporting', E_ALL);

        $ch = curl_init("http://192.168.1.70:7878/search/csv/");
        //$ch = curl_init("https://api-adresse.data.gouv.fr/search/csv/");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $this->buildMultiPartRequest($ch, uniqid(), ["columns" => "adresse"], ['data' => ['fileName' => $sFichierEntree, "fileContent" => file_get_contents($sFichierEntree)]]);
        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        if ($httpcode == 200) {
            file_put_contents($sFichierSortie, $result);
        } else {
            echo "Problème BANO <br>";
        }

        curl_close($ch);

        if (file_exists($sFichierSortie)) {
            return true;
        } else {
            return false;
        }
    }

    public function searchBano() {


        $sFileName = "C:/insee_file/sirc_pour_bano_CONCAT.csv";

        // Reporte toutes les erreurs PHP (Voir l'historique des modifications)
        error_reporting(E_ALL);

        // Même chose que error_reporting(E_ALL);
        ini_set('error_reporting', E_ALL);

        // Generated by curl-to-PHP: http://incarnate.github.io/curl-to-php/
        $ch = curl_init("https://api-adresse.data.gouv.fr/search/csv/");

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);


        $ch = $this->buildMultiPartRequest($ch, uniqid(), ["columns" => "adresse"], ['data' => ['fileName' => 'Clients_BPMED_pour_BANO.csv', "fileContent" => file_get_contents($sFileName)]]);


        $result = curl_exec($ch);

        $aResultTab = $liens = explode("\n", $result);
        //fgetcsv
        $fileNameResult = "C:/insee_file/sirc_pour_bano_reponse.csv";
        $delimiteur = ',';
        $fp = fopen($fileNameResult, 'w');

        foreach ($aResultTab as $ligne) {

            $aLigne = explode(',', $ligne);
            fputcsv($fp, $aLigne, ';', "*");
        }

        // fermeture du fichier csv
        fclose($fp);
        // REMPLACEMENT DES * MISES EN ENCLOSURE OBLIGATOIRE DE fputcsv
        $replace = str_replace("*", "", file_get_contents($fileNameResult));
        file_put_contents($fileNameResult, $replace);

        curl_close($ch);

        if (file_exists($fileNameResult)) {
            return true;
        } else {
            return false;
        }
    }

    public function searchBanoBPMED() {



        //$fileNameResult = "C:/insee_file/Clients_BPMED_pour_BANO_reponse.csv";
        $fileNameResult = "C:/insee_file/Clients_BPMED_pour_BANO_reponse.csv";
        $delimiteur = ',';



        for ($i = 0; $i < 50; $i++) {
            $sFileName = "C:/insee_file/BPMED/Clients_BPMED_complet_102018_pour_BANO_" . $i . ".csv";

            echo 'I => ' . $i . "<br>";
            //$sFileName = "C:/insee_file/BPMED/Clients_BPMED_complet_102018_pour_BANO_0.csv";

            echo $sFileName . '<br>';
            // Reporte toutes les erreurs PHP (Voir l'historique des modifications)
            error_reporting(E_ALL);

            // Même chose que error_reporting(E_ALL);
            ini_set('error_reporting', E_ALL);

            $ch = curl_init("https://api-adresse.data.gouv.fr/search/csv/");

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $ch = $this->buildMultiPartRequest($ch, uniqid(), ["columns" => "adresse"], ['data' => ['fileName' => 'Clients_BPMED_pour_BANO.csv', "fileContent" => file_get_contents($sFileName)]]);

            $result = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($httpcode == 200) {
                echo 'HTTP => 200<br>';
                file_put_contents($fileNameResult, $result, FILE_APPEND | LOCK_EX);
            } else {
                echo 'HTTP => != 200 ' . $i . "<br>";
                //$i--;
            }
            sleep(10);
        }
        curl_close($ch);

        if (file_exists($fileNameResult)) {
            return true;
        } else {
            return false;
        }
    }

    public function cutFichierPurBano() {
        $inumLigne = 0;
        $fp = fopen(FILE_RESULT_POUR_BANO, 'r+');

        $i = 0; //Compteur de ligne 
        $o = 0; // compteur de fichiers
        $bGetEnete = FALSE;

        //$fileNameResult = "C:/Users/sleco/Documents/GEOCODEUR/test_reponse_0.csv";

        $sEnete = "";

        while ($current_line = fgets($fp)) {

            $fileNameResult = REP_SORTIE_BANO . "test_reponse_bano" . $o . ".csv";
            $fpRes = fopen($fileNameResult, 'a+');

            // ON RECUEPERE LES ENTETES 
            if ($i == 0 && $o == 0) {
                $sEnete = $current_line;
            }
            echo $current_line . '<br>';
            // ON MET LES ENTETES A CHAQUE DEBUT DE FICHIER
            if ($i == 0 && $o > 0) {
                file_put_contents($fileNameResult, $sEnete, FILE_APPEND | LOCK_EX);
            }

            file_put_contents($fileNameResult, $current_line, FILE_APPEND | LOCK_EX);

            $i++;
            // CHANGE DE FICHIER ET ON REMET LES LIGNES A ZERO
            if ($i == 10000) {
                $o++;
                $i = 0;
            }
        }

        fclose($fp);
    }

    public function createFichierGeocode() {

        echo "------------------------createFichierGeocode<\n>";
        $this->sendFileBano(FILE_RESULT_POUR_BANO, FILE_SORTIE_BANO);
    }

    public function isCreatedFichierGeocoded() {

        echo "------------------------createFichierGeocode<\n>";
        return $this->sendFileBano(FILE_RESULT_POUR_BANO, FILE_SORTIE_BANO);
    }

}
