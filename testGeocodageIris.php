<?php
require_once 'classes/connectPostgreSql.php';
$oConnectPG = new connectPostreSql();
$numfic=2;
$oConnectPG->geocodageIris($numfic);