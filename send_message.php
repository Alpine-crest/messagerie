<?php
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'cookie_samesite' => 'Strict'
]);
require_once 'includes/db.php';

// HTTP headers sécurité
header('Strict-Transport-Security: max-age=63072000; includeSubDomains; preload');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Content-Security-Policy: default-src \'none\';');

// Vérification authentification stricte
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Non autorisé');
}

// CSRF : token à usage unique, nouvelle génération après chaque envoi
if (
    empty($_POST['csrf_token']) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    http_response_code(403);
    exit('CSRF invalide');
}
unset($_SESSION['csrf_token']);

$user_id = $_SESSION['user_id'];
$contact_username = trim($_POST['to'] ?? '');
$plaintext = trim($_POST['message'] ?? '');

// Validation stricte
if (!$contact_username || !$plaintext || mb_strlen($plaintext) > 2000) {
    http_response_code(400);
    exit('Entrée invalide');
}

// Vérification que le destinataire est bien dans la liste des contacts (anti-spam)
$stmt = $pdo->prepare('SELECT u.id, u.encryption_key FROM users u JOIN contacts c ON u.id = c.contact_id WHERE u.username = ? AND c.user_id = ?');
$stmt->execute([$contact_username, $user_id]);
$contact = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$contact) {
    http_response_code(403);
    exit('Destinataire non autorisé');
}
$contact_id = $contact['id'];
$key = base64_decode($contact['encryption_key']);
if (strlen($key) !== 32) {
    http_response_code(500);
    exit('Clé de chiffrement invalide');
}

// Génère IV aléatoire, jamais réutilisé
$iv = random_bytes(16);

// Chiffre le message
$ciphertext = openssl_encrypt($plaintext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
if ($ciphertext === false) {
    http_response_code(500);
    exit('Erreur de chiffrement');
}

// Encode pour stockage
$ciphertext_b64 = base64_encode($ciphertext);
$iv_b64 = base64_encode($iv);

// Insertion SQL stricte, logs en cas d’échec
$stmt = $pdo->prepare('INSERT INTO messages (sender_id, receiver_id, content, iv) VALUES (?, ?, ?, ?)');
if (!$stmt->execute([$user_id, $contact_id, $ciphertext_b64, $iv_b64])) {
    error_log("Erreur DB message: " . print_r($pdo->errorInfo(), true));
    http_response_code(500);
    exit('Erreur base de données');
}

// Nouveau token CSRF pour prévenir le double submit
$new_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $new_token;

// Toujours renvoyer un JSON
header('Content-Type: application/json');
echo json_encode(['csrf_token' => $new_token]);
exit;
?>