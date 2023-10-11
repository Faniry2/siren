<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <title>API BANO</title>
</head>
<body>

<div style="border-top: 200px;width: 100%; height: 100%;top: 50%; left: 50%;">

    <a href="index.php" >
        <div style="color:#eeeeee;height: 100px;text-align: center;background-color: #13A0B2;">Retour</div>
    </a>
    <br>

    <br>
    <!--<a href="outil.php" ><div style="color:#eeeeee; height: 100px;text-align: center;background-color: #66afe9;">Accès à outils</div></a>-->




</div>
<div>

    <?php
    
    
ini_set('memory_limit', '-1');
ini_set('upload_max_filesize', '2M');
ini_set('post_max_size', '3M');

    echo "Récupération SIRENE BANO <br>";


    include 'classes/apiBano.php';


    $oApiBano = new apiBano();
    if($oApiBano->searchBanoBPMED()) {
        echo "Le fichier géocodé a bien été généré";
    } else{
        echo "Le fichier géocodé n'a été généré";
    }

    ?>






</div>

</body>
</html>




<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

