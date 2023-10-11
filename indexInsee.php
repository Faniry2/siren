<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <title>API INSEE</title>
</head>
<body>
<div>



<?php
/**
 * Created by PhpStorm.
 * User: sleco
 * Date: 25/10/2018
 * Time: 11:50
 */

include 'classes/apiInsee.php';

$oApiInsee = new apiInsee();
$resultJSON = $oApiInsee->getJetonInsee();

?>

<form enctype="multipart/form-data" method="GET" action="getinseeinfos.php" name="formupload" id="formupload">

    <input type="hidden" name="token" value="<?php echo $resultJSON->access_token ; ?>" id="token"/>

    <input type="submit" name="envoyer" value="Envoyer le token"/>
</form>



<pre>
    <?php

    var_dump($resultJSON);

    ?>
</pre>






</div>

</body>
</html>