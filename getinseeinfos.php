<!doctype html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport"
              content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title>GET INSEE INFOS</title>
    </head>
    <body>


        <?php
        include 'classes/apiInsee.php';
        include 'classes/apiBano.php';
        include 'classes/connectPostgreSql.php';
        include 'classes/geosireneTraitement.php';



        $oApiBano = new apiBano();
        $oApiInsee = new apiInsee();
        $oConnectPG = new connectPostreSql();
        $oUtil = new Util();
        $oGeosireneTraitement = new geosireneTraitement();

        date_default_timezone_set("Europe/Paris");
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');

        date_default_timezone_set('UTC');

        // Start date
        //$sDateFormat = '2018-11-12';
        // End date
        //$end_date = '2018-11-26';

        $numfic = 1;
        $sDateFormat = strftime("%Y-%m-%d", mktime(0, 0, 0, date('m'), date('d')-1, date('Y'))); 
        
        $oGeosireneTraitement->nettoyageGeosirene();
        //die();

        // ON RECUPERE LE JETON POUR L'API INSEE
        $resultJSON = $oApiInsee->getJetonInsee();

        //while (strtotime($sDateFormat) <= strtotime($end_date)) {

        unlink(FILE_RESULT_POUR_BANO);
        unlink(FILE_SORTIE_BANO);

        //$sDateFormat = date("Y-m-d", strtotime("+1 day", strtotime($sDateFormat)));
        echo "*********************DATE => " . $sDateFormat . "**************************<br>";

        // INSERT EN TABLE TEMPORAIRE + CREE LE FICHIER GEOCODE
        $oGeosireneTraitement->etape1($resultJSON, $sDateFormat);

        $oGeosireneTraitement->etape2($sDateFormat, $numfic);
            //$numfic++;
        //}
        $oGeosireneTraitement->exportTableGeosirene();


        ?>



    </body>
</html>