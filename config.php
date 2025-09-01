<?php

// Infos de connexion
$db_host = "localhost";
$db_name = "successong";
$db_user = "fabrice";
$db_pass = "motdepassefort";

// Connexion PDO
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base : " . $e->getMessage());
}
?>
