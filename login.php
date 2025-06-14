<?php
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'cookie_samesite' => 'Strict'
]);

require_once 'includes/db.php';

// Headers de sécurité
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

// Redirige si déjà connecté
if (isset($_SESSION['user_id'])) {
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
        header('Location: home.php');
        exit;
    } else {
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
    <title>Connexion - Messagerie</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container center small">
    <h2>Connexion</h2>
    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form action="login.php" method="post" autocomplete="off">
        <label for="username">Pseudo :</label>
        <input type="text" name="username" id="username" required>
        <label for="password">Mot de passe :</label>
        <input type="password" name="password" id="password" required>
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <button class="btn" type="submit">Se connecter</button>
    </form>
    <p>Pas encore de compte ? <a href="register.php">Inscription</a></p>
    <a class="btn small" href="index.php">Retour accueil</a>
</div>
</body>
</html>