<?php
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'cookie_samesite' => 'Strict'
]);

if (!empty($_SESSION['user_id'])) {
    $stmt = $pdo->prepare('UPDATE users SET last_active = NOW() WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
}

require_once 'includes/db.php';

// Affichage d'une erreur éventuelle
$error = $_GET['error'] ?? '';

// Headers sécurité envoyés dès le départ
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Protection CSRF
    if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header('Location: register.php?error=Session expirée, veuillez réessayer.');
        exit;
    }
    unset($_SESSION['csrf_token']);

    // Validation & nettoyage
    function sanitize($data) {
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (strlen($username) < 3 || strlen($password) < 4) {
        header('Location: register.php?error=Pseudo ou mot de passe trop court');
        exit;
    }

    // Vérifie si le pseudo existe déjà (case-insensitive)
    $stmt = $pdo->prepare('SELECT id FROM users WHERE LOWER(username) = LOWER(?)');
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        header('Location: register.php?error=Ce pseudo existe déjà');
        exit;
    }

    // Hash du mot de passe
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Génération clé de chiffrement OpenSSL (256 bits en base64)
    try {
        $encryption_key = base64_encode(openssl_random_pseudo_bytes(32));
    } catch (Exception $e) {
        header('Location: register.php?error=Erreur de génération de la clé de sécurité');
        exit;
    }

    // Insertion utilisateur
    $stmt = $pdo->prepare('INSERT INTO users (username, password, encryption_key) VALUES (?, ?, ?)');
    try {
        $stmt->execute([$username, $hashedPassword, $encryption_key]);
        $_SESSION['user_id'] = $pdo->lastInsertId();
        $_SESSION['username'] = $username;
        header('Location: home.php');
        exit;
    } catch (Exception $e) {
        header('Location: register.php?error=Erreur lors de l\'inscription');
        exit;
    }
}

// Génération d’un nouveau token CSRF à chaque affichage du formulaire
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription - Messagerie</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container center small">
        <h2>Inscription</h2>
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form action="register.php" method="post" autocomplete="off">
            <label for="username">Choisis un pseudo :</label>
            <input type="text" name="username" id="username" required>
            <label for="password">Mot de passe :</label>
            <input type="password" name="password" id="password" required>
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <button class="btn" type="submit">S'inscrire</button>
        </form>
        <p>Déjà un compte ? <a href="login.php">Connexion</a></p>
        <a class="btn small" href="index.php">Retour accueil</a>
    </div>
</body>
</html>