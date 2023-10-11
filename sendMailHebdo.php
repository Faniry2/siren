<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

error_reporting(E_ALL & ~E_NOTICE);
include 'classes/apiInsee.php';
include 'classes/apiBano.php';
include 'classes/connectPostgreSql.php';
include 'classes/geosireneTraitement.php';
include 'classes/ConnectGeocube.php';

$oApiBano = new apiBano();
$oApiInsee = new apiInsee();
$oConnectPG = new connectPostreSql();
$oUtil = new Util();
$oGeosireneTraitement = new geosireneTraitement();
$oGeocube = new ConnectGeocube();

date_default_timezone_set("Europe/Paris");
ini_set('max_execution_time', 0);
ini_set('memory_limit', '-1');
date_default_timezone_set('UTC');
$sDateFormat = strftime("%d-%m-%Y", mktime(0, 0, 0, date('m'), date('d')-1, date('Y')));
$sDateJour = strftime("%d-%m-%Y", mktime(0, 0, 0, date('m'), date('d'), date('Y')));
$sDateSemDern = strftime("%d-%m-%Y", mktime(0, 0, 0, date('m'), date('d') - 8, date('Y')));
$oGeosireneTraitement->sendMailAlerteHebdo($sDateFormat, $sDateJour, $sDateSemDern);
