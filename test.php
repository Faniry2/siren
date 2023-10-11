<?php


require './classes/geosireneTraitement.php';
require './classes/connectPostgreSql.php';

$oG = new geosireneTraitement();

$sDateFormat = '2020-12-11';
$oG->sendMailAlerteQuotidienne($sDateFormat);

echo "FIN TRAITEMENT<br>";

die();
 $entityManager = $this->getEntityManager();

        $RAW_QUERY = "SELECT row_to_json(fc) AS geojson FROM (
            SELECT
            '200' As  Status,
            'OK' As StatusMessage ,
            'FeatureCollection' As type,
            array_to_json(array_agg(f)) As features
            FROM (SELECT 'Feature' As type,
                ST_AsGeoJSON(lg.the_geom_4326, 15, 1)::json As geometry,
                row_to_json((SELECT l FROM (SELECT dep, nom_dep) As l)) As properties
                FROM (SELECT
                        gid,
                        departement_geo.dep,
                        nom_dep,
                        the_geom_4326
                        FROM geo.departement_geo                                           
                        WHERE dep IN (:dep1)
                        ORDER BY dep
                ) lg
            ) As f
        ) As fc;";

        $statement = $entityManager->getConnection()->prepare($RAW_QUERY);
        // Set parameters
        $statement->bindValue(':dep1', '1,10');
        $statement->execute();

        return $statement->fetchAllAssociative();