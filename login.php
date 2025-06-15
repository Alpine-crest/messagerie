<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/db.php';           // Connexion PDO d'abord !
require_once 'includes/security.php';     // (Optionnel) Mettre ici si dépend de la base

session_start([
    'cookie_httponly' => true,
    'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'cookie_samesite' => 'Strict'
]);

// Headers de sécurité
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

if (!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = 0;
if (!isset($_SESSION['last_login_attempt'])) $_SESSION['last_login_attempt'] = time();

if ($_SESSION['login_attempts'] >= 5 && (time() - $_SESSION['last_login_attempt'] < 180)) {
    die('Trop de tentatives, réessayez dans 3 minutes.');
}

// Met à jour last_active si déjà connecté
if (!empty($_SESSION['user_id'])) {
    $stmt = $pdo->prepare('UPDATE users SET last_active = NOW() WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    header('Location: home.php');
    exit;
}

$error = $_GET['error'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF
    if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header('Location: login.php?error=Session expirée, veuillez réessayer.');
        exit;
    }
    unset($_SESSION['csrf_token']);

    // Validation/sanitation
    function sanitize($data) {
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$username || !$password) {
        header('Location: login.php?error=Identifiants manquants');
        exit;
    }

    // Recherche utilisateur
    $stmt = $pdo->prepare('SELECT id, username, password FROM users WHERE LOWER(username) = LOWER(?)');
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Réinitialise la session (anti-fixation)
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['login_attempts'] = 0; // Reset tentative si succès
        header('Location: home.php');
        exit;
    } else {
        $_SESSION['login_attempts']++;
        $_SESSION['last_login_attempt'] = time();
        header('Location: login.php?error=Mauvais identifiants');
        exit;
    }
}

// Nouveau token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Xion – Connexion</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="assets/xion-auth.css">
</head>
<body>
<div class="xion-auth-container">
    <div class="xion-auth-panel">
        <h1 class="xion-auth-title">Xion</h1>
        <h2 class="xion-auth-subtitle">Se connecter</h2>
        <?php if (isset($error) && $error): ?>
        <div class="xion-auth-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <div class="xion-auth-input-group">
                <span class="xion-auth-icon">
                    <svg width="22" height="22" fill="none" stroke="#8b949e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="7" r="4"/><path d="M17 19a6 6 0 0 0-12 0"/></svg>
                </span>
                <input type="text" name="username" placeholder="Pseudo" autocomplete="username" required>
            </div>
            <div class="xion-auth-input-group">
                <span class="xion-auth-icon">
                    <svg width="22" height="22" fill="none" stroke="#8b949e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="10" width="16" height="9" rx="2"/><path d="M7 10V7a4 4 0 0 1 8 0v3"/></svg>
                </span>
                <input type="password" name="password" placeholder="Mot de passe" autocomplete="current-password" required>
            </div>
            <button class="xion-auth-btn" type="submit">Se connecter</button>
        </form>
        <div class="xion-auth-hint">
            <span class="xion-auth-icon">
                <svg width="20" height="20" fill="none" stroke="#8b949e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="8" width="14" height="9" rx="2"/><path d="M7 8V6a3 3 0 0 1 6 0v2"/></svg>
            </span>
            <span>Trop de tentatives entraînera un verrouillage</span>
        </div>
        <div style="margin-top:1.5em;">
            <a href="register.php" class="xion-nav-link">Créer un compte</a>
        </div>
    </div>
</div>
</body>
</html>