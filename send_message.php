<?php
session_start();
require 'db.php';

// 1. Vérification connexion obligatoire
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['status'=>'error', 'message'=>'Non authentifié']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$receiver = $data['receiver'] ?? '';
$encrypted_message = $data['encrypted_message'] ?? '';

// 2. Empêcher caractères dangereux dans le pseudo du destinataire
if (!preg_match('/^[\w\.-]{2,}$/', $receiver)) {
    http_response_code(400);
    echo json_encode(['status'=>'error', 'message'=>'Pseudo destinataire invalide']);
    exit;
}

// 3. Anti-spam/phishing simple
function isPhishing($msg) {
    if (preg_match('/(http|https):\/\/[^\s]+/', $msg)) {
        if (preg_match('/(bit\.ly|tinyurl\.com|free|bitcoin|urgent|bank|paypal)/i', $msg)) {
            return true;
        }
    }
    if (preg_match('/(password|mot de passe|transfert|urgent|cliquer ici|gagner)/i', $msg)) {
        return true;
    }
    return false;
}
if (isPhishing($encrypted_message)) {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>'Message bloqué (suspect de phishing/spam)']);
    exit;
}

$stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
$stmt->execute([$receiver]);
$receiver_id = $stmt->fetchColumn();

if (!$receiver_id) {
    http_response_code(404);
    echo json_encode(['status'=>'error','message'=>'Destinataire inconnu']);
    exit;
}

$stmt = $pdo->prepare('INSERT INTO messages (sender_id, receiver_id, encrypted_message) VALUES (?, ?, ?)');
$stmt->execute([$_SESSION['user_id'], $receiver_id, htmlspecialchars($encrypted_message, ENT_QUOTES)]);
echo json_encode(['status'=>'success']);
?>