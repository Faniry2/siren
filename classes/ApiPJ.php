<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ApiPJ
 *
 * @author sleco
 */
include 'UserAgent.php';

class ApiPJ {

    public function sendRequest($nom, $adresse, $siret) {

echo "http://192.168.1.130:8080/REST_API_PJGeosiren/geosiren.com/apipj/search?siret=" . $siret . "&nom=" . $nom . "&adresse=" . $adresse."\n";
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_PORT, "8080");

        curl_setopt($curl, CURLOPT_URL, "http://192.168.1.130:8080/REST_API_PJGeosiren/geosiren.com/apipj/search?siret=" . $siret . "&nom=" . $nom . "&adresse=" . $adresse);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, '');
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

        $headers = array();
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);


        $result = curl_exec($curl);
        $resultJSON = json_decode($result);
        //$resultJSON = $result;
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            //echo "pas erreur";
            return $resultJSON;
        }
    }

    public function sendRequest118($nom, $adresse) {

        $adresseHeader = str_replace("+", "%20", $adresse);
        $userAgent = UserAgent::random();

        //"http://www.118218.fr/recherche?what=. $nom .&category=expert-comptable%2C+commissaire++aux+comptes&address=4+RUE+DE+SEGONZAC&city_geo_id=16102&city=Cognac+%2816%29&state_geo_id=&state=&website=&phone=&distance=12&isAdvancedSearch=1&submit=";
        echo $adresseHeader . "<br>";
        echo "http://www.118218.fr/recherche?what=" . $nom . "&where=" . $adresse . "&distance=12\n";
        $curl = curl_init();


        curl_setopt($curl, CURLOPT_URL, "http://www.118218.fr/recherche?what=" . $nom . "&where=" . $adresse . "&distance=12");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, '');
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

        /* $headers = array(
          "accept-language: fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7",
          "cache-control: no-cache",
          "user-agent: ".$userAgent); */

        ///$headers = array();
        //$headers[] = 'Connection: keep-alive';
        //$headers[] = 'Pragma: no-cache';
        //$headers[] = 'Cache-Control: no-cache';
        //$headers[] = 'Upgrade-Insecure-Requests: 1';
        //$headers[] = 'User-agent: *';
        ///$headers[] = 'Disallow: /';
        //$headers[] = 'X-Requested-With : "XMLHttpRequest';
        //$headers[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3';
        //$headers[] = 'Host: www.118218.fr';
        //$headers[] = 'Referer: http://www.118218.fr/recherche?category_id=&geo_id=&distance=&category=&what=' . $nom . '&where='. $adresse .'&distance=10';
        //$headers[] = 'Accept-Encoding: gzip, deflate';
        //$headers[] = 'Upgrade-Insecure-Requests: 1';
        //$headers[] = 'X-CDN: Incapsula';
        //$headers[] = 'Accept-Language: fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7';



        $headers[] = 'Connection: keep-alive';
        $headers[] = 'Pragma: no-cache';
        $headers[] = 'Cache-Control: no-cache';
        $headers[] = 'Upgrade-Insecure-Requests: 1';
        $headers[] = 'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/73.0.3683.86 Safari/537.36';
        $headers[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3';
        $headers[] = 'Accept-Encoding: gzip, deflate';
        $headers[] = 'Accept-Language: fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7';

        $headers[] = 'Cookie: PHPSESSID=ag3cm0sug43pc2hqrkuqiic1k4; web_uuid=213.41.133.125_5c9b3d9ed37a09.93917839%3A%3AParis%2C+A8; visid_incap_204708=l3vo6x5sT9CizRoLiyVNWZ09m1wAAAAAQUIPAAAAAABbq7RiS0vanCsEwN8aLX4g; nlbi_204708=KngyWP/kVUsF0Sc2rmnmUQAAAAC60W1FgLluk/KrT/0dPGsq; incap_ses_729_204708=dw6qaokIwj1IWtJoh+4dCp49m1wAAAAAI48UlgDc5TaGT+uAIX29EA==; lstVstdCatgry=expert-comptable%2C%20commissaire%20%20aux%20comptes; lstSrchWhr=".$adresseHeader."; _ga=GA1.2.1075550846.1553677731; _gid=GA1.2.232145166.1553677731; wwp=' . $adresseHeader . '; __gads=ID=f5e343ffe9e35734:T=1553677729:S=ALNI_MbWvYK9EP7sZQ8014qKoBXn1mmRxQ';
        $headers[] = 'User-Agent: ' . $userAgent;






        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);


        $result = curl_exec($curl);
        //$resultJSON = json_decode($result);
        $resultJSON = $result;
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            //echo "pas erreur";
            return $resultJSON;
        }

        /* $curl = curl_init();

          curl_setopt_array($curl, array(
          CURLOPT_URL => "http://www.118218.fr/recherche?what=EXPERTISE+ET+TECHNIQUE+COMPTABLES&where=14+RUE+JEAN+JACQUES+ROUSSEAU+21500+MONTBARD&distance=9",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_POSTFIELDS => "",
          CURLOPT_HTTPHEADER => array(
          "Postman-Token: b7568cc6-252c-42e6-babc-df4ff6658fa8",
          "cache-control: no-cache"
          ),
          ));

          $response = curl_exec($curl);
          $err = curl_error($curl);

          curl_close($curl);

          if ($err) {
          echo "cURL Error #:" . $err;
          } else {
          echo $response;
          } */
    }

}
