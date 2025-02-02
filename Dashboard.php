<?php 

include("connexion.php");

//Donnees Importante
//Nbr Accident
$requete = $bdd->prepare("SELECT COUNT(Accident_Id) 
FROM sae303_accident;
");
$requete->execute();
$value = $requete->fetch(PDO::FETCH_ASSOC);


$nbr_accident= array_values($value)[0];

//Nbr Victime
$requete2 = $bdd->prepare("SELECT COUNT( DISTINCT id_usager) 
FROM sae303_usager;
");
$requete2->execute();
$value2 = $requete2->fetch(PDO::FETCH_ASSOC);

$nbr_victime=array_values($value2)[0];

//Nbr Mort
$requete3 = $bdd->prepare("SELECT COUNT(id_usager)  
FROM sae303_usager WHERE grav=2 ;
");
$requete3->execute();
$value3 = $requete3->fetch(PDO::FETCH_ASSOC);

$nbr_mort=array_values($value3)[0];
//Donnees Importante


//Donnees Secondaire
//Commune avec le plus d'accident
$requete4 = $bdd->prepare("SELECT COUNT(`Accident_Id`),LIBELLE FROM sae303_commune JOIN sae303_accident ON sae303_commune.com_id=sae303_accident.com_id GROUP BY `LIBELLE` ORDER BY COUNT(Accident_Id) DESC LIMIT 3;
");
$requete4->execute();
$value4 = $requete4->fetchAll(PDO::FETCH_ASSOC);
foreach ($value4 as $row) {
    $accident_commune[]= $row['COUNT(`Accident_Id`)'];
    $nom_commune[] = $row['LIBELLE'];  
}

//Obstacle les plus heurtés hors véhicules
$obstacleLabel=['1' => 'Véhicule en stationnement',
    '2' => 'Arbre',
    '3' => 'Glissière métallique',
    '4' => 'Glissière béton',
    '5' => 'Autre glissière',
    '6' => 'Bâtiment, mur, pile de pont',
    '7' => 'Support de signalisation verticale ou poste d’appel d’urgence',
    '8' => 'Poteau',
    '9' => 'Mobilier urbain',
    '10' => 'Parapet',
    '11' => 'Ilot, refuge, borne haute',
    '12' => 'Bordure de trottoir',
    '13' => 'Fossé, talus, paroi rocheuse',
    '14' => 'Autre obstacle fixe sur chaussée',
    '15' => 'Autre obstacle fixe sur trottoir ou accotement',
    '16' => 'Sortie de chaussée sans obstacle',
    '17' => 'Buse – tête d’aqueduc'];

$requete5 = $bdd->prepare("SELECT COUNT(`Num_Acc`),`obs` FROM `sae303_vehicule` GROUP BY `obs` ORDER BY COUNT(`Num_Acc`) DESC LIMIT 1,3");
$requete5->execute();
$value5 = $requete5->fetchAll(PDO::FETCH_ASSOC);
foreach ($value5 as $row) {
    $nbr_obstacle[] = $row['COUNT(`Num_Acc`)'];
    $obstacle[]=$obstacleLabel[(int)$row['obs']];
}

//nombre de piétons heurté
$requete6 = $bdd->prepare("SELECT COUNT(`obsm`) FROM `sae303_vehicule` WHERE `obsm`=1;");
$requete6->execute();
$value6 = $requete6->fetch(PDO::FETCH_ASSOC);
$nbr_pietons=array_values($value6)[0];

//Donnees Secondaire

//PieChart

$filter = $_GET['filter'];
$responsePie = ['labels' => [], 'data' => []];

    if($filter=== "sex"){
        // Données par sexe
        $requete7 =$bdd->prepare("SELECT COUNT(`Num_Acc`),sexe FROM `sae303_usager` GROUP BY `sexe`;");
        $requete7->execute();
        $value7 = $requete7->fetchAll(PDO::FETCH_ASSOC);
        $sexLabels = ['1' => 'Masculin', '2' => 'Féminin', '-1' => 'Non renseigné'];
        
        foreach ($value7 as $row) {
            $responsePie['labels'][] = $sexLabels[$row['sexe']];
            $responsePie['data'][] = (int) $row['COUNT(`Num_Acc`)']; 
        }
        
    }elseif ($filter === 'year') {
        // Données par année de naissance
        $requete8=$bdd->prepare("SELECT CASE WHEN 2022 - an_nais BETWEEN 18 AND 24 THEN '18-24' WHEN 2022 - an_nais BETWEEN 25 AND 34 THEN '25-34' WHEN 2022 - an_nais BETWEEN 35 AND 44 THEN '35-44' WHEN 2022 - an_nais BETWEEN 45 AND 54 THEN '45-54' WHEN 2022 - an_nais BETWEEN 55 AND 64 THEN '55-64' WHEN 2022 - an_nais > 60 THEN '65+' END AS cat_age , COUNT(*) AS count FROM sae303_usager GROUP BY cat_age ORDER BY cat_age LIMIT 1,6");
        $requete8->execute();
        $value8 = $requete8->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($value8 as $row) {
            $responsePie['labels'][] = $row['cat_age'];
            $responsePie['data'][] = (int) $row['count']; 
        }

    }
//LineChart
$responseLine = ['labels' => [], 'data' => []];

//Accidents par mois
$requete9 = $bdd->prepare("SELECT COUNT(Accident_Id), mois FROM sae303_accident GROUP BY mois;");
$requete9->execute();
$value9 = $requete9->fetchAll(PDO::FETCH_ASSOC);
$moisLabels = [1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'];
        foreach ($value9 as $row) {
            $responseLine['labels'][] = $moisLabels[(int)$row['mois']]; 
            $responseLine['data'][] =  $row['COUNT(Accident_Id)']; 
        }
    
//BarChart
$filter = $_GET['filter'];
$response = ['barChart' => ['labels' => [], 'data' => []]];

    //Données gravité accident selon vitesse
    if ($filter === 'vitesse') {    
        $requete10 = $bdd->prepare("SELECT COUNT(sae303_lieu.Num_Acc) AS nombre, vma, grav FROM sae303_lieu JOIN sae303_accident ON sae303_lieu.Num_Acc = sae303_accident.Accident_Id JOIN sae303_usager ON sae303_usager.Num_Acc = sae303_accident.Accident_Id WHERE `vma`!='-1' AND grav!='-1' GROUP BY vma,grav ORDER BY `sae303_lieu`.`vma` ASC;");
        $requete10->execute();
        $value10 = $requete10->fetchAll(PDO::FETCH_ASSOC);
        foreach ($value10 as $row) {
            $responseBar['labels'][] = $row['vma'];
            $responseBar['labels2'][] = $row['grav'];  
            $responseBar['data'][] =  $row['nombre']; 
        }
    }
    //Données gravité accident selon place dans véhicule
    if($filter ==='place'){
        $requete11= $bdd->prepare("SELECT usager.place AS place, usager.grav, COUNT(usager.Num_Acc) AS total_accidents, ROUND((COUNT(usager.Num_Acc) * 100) / total.total_accidents, 1) AS percentage FROM sae303_usager AS usager JOIN (SELECT place, COUNT(Num_Acc) AS total_accidents FROM sae303_usager WHERE grav != '-1' AND place IN (1,2,3,4,5) GROUP BY place) AS total ON usager.place = total.place WHERE usager.grav != '-1' AND usager.place != 10 GROUP BY usager.place, usager.grav ORDER BY usager.place ASC;");
        $requete11->execute();
        $value11 = $requete11->fetchAll(PDO::FETCH_ASSOC);
        foreach ($value11 as $row) {
            $responseBar['labels'][] = $row['place'];
            $responseBar['labels2'][] = $row['grav'];  
            $responseBar['data'][] =  $row['percentage'];
        }
        
    }
$vehiculeLabels = [
    '0' => 'Indéterminable',
    '1' => 'Bicyclette',
    '2' => 'Cyclomoteur <50cm3',
    '3' => 'Voiturette (Quadricycle à moteur carrossé)',
    '4' => 'Référence inutilisée depuis 2006 (scooter immatriculé)',
    '5' => 'Référence inutilisée depuis 2006 (motocyclette)',
    '6' => 'Référence inutilisée depuis 2006 (side-car)',
    '7' => 'VL seul',
    '8' => 'Référence inutilisée depuis 2006 (VL + caravane)',
    '9' => 'Référence inutilisée depuis 2006 (VL + remorque)',
    '10' => 'VU seul 1,5T <= PTAC <= 3,5T avec ou sans remorque',
    '11' => 'Référence inutilisée depuis 2006 (VU (10) + caravane)',
    '12' => 'Référence inutilisée depuis 2006 (VU (10) + remorque)',
    '13' => 'PL seul 3,5T < PTAC <= 7,5T',
    '14' => 'PL seul > 7,5T',
    '15' => 'PL > 3,5T + remorque',
    '16' => 'Tracteur routier seul',
    '17' => 'Tracteur routier + semi-remorque',
    '18' => 'Référence inutilisée depuis 2006 (transport en commun)',
    '19' => 'Référence inutilisée depuis 2006 (tramway)',
    '20' => 'Engin spécial',
    '21' => 'Tracteur agricole',
    '30' => 'Scooter < 50 cm3',
    '31' => 'Motocyclette > 50 cm3 et <= 125 cm3',
    '32' => 'Scooter > 50 cm3 et <= 125 cm3',
    '33' => 'Motocyclette > 125 cm3',
    '34' => 'Scooter > 125 cm3',
    '35' => 'Quad léger <= 50 cm3',
    '36' => 'Quad lourd > 50 cm3',
    '37' => 'Autobus',
    '38' => 'Autocar',
    '39' => 'Train',
    '40' => 'Tramway',
    '41' => '3RM <= 50 cm3',
    '42' => '3RM > 50 cm3 <= 125 cm3',
    '43' => '3RM > 125 cm3',
    '50' => 'EDP à moteur',
    '60' => 'EDP sans moteur',
    '80' => 'VAE',
    '99' => 'Autre véhicule'
];
    
function getVehiculeLabels($codes) {
    global $vehiculeLabels; 
    $codesArray = explode(',', $codes);
    $carteLabels = [];
    foreach ($codesArray as $code) {
        $carteLabels[] = $vehiculeLabels[$code];  
    }
    return implode(', ', $carteLabels);
}
//Données pour la carte
$requete12=$bdd->prepare("SELECT `Accident_Id`, CONCAT(`jour`, '/', `mois`, '/', `an`) AS date_accident, COALESCE(`lat`, 'Non renseigné') AS latitude, COALESCE(`long`, 'Non renseigné') AS longitude, IF(`adr` = 'n/a', 'Non renseigné', `adr`) AS rue, GROUP_CONCAT(DISTINCT IFNULL(`catv`, 'Non renseigné')) AS type_vehicule, IF(`vma` = -1, 'Non renseigné', `vma`) AS vma, GROUP_CONCAT(DISTINCT CASE WHEN `grav` = 1 THEN 'Indemne' WHEN `grav` = 2 THEN 'Tué' WHEN `grav` = 3 THEN 'Hospitalisé' WHEN `grav` = 4 THEN 'Blessé léger' ELSE 'Non renseigné' END ) AS etat FROM sae303_accident LEFT JOIN sae303_lieu ON `Accident_Id` = sae303_lieu.`Num_Acc` LEFT JOIN sae303_vehicule ON `Accident_Id` = sae303_vehicule.`Num_Acc` LEFT JOIN sae303_usager ON `Accident_Id` = sae303_usager.`Num_Acc` GROUP BY `Accident_Id`, `vma`");
$requete12->execute();
$value12 = $requete12->fetchAll(PDO::FETCH_ASSOC);

    foreach ($value12 as $row) {
        $responseCarte[] = [
    'date_accident' => $row['date_accident'],
    'latitude' => str_replace(',', '.', $row['latitude']), // Remplace les virgules par des points
    'longitude' => str_replace(',', '.', $row['longitude']), 
    'rue' => $row['rue'],
    'type_vehicule' => getVehiculeLabels($row['type_vehicule']),
    'vma' => $row['vma'],
    'etat' => $row['etat']
];

    }

    $response = [
        'nbr_accident'=>$nbr_accident,
        'nbr_victime'=>$nbr_victime,
        'nbr_mort'=>$nbr_mort,
        'accident_commune'=>$accident_commune,
        'nom_commune'=>$nom_commune,
        'nbr_obstacle'=>$nbr_obstacle,
        'obstacle'=>$obstacle,
        'nbr_pietons'=>$nbr_pietons,
        'pieChart' => $responsePie, 
        'lineChart' => $responseLine, 
        'barChart' =>$responseBar,
        'carteChart'=>$responseCarte
    ];
        

header('Content-Type: application/json');
echo json_encode(value: $response);




?>
