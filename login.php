<?php
session_start();
require_once 'includes/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: home.php');
    exit;
}
$error = $_GET['error'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Vérifie si l'utilisateur existe
    $stmt = $pdo->prepare('SELECT id, password FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Authentification OK
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $username;
        header('Location: home.php');
        exit;
    } else {
        header('Location: login.php?error=Mauvais identifiants');
        exit;
    }
} else {
    header('Location: login.php');
    exit;
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
        <form action="login_process.php" method="post">
            <label for="username">Pseudo :</label>
            <input type="text" name="username" id="username" required>
            <label for="password">Mot de passe :</label>
            <input type="password" name="password" id="password" required>
            <button class="btn" type="submit">Se connecter</button>
        </form>
        <p>Pas encore de compte ? <a href="register.php">Inscription</a></p>
        <a class="btn small" href="index.php">Retour accueil</a>
    </div>
</body>
</html>