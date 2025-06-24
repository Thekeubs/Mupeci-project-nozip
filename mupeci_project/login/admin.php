<?php
session_start();
require_once '../includes/functions.php';
require_once '../includes/auth.php';

$error = '';

if ($_POST) {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Debug: Check user in DB
    // require_once '../database/config.php';
    // $pdo = getDB();
    // $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    // $stmt->execute([$email]);
    // $user = $stmt->fetch(PDO::FETCH_ASSOC);
    // if ($user) {
    //     echo 'User found: ' . htmlspecialchars($user['email']) . ' | Type: ' . htmlspecialchars($user['user_type']) . ' | Active: ' . $user['active'] . '<br>';
    //     echo 'Hash: ' . $user['password'] . '<br>';
    //     echo 'password_verify: ' . (password_verify($password, $user['password']) ? 'OK' : 'NON') . '<br>';
    // } else {
    //     echo 'No user found for email: ' . htmlspecialchars($email) . '<br>';
    // }
    
    if (login($email, $password, 'admin')) {
        header('Location: admin-secret.php');
        exit();
    } else {
        $error = 'Identifiants administrateur incorrects';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Administrateur - MUPECI</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="login-form">
            <div class="form-header">
                <h2>Connexion Administrateur</h2>
                <p>Accès sécurisé à l'administration</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="email">Email Administrateur</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-secondary btn-full">Continuer</button>
            </form>
            
            <div class="login-help">
                <p><strong>Compte de test :</strong></p>
                <p>Email: admin@mupeci.com | Mot de passe: admin123</p>
            </div>
            
            <div class="back-link">
                <a href="../index.php">← Retour à l'accueil</a>
            </div>
        </div>
    </div>
</body>
</html>
