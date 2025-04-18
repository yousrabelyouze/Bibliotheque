<?php
$host = 'localhost'; // ou 127.0.0.1
$dbname = 'bibliotheque'; // nom de la base de données
$username = 'root'; // nom d'utilisateur MySQL
$password = ''; // mot de passe (laisse vide si pas de mot de passe)

// Créer la connexion avec MySQLi
$conn = new mysqli($host, $username, $password, $dbname);

// Vérifier la connexion
if ($conn->connect_error) {
    die("Erreur de connexion à la base de données : " . $conn->connect_error);
}

// Définir le jeu de caractères
$conn->set_charset("utf8");
?>
