<?php
session_start();
require 'db.php';
// 1. Vérification connexion obligatoire
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['status'=>'error','message'=>'Non authentifié']);
    exit;
}

$with = $_GET['with'] ?? '';
// 2. Empêcher caractères dangereux dans le pseudo demandé
if (!preg_match('/^[\w\.-]{2,}$/', $with)) {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>'Pseudo demandé invalide']);
    exit;
}

$stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
$stmt->execute([$with]);
$with_id = $stmt->fetchColumn();
if (!$with_id) {
    http_response_code(404);
    echo json_encode(['status'=>'error','message'=>'Utilisateur inconnu']);
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM messages WHERE 
    (sender_id = ? AND receiver_id = ?) OR 
    (sender_id = ? AND receiver_id = ?) 
    ORDER BY created_at ASC');
$stmt->execute([$_SESSION['user_id'], $with_id, $with_id, $_SESSION['user_id']]);
$messages = [];
while ($msg = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $msg['encrypted_message'] = htmlspecialchars($msg['encrypted_message'], ENT_QUOTES);
    $messages[] = $msg;
}
echo json_encode(['status'=>'success','messages'=>$messages]);
?>