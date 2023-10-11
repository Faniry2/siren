<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

error_reporting(E_ALL & ~E_NOTICE);
require_once 'classes/apiInsee.php';
require_once 'classes/apiBano.php';
require_once 'classes/connectPostgreSql.php';
require_once 'classes/geosireneTraitement.php';
require_once 'classes/ConnectGeocube.php';
require_once 'classes/remplirDenoGeoscar.php';
require_once  'SearchPj.php';

$oDenoGeoscar = new RemplirDenoGeoscar();



$oDenoGeoscar->traitementDeno();