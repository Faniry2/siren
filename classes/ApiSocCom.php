<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ApiSocCom
 *
 * @author sleco
 */
class ApiSocCom {

    public function getInfos($siren) {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://societescraper.imarkahann.com/societe/siren/" . $siren,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Accept: */*",
                "Accept-Encoding: gzip, deflate",
                "Cache-Control: no-cache",
                "Connection: keep-alive",
                "Host: societescraper.imarkahann.com",
                "Postman-Token: bc283875-9bb8-459f-b50a-f8c3b4c23112,038bc21a-1d01-4dde-99a8-1f379cc75daa",
                "User-Agent: PostmanRuntime/7.17.1",
                "cache-control: no-cache"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            return $response;
        }
    }

    public function getInfos2($param) {

        $request = new HttpRequest();
        $request->setUrl('https://societescraper.imarkahann.com/societe/siren/'.$param);
        $request->setMethod(HTTP_METH_GET);

        $request->setHeaders(array(
            'cache-control' => 'no-cache',
            'Connection' => 'keep-alive',
            'Accept-Encoding' => 'gzip, deflate',
            'Host' => 'societescraper.imarkahann.com',
            'Postman-Token' => 'bc283875-9bb8-459f-b50a-f8c3b4c23112,d0ad51af-29fc-4fe1-af42-ad4a7c7806ea',
            'Cache-Control' => 'no-cache',
            'Accept' => '*/*',
            'User-Agent' => 'PostmanRuntime/7.17.1'
        ));

        try {
            $response = $request->send();

            echo $response->getBody();
        } catch (HttpException $ex) {
            return $ex;
        }
    }

}
