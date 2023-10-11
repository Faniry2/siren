<?php
/**
 * Created by PhpStorm.
 * User: germain
 * Date: 22/10/2018
 * Time: 21:10
 */


$client_id = "hKZPaT8JoqppdxfvHl_7LfYjMDAa";
$client_secret = "rkgvaRzKV441kUjrFYl6aYfyzCka";


$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, "https://api.insee.fr/token");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
curl_setopt($ch, CURLOPT_POST, 1);
// AJOUT
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

$headers = array();
$headers[] = "Authorization: Basic ".base64_encode($client_id.":".$client_secret);
$headers[] = "Content-Type: application/x-www-form-urlencoded";
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$result = curl_exec($ch);
$resultJSON = json_decode($result);
if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
}
curl_close ($ch);



?>


<h1>TOKEN INFOS</h1>

<pre>

    <?php


    var_dump($resultJSON);







    ?>


</pre>



