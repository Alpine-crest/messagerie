<?php
session_start();
require_once 'includes/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: home.php');
    exit;
}
$error = $_GET['error'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Sécurité : vérifie que username n'est pas vide et pas trop court
    if (strlen($username) < 3 || strlen($password) < 4) {
        header('Location: register.php?error=Pseudo ou mot de passe trop court');
        exit;
    }

    // Vérifie si l'utilisateur existe déjà
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        header('Location: register.php?error=Ce pseudo existe déjà');
        exit;
    }

    // Hash du mot de passe (très important pour la sécurité)
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insérer le nouvel utilisateur
    $stmt = $pdo->prepare('INSERT INTO users (username, password) VALUES (?, ?)');
    if ($stmt->execute([$username, $hashedPassword])) {
        // Se connecter automatiquement après inscription
        $_SESSION['user_id'] = $pdo->lastInsertId();
        $_SESSION['username'] = $username;
        header('Location: home.php');
        exit;
    } else {
        header('Location: register.php?error=Erreur lors de l\'inscription');
        exit;
    }
} else {
    header('Location: register.php');
    exit;
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
        <form action="register_process.php" method="post">
            <label for="username">Choisis un pseudo :</label>
            <input type="text" name="username" id="username" required>
            <label for="password">Mot de passe :</label>
            <input type="password" name="password" id="password" required>
            <button class="btn" type="submit">S'inscrire</button>
        </form>
        <p>Déjà un compte ? <a href="login.php">Connexion</a></p>
        <a class="btn small" href="index.php">Retour accueil</a>
    </div>
</body>
</html>