<?php
require 'db.php';
require 'vendor/autoload.php'; // Pour OTPHP
use OTPHP\TOTP;

$data = json_decode(file_get_contents('php://input'), true);

$username = trim($data['username'] ?? '');
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';
$public_key = $data['public_key'] ?? '';

// Vérification des données
if (!$username || !$email || !$password || !$public_key) {
    http_response_code(400);
    echo json_encode(['status'=>'error', 'message'=>'Données manquantes']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['status'=>'error', 'message'=>'Email invalide']);
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);

// Vérifie que le user/email n'existe pas
$stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
$stmt->execute([$username, $email]);
if ($stmt->fetch()) {
    http_response_code(409);
    echo json_encode(['status'=>'error', 'message'=>'Utilisateur ou email déjà pris']);
    exit;
}

// --- Génération du secret TOTP (2FA)
$totp = TOTP::create();
$twofa_secret = $totp->getSecret();
$qr_url = $totp->getQrCodeUri('Messagerie Sécurisée:'.$username);

// Création du compte
$stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, public_key, twofa_secret) VALUES (?, ?, ?, ?, ?)');
$stmt->execute([$username, $email, $hash, $public_key, $twofa_secret]);

echo json_encode([
    'status'=>'success',
    'twofa_qr_url' => $qr_url, // Pour afficher le QR côté client
]);
?>