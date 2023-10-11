<?php

/**
 * Created by PhpStorm.
 * User: sleco
 * Date: 23/10/2018
 * Time: 10:44
 */
class apiInsee {

    public function sendRequestDatemAJ($token) {

        $result = $this->sendRequest($token, "https://api.insee.fr/entreprises/sirene/V3/informations");

        $resultJSON = null;
        if ($result) {

            $resultJSON = json_decode($result);
        }
        return $resultJSON;
    }

    public function sendRequestSiret($token, $siret) {

        $result = $this->sendRequest($token, "https://api.insee.fr/entreprises/sirene/V3/siret/" . $siret);

        $resultJSON = null;
        if ($result) {

            $resultJSON = json_decode($result);
        }
        return $resultJSON;
    }

    public function getFormatDate($date) {
        // FORMAT DATE EN ENTREE 2018-10-25T00:48:35 -1 jour
        $aDate = explode('T', $date);
        $aDate1 = explode('-', $aDate[0]);
        $iAnnee = $aDate1[2] - 1;
        $sDateSTRING = strval($aDate1[0] . "-" . $aDate1[1] . "-" . $iAnnee);
        echo "date entrée = " . $date . " date sortie = " . $sDateSTRING . "<br>";
        return $sDateSTRING;
    }

    public function sendRequestLastMaj($token, $date, $sCurseur) {

        //echo "DATE  = ------------------------------- ".$date."<br>";
        //$result = $this->sendRequest($token, "https://api.insee.fr/entreprises/sirene/V3/siret?q=dateDernierTraitementEtablissement:".$date);
        //periode(etablissementSiege:false)
        echo "requete ==========================> https://api.insee.fr/entreprises/sirene/V3/siret?q=dateDernierTraitementEtablissement:" . $date . "&curseur=" . $sCurseur . "&nombre=4000<br>";
        //$result = $this->sendRequest($token, "https://api.insee.fr/entreprises/sirene/V3/siret?q=dateDernierTraitementEtablissement:".$date."&curseur=".$sCurseur."&nombre=100");
        $result = $this->sendRequest($token, "https://api.insee.fr/entreprises/sirene/V3/siret?q=dateDernierTraitementEtablissement:" . $date . "&curseur=" . $sCurseur . "&nombre=4000");

        $resultJSON = null;
        if ($result) {
            $resultJSON = json_decode($result);
        }
        return $resultJSON;
    }

    private function sendRequest($token, $url) {

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 0);

        $headers = array();
        $headers[] = "Authorization: Bearer " . $token;
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        //$result = curl_exec($ch);
        $result = utf8_encode(curl_exec($ch));
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
        return $result;
    }

    public function getJetonInsee() {

        $tab_jours = array(7, 1, 2, 3, 4, 5, 6);

        $inumJour = $tab_jours[date('w', mktime(0, 0, 0, date('m'), date('d'), date('Y')))];

        if ($inumJour % 2 == 0) {
            $client_id = CLE_INSEE_CONSO_1;
            $client_secret = CLE_INSEE_SECRET_1;
        } else {
            $client_id = CLE_INSEE_CONSO_2;
            $client_secret = CLE_INSEE_SECRET_2;
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://api.insee.fr/token");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
        curl_setopt($ch, CURLOPT_POST, 1);
        // AJOUT
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

        $headers = array();
        $headers[] = "Authorization: Basic " . base64_encode($client_id . ":" . $client_secret);
        $headers[] = "Content-Type: application/x-www-form-urlencoded";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        $resultJSON = json_decode($result);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        } else {
            echo 'Token récupéré avec succès !';
        }
        curl_close($ch);
        return $resultJSON;
    }

    public function revokeJetonInsee($jeton) {

        $tab_jours = array(7, 1, 2, 3, 4, 5, 6);

        $inumJour = $tab_jours[date('w', mktime(0, 0, 0, date('m'), date('d'), date('Y')))];

        if ($inumJour % 2 == 0) {
            $client_id = "4Tw90LpEBAFTAGSFga_0QvI9gNYa";
            $client_secret = "UleFhs44JLhFfYDSYJI4AXV1OYYa";
        } else {
            $client_id = "hKZPaT8JoqppdxfvHl_7LfYjMDAa";
            $client_secret = "rkgvaRzKV441kUjrFYl6aYfyzCka";
        }


        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://api.insee.fr/revoke');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "token=" . $jeton);
        curl_setopt($ch, CURLOPT_POST, 1);

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

        $headers = array();
        $headers[] = "Authorization: Basic " . base64_encode($client_id . ":" . $client_secret);
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        $resultJSON = json_decode($result);

        print_r($result);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        } else {
            echo 'Token révoké avec succès !';
        }
        curl_close($ch);
        return $resultJSON;
    }

    public function getInfosFromDate($token, $date, $curser = "*", $number = 100) {

        $ret = array();

        echo "https://api.insee.fr/entreprises/sirene/V3/siret?q=dateDernierTraitementEtablissement:" . $date . "&curseur=" . $curser . "&nombre=" . $number . "\n";


        $urlGET = "https://api.insee.fr/entreprises/sirene/V3/siret?q=dateDernierTraitementEtablissement:" . $date . "&curseur=" . $curser . "&nombre=" . $number;

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $urlGET,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_POSTFIELDS => "",
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer " . $token,
                "cache-control: no-cache"
            ),
        ));

        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        $ret['error'] = $err;
        $ret['code'] = $httpcode;
        $ret['response'] = $response;

        return $ret;
    }

    public function getUniteLegaleBySiren($sSiren, $sToken) {


        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.insee.fr/entreprises/sirene/V3/siren/844980771",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Accept: */*",
                "Authorization: Bearer ".$sToken,
                "Cache-Control: no-cache",
                "Connection: keep-alive",
                "Host: api.insee.fr",
                "Postman-Token: 676cd029-e2b2-44ad-bb83-2874d63921ec,594814b3-382a-4b9f-9001-b2d1589158ca",
                "User-Agent: PostmanRuntime/7.15.0",
                "accept-encoding: gzip, deflate",
                "cache-control: no-cache",
                "cookie: pdapimgateway=1830169354.22560.0000"
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
    
    
    public function getMajUlFromDate($token, $date, $curser = "*", $number = 100) {

        $ret = array();




        $urlGET = "https://api.insee.fr/entreprises/sirene/V3/siren?q=dateDernierTraitementUniteLegale:" . $date . "&curseur=" . $curser . "&nombre=" . $number;

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $urlGET,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_POSTFIELDS => "",
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer " . $token,
                "cache-control: no-cache"
            ),
        ));

        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        $ret['error'] = $err;
        $ret['code'] = $httpcode;
        $ret['response'] = $response;

        return $ret;
    }

}
