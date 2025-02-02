<?php 
try {
    $bdd = new PDO( "mysql:host=localhost; dbname=sae_303_accidentologie", "root", "");
} catch (Exception $e) {
    die("Erreur de connexion à la base de données.");
}


?>