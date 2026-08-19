<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

//LOCAL
/*define('CLE_INSEE_CONSO_1', "KFIMGXmBw93yzqazfIA4KzALn_Ma");
define('CLE_INSEE_SECRET_1', "luYjdXh_pf2YbCc3cyx11upP77oa");

define('CLE_INSEE_CONSO_2', "MZ3pnGacDRUlf6vsbNE3zc4l9IMa");
define('CLE_INSEE_SECRET_2', "LKc65xwwifD8LY1Mjb7fEQOXDAsa");

define('DB_NAME_MY',"copropriete");
define('DB_HOST_MY',"localhost");
define('DB_USER_MY',"root");
define('DB_PASS_MY',"fm9enusp");
define('DB_PORT_MY',"3306");

define('DB_NAME',"geosirene");
define('DB_NAME_CUBE',"geocube");
define('DB_HOST',"localhost");
define('DB_USER',"postgres");
//define('DB_PASS',md5('SCRAM-SHA-256$4096:2zl/vyqUg8SCYQUGxJ84Cw==$jQTYUXudfra+H98WEA0Uz98EuikcCodArbF9dLvP1OA=:M8U3iH8AFaoeVAK7HtBGzlg4ls7k+sK6rR7vbzFHOJc='));
define('DB_PASS','pdr44pem77');
define('DB_PORT',"5432");


define('ADR_BANO',"http://192.168.1.68:7878/");

define('DB_HOST_PROD',"geomarketing-studio.com");
define('DB_USER_PROD',"postgres");
define('DB_PASS_PROD',"Gt24#Poe9681#37uHtR");
$sDate = strftime("%Y-%m-%d", mktime(0, 0, 0, date('m'), date('d'), date('Y')));
define('FILE_RESULT_POUR_PJ',"C:\\Users\\sleco\\Documents\\GEOSIRENE\\sirene_pour_pj_$sDate.csv");
define('FILE_RESULT_POUR_BANO',"C:\\Users\\sleco\\Documents\\GEOSIRENE\\sirene_pour_bano.csv");
define('FILE_SORTIE_BANO',"C:\\Users\\sleco\\Documents\\GEOSIRENE\\sirene_resultat_bano.csv");
define('REP_SORTIE_BANO',"C:\\Users\\sleco\\Documents\\GEOSIRENE\\");
define('FILE_LOG_MAJ',"C:\\Users\\sleco\\Documents\\GEOSIRENE\\log_maj_geosirene.csv");
define('ADR_FTP',"geomarketing-studio.com");*/

//PROD

define('ADR_BANO',"http://172.19.22.3:7878/");
define('CLE_INSEE_CONSO_1', "4Tw90LpEBAFTAGSFga_0QvI9gNYa");
define('CLE_INSEE_SECRET_1', "UleFhs44JLhFfYDSYJI4AXV1OYYa");
 
define('CLE_INSEE_CONSO_2',  "hKZPaT8JoqppdxfvHl_7LfYjMDAa");
define('CLE_INSEE_SECRET_2', "rkgvaRzKV441kUjrFYl6aYfyzCka");
define('DB_NAME',"geosirene");
define('DB_NAME_CUBE',"geocube");
define('DB_HOST',"54.36.247.53");
define('DB_USER',"postgres");
define('DB_PASS',"Gt24#Poe9681#37uHtR");
define('DB_PORT',"5432");
$sDate = strftime("%Y-%m-%d", mktime(0, 0, 0, date('m'), date('d'), date('Y')));
define('FILE_RESULT_POUR_PJ',"C:\\Users\\sleco\\Documents\\GEOSIRENE\\sirene_pour_pj_$sDate.csv");
define('FILE_RESULT_POUR_BANO',"E:\\maj_geosirene\\sirene_pour_bano.csv");
define('FILE_SORTIE_BANO',"E:\\maj_geosirene\\sirene_resultat_bano.csv");
define('FILE_LOG_MAJ',"E:\\maj_geosirene\\log_maj_geosirene.csv");
define('ADR_FTP',"192.168.1.120"); 


//DEV
/*
define('ADR_BANO',"http://192.168.1.68:7878/");
define('CLE_INSEE_CONSO_1', "KFIMGXmBw93yzqazfIA4KzALn_Ma");
define('CLE_INSEE_SECRET_1', "luYjdXh_pf2YbCc3cyx11upP77oa");
define('CLE_INSEE_CONSO_2', "MZ3pnGacDRUlf6vsbNE3zc4l9IMa");
define('CLE_INSEE_SECRET_2', "LKc65xwwifD8LY1Mjb7fEQOXDAsa");
define('DB_HOST_PROD',"geomarketing-studio.com");
define('DB_USER_PROD',"postgres");
define('DB_PASS_PROD',"Gt24#Poe9681#37uHtR");
define('DB_NAME',"geosirene");
define('DB_NAME_CUBE',"geocube");
define('DB_HOST',"192.168.1.110");
define('DB_USER',"postgres");
define('DB_PASS',"pdr44pem77");
define('DB_PORT',"5432");
$sDate = strftime("%Y-%m-%d", mktime(0, 0, 0, date('m'), date('d'), date('Y')));
define('FILE_RESULT_POUR_PJ',"E:\\maj_geosirene\\sirene_pour_pj_$sDate.csv");
define('FILE_RESULT_POUR_BANO',"E:\\maj_geosirene\\sirene_pour_bano.csv");
define('FILE_SORTIE_BANO',"E:\\maj_geosirene\\sirene_resultat_bano.csv");
define('FILE_LOG_MAJ',"E:\\maj_geosirene\\log_maj_geosirene.csv");
define('ADR_FTP',"geomarketing-studio.com"); */

class config {
    //put your code here
}
