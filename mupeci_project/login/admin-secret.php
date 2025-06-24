<?php
session_start();
require_once '../includes/functions.php';
require_once '../includes/auth.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['type'] !== 'admin') {
    header('Location: admin.php');
    exit();
}

$error = '';

if (!isset($_SESSION['captcha'])) {
    $_SESSION['captcha'] = rand(1000, 9999);
}
$captcha = $_SESSION['captcha'];

if ($_POST) {
    $secretCode = $_POST['secret_code'] ?? '';
    $captchaInput = $_POST['captcha'] ?? '';
    
    if ($captchaInput != $_SESSION['captcha']) {
        $error = 'Code CAPTCHA incorrect';
    } elseif (!verifySecretCode($secretCode)) {
        $error = 'Code secret incorrect';
    } else {
        $_SESSION['admin_verified'] = true;
        unset($_SESSION['captcha']); // Reset for next time
        header('Location: ../admin/dashboard.php');
        exit();
    }
    // Générer nouveau CAPTCHA après une erreur
    $_SESSION['captcha'] = rand(1000, 9999);
    $captcha = $_SESSION['captcha'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification Sécurisée - MUPECI</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="login-form">
            <div class="form-header">
                <h2>Vérification Sécurisée</h2>
                <p>Saisissez le code secret administrateur</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="secret_code">Code Secret</label>
                    <input type="password" id="secret_code" name="secret_code" required>
                </div>
                
                <div class="form-group">
                    <label for="captcha">Code CAPTCHA: <strong><?php echo $captcha; ?></strong></label>
                    <input type="text" id="captcha" name="captcha" required placeholder="Saisissez le code ci-dessus">
                </div>
                
                <button type="submit" class="btn btn-secondary btn-full">Vérifier</button>
            </form>
            
            <div class="login-help">
                <p><strong>Code secret de test :</strong> MUPECI2024</p>
            </div>
            
            <div class="back-link">
                <a href="admin.php">← Retour</a>
            </div>
        </div>
    </div>
</body>
</html>
