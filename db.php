<?php
$pdo = new PDO(
    'mysql:host=localhost;dbname=messagerie;charset=utf8mb4',
    'root',
    '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
?>