<?php
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'cookie_samesite' => 'Strict'
]);
require_once 'includes/db.php';

// Sécurité headers
header('Strict-Transport-Security: max-age=63072000; includeSubDomains; preload');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Content-Security-Policy: default-src \'self\';');

if (!empty($_SESSION['user_id'])) {
    header('Location: home.php');
    exit;
}

$error = $_GET['error'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF
    if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        header('Location: register.php?error=Session expirée, veuillez réessayer.');
        exit;
    }
    unset($_SESSION['csrf_token']);

    function sanitize($data) {
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Validation champs
    if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username) || strlen($password) < 4) {
        header('Location: register.php?error=Pseudo ou mot de passe invalide');
        exit;
    }
    if ($password !== $password_confirm) {
        header('Location: register.php?error=Les mots de passe ne correspondent pas');
        exit;
    }

    // Unicité pseudo
    $stmt = $pdo->prepare('SELECT id FROM users WHERE LOWER(username) = LOWER(?)');
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        header('Location: register.php?error=Ce pseudo existe déjà');
        exit;
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Génération clé chiffrement (256 bits)
    try {
        $encryption_key = base64_encode(random_bytes(32));
    } catch (Exception $e) {
        header('Location: register.php?error=Erreur génération clé');
        exit;
    }

    // Insertion utilisateur
    $stmt = $pdo->prepare('INSERT INTO users (username, password, encryption_key) VALUES (?, ?, ?)');
    try {
        $stmt->execute([$username, $hashedPassword, $encryption_key]);
        session_regenerate_id(true);
        $_SESSION['user_id'] = $pdo->lastInsertId();
        $_SESSION['username'] = $username;
        header('Location: home.php');
        exit;
    } catch (Exception $e) {
        header('Location: register.php?error=Erreur lors de l\'inscription');
        exit;
    }
}

// Génération d’un nouveau token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Xion – Inscription</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="assets/xion-auth.css">
</head>
<body>
<div class="xion-auth-container">
    <div class="xion-auth-panel">
        <h1 class="xion-auth-title">Xion</h1>
        <h2 class="xion-auth-subtitle">S’inscrire</h2>
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
                <input type="password" name="password" placeholder="Mot de passe" autocomplete="new-password" required>
            </div>
            <div class="xion-auth-input-group">
                <span class="xion-auth-icon">
                    <svg width="22" height="22" fill="none" stroke="#8b949e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="10" width="16" height="9" rx="2"/><path d="M7 10V7a4 4 0 0 1 8 0v3"/></svg>
                </span>
                <input type="password" name="password_confirm" placeholder="Confirmer mot de passe" autocomplete="new-password" required>
            </div>
            <button class="xion-auth-btn" type="submit">S’inscrire</button>
        </form>
        <div class="xion-auth-hint">
            <span class="xion-auth-icon">
                <svg width="20" height="20" fill="none" stroke="#8b949e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="10 2 2 7 2 17 10 22 18 17 18 7 10 2"/><path d="M10 13v-3"/></svg>
            </span>
            <span>Une clé de chiffrement sera générée pour protéger votre compte</span>
        </div>
        <div style="margin-top:1.5em;">
            <a href="login.php" class="xion-nav-link">Déjà inscrit ? Connexion</a>
        </div>
    </div>
</div>
</body>
</html>