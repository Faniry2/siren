<?php
        include 'classes/apiInsee.php';
        include 'classes/apiBano.php';
        include 'classes/connectPostgreSql.php';
        include 'classes/geosireneTraitement.php';

        //$debug = FALSE;


        $oApiBano = new apiBano();
        $oApiInsee = new apiInsee();
        $oConnectPG = new connectPostreSql();
        $oUtil = new Util();
        $oGeosireneTraitement = new geosireneTraitement();




        date_default_timezone_set("Europe/Paris");
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');

        date_default_timezone_set('UTC');

        
        
        $oGeosireneTraitement->repareNomEtablissementNumFic(1);
        
        