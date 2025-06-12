<?php
require 'db.php';
$username = $_GET['username'] ?? '';
$stmt = $pdo->prepare('SELECT public_key FROM users WHERE username = ?');
$stmt->execute([$username]);
$key = $stmt->fetchColumn();
if ($key) echo json_encode(['status'=>'success','public_key'=>$key]);
else echo json_encode(['status'=>'error','message'=>"Utilisateur inconnu"]);
?>