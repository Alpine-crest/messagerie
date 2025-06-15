<?php
$host = '127.0.0.1';
$dbname = 'messagerie';
$user = 'marius';
$pass = 'mon site s appelle xion'; // Modifie selon ton installation

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>