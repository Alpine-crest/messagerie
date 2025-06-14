<?php
$host = 'localhost';
$dbname = 'messagerie';
$user = 'root';
$pass = ''; // Modifie selon ton installation

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
];
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, $options);
} catch (PDOException $e) {
    // Journalise mais ne révèle jamais en prod
    error_log("DB Connection error: " . $e->getMessage());
    die("Erreur de connexion à la base de données.");
}
?>