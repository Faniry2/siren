<?php

require_once 'classes/connectPostgreSql.php';

$oConnectPG = new connectPostreSql();
ini_set('max_execution_time', 0);

$sDate = strftime("%Y-%m-%d", mktime(0, 0, 0, date('m'), date('d'), date('Y')));

$conn_id = ftp_ssl_connect(ADR_FTP);
// Identification avec un nom d'utilisateur et un mot de passe
$login_result = ftp_login($conn_id, 'ftp_mada', 'Mada#44');
if (!$login_result) {
    die("can't login");
}


if (ftp_chdir($conn_id, "PJ")) {
    echo "Le dossier courant est maintenant : " . ftp_pwd($conn_id) . "\n";
} else { 
    echo "Impossible de changer de dossier\n";
}

if (ftp_get($conn_id, "E:/maj_geosirene/filepages_jaunes_$sDate.csv", "sirene_pour_pj_$sDate.csv", FTP_BINARY)) {
    echo "Le fichier  a été écrit avec succès\n";
} else {
    echo "Il y a un problème\n";
}

 /*if (ftp_delete($conn_id, "sirene_pour_pj.csv")) {
    echo "le fichier effacé avec succès\n";
  } else {
      echo "Impossible d'effacer le fichier $file\n";
}*/


if (($handle = fopen("E:/maj_geosirene/filepages_jaunes_$sDate.csv", "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $num = count($data);
       
        for ($c=0; $c < $num; $c++) {
            echo $data[$c] . "\n";

            $aLine = explode(";", $data[$c]);

            $siret = $aLine[0];
            $tel = str_replace('"', '', $aLine[1]);
            $scrore = $aLine[2];

            $oConnectPG->updateTelGeosireneSiret($siret, $tel, $scrore);
            $oConnectPG->insertTablePJ($siret, $tel, $scrore);
        }
    }
    fclose($handle);
}
unlink("E:/maj_geosirene/filepages_jaunes_$sDate.csv");
$oConnectPG->sendMailPagesJaunes();



