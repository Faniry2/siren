<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require_once 'classes/connectPostgreSql.php';


$bdd = new connectPostreSql();

 $dateOld = strftime("%Y-%m-%d", mktime(0, 0, 0, date('m'), date('d'), date('Y')));
 
 echo $dateOld."\n";
 
 for ($i=1; $i<61; $i++) {
     
     $dateModif = date("Y-m-d", strtotime($dateOld . ' - ' . $i . 'day'));
     
     echo "NUMFIC = ".$i."\n";
     
     echo $dateModif."\n";
     
     $bdd->updateDateIntNumFic($dateModif, $i);
 }
