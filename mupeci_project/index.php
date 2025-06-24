<?php
session_start();
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MUPECI - Système de Gestion des Visiteurs</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="welcome-section">
            <div class="logo-container">
                <img src="assets/images/logo.png" alt="MUPECI Logo" class="logo">
                <h1>MUPECI</h1>
                <p class="subtitle">Système de Gestion des Visiteurs</p>
            </div>
            
            <div class="access-cards">
                <div class="access-card">
                    <div class="card-icon">👥</div>
                    <h3>Accès Réceptionniste</h3>
                    <p>Gestion quotidienne des visiteurs et de la file d'attente</p>
                    <a href="login/receptionist.php" class="btn btn-primary">Se Connecter</a>
                </div>
                
                <div class="access-card">
                    <div class="card-icon">⚙️</div>
                    <h3>Accès Administrateur</h3>
                    <p>Administration complète du système et gestion des utilisateurs</p>
                    <a href="login/admin.php" class="btn btn-secondary">Administration</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
