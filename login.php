<?php
session_start();
require 'db.php';
require 'vendor/autoload.php';
use OTPHP\TOTP;

// 1. Empêche de se connecter si déjà connecté
if (isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['status'=>'error', 'message'=>'Déjà connecté. Déconnectez-vous d\'abord.']);
    exit;
}

// 2. Protection brute-force
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = 0;
if ($_SESSION['login_attempts'] >= 5) {
    if (empty($data['captcha']) || $data['captcha'] !== ($_SESSION['captcha_ans'] ?? '')) {
        $a = rand(1,9); $b = rand(1,9);
        $_SESSION['captcha_ans'] = (string)($a+$b);
        http_response_code(429);
        echo json_encode([
            'status'=>'error',
            'message'=>'Trop de tentatives, résolvez le captcha',
            'captcha_question'=>"Combien font $a + $b ?"
        ]);
        exit;
    }
}

$login = trim($data['login'] ?? '');
$password = $data['password'] ?? '';
$totp_code = $data['totp'] ?? '';

// 3. Empêche les caractères dangereux dans le login
if (!preg_match('/^[\w@\.-]{2,}$/', $login)) {
    http_response_code(400);
    echo json_encode(['status'=>'error', 'message'=>'Login invalide']);
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? OR email = ?');
$stmt->execute([$login, $login]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($password, $user['password_hash'])) {
    // 2FA
    if ($user['twofa_secret']) {
        $totp = TOTP::create($user['twofa_secret']);
        if (!$totp->verify($totp_code)) {
            $_SESSION['login_attempts']++;
            http_response_code(401);
            echo json_encode(['status'=>'error', 'message'=>'Code 2FA invalide']);
            exit;
        }
    }
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['login_attempts'] = 0;
    echo json_encode(['status'=>'success', 'username'=>htmlspecialchars($user['username'],ENT_QUOTES),'public_key'=>$user['public_key']]);
} else {
    $_SESSION['login_attempts']++;
    http_response_code(401);
    echo json_encode(['status'=>'error', 'message'=>'Identifiants invalides']);
}
?>