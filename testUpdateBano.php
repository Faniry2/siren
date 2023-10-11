<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require_once 'classes/connectPostgreSql.php';
$oConnectPG = new connectPostreSql();

$sDateJour = "2020-08-29";
$oConnectPG->updateStockGeoInsee($sDateJour);
$oConnectPG->updateStockFermeGeoInsee($sDateJour);
