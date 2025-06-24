<?php
session_start();
require_once '../includes/functions.php';
require_once '../includes/auth.php';

$error = '';

if ($_POST) {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    require_once '../database/config.php';
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        // Debug lines removed
    }
    
    if (login($email, $password, 'receptionist')) {
        header('Location: ../receptionist/reception.php');
        exit();
    } else {
        $error = 'Identifiants incorrects ou compte désactivé';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Réceptionniste - MUPECI</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="login-form">
            <div class="form-header">
                <h2>Connexion Réceptionniste</h2>
                <p>Accédez à votre espace de travail</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="email">Nom ou Email</label>
                    <input type="text" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-full">Se Connecter</button>
            </form>
            
            <div class="login-help">
                <p><strong>Comptes de test :</strong></p>
                <p>Email: marie@mupeci.com | Mot de passe: password123</p>
                <p>Email: jean@mupeci.com | Mot de passe: password123</p>
            </div>
            
            <div class="back-link">
                <a href="../index.php">← Retour à l'accueil</a>
            </div>
        </div>
    </div>
</body>
</html>
