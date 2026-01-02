<?php
/**
 * Page d'inscription
 */

session_start();

require_once 'includes/auth.php';

$error = '';
$success = '';

// Si déjà connecté, rediriger vers le dashboard
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs obligatoires.';
    } elseif ($password !== $password_confirm) {
        $error = 'Les mots de passe ne correspondent pas.';
    } elseif (strlen($password) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email invalide.';
    } else {
        if (register($username, $email, $password, $full_name)) {
            $success = 'Compte créé avec succès ! Vous pouvez maintenant vous connecter.';
        } else {
            $error = 'Erreur lors de la création du compte. Le nom d\'utilisateur ou l\'email existe déjà.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Dashboard Freelance</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-box">
            <h1>Créer un compte</h1>
            <p class="subtitle">Rejoignez la communauté</p>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <a href="login.php" class="btn btn-primary btn-block">Se connecter</a>
            <?php else: ?>
                <form method="POST" class="auth-form">
                    <div class="form-group">
                        <label for="username">Nom d'utilisateur *</label>
                        <input type="text" id="username" name="username" required autofocus>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="full_name">Nom complet</label>
                        <input type="text" id="full_name" name="full_name">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Mot de passe *</label>
                        <input type="password" id="password" name="password" required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label for="password_confirm">Confirmer le mot de passe *</label>
                        <input type="password" id="password_confirm" name="password_confirm" required minlength="6">
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">Créer le compte</button>
                </form>
                
                <p class="auth-link">
                    Déjà un compte ? <a href="login.php">Se connecter</a>
                </p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

