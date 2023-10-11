<?php
require_once 'classes/connectPostgreSql.php';
set_time_limit(0);
$oConnectPG = new connectPostreSql();
$results= $oConnectPG->getNewSirenInListeSiren();
foreach($results as $result){
    $siren=$result["siren"];
    echo "<pre>" .$siren."</pre>";
    $oConnectPG->addNewSirenInListeSiren($siren);
}
echo "traitement terminé";
