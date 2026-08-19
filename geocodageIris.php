<?php
error_reporting(E_ALL & ~E_NOTICE);
require_once 'classes/connectPostgreSql.php';

$oConnectPG = new connectPostreSql();
$oConnectPG->geocodeIrisAndUpdateTableGeosirenHaveToIris();
