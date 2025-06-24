<?php
// Script d'installation amélioré pour XAMPP
require_once 'config.php';

// Style CSS pour une meilleure présentation
echo "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Installation MUPECI - Base de données</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .success { color: #22c55e; font-weight: bold; }
        .error { color: #dc2626; font-weight: bold; }
        .warning { color: #f97316; font-weight: bold; }
        .info { color: #3b82f6; }
        .step { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 8px; border-left: 4px solid #22c55e; }
        .error-step { border-left-color: #dc2626; background: #fef2f2; }
        pre { background: #f1f5f9; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .btn { display: inline-block; padding: 10px 20px; background: #22c55e; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .btn:hover { background: #16a34a; }
    </style>
</head>
<body>";

echo "<h1>🚀 Installation de la base de données MUPECI</h1>\n";

// Étape 1 : Vérification de XAMPP
echo "<div class='step'>";
echo "<h2>Étape 1 : Vérification de XAMPP</h2>";

try {
    // Test de connexion basique à MySQL
    $testConnection = @new PDO('mysql:host=localhost;port=3306', 'root', '');
    echo "<p class='success'>✅ MySQL XAMPP est accessible</p>";
    
    // Informations sur MySQL
    $stmt = $testConnection->query("SELECT VERSION() as version");
    $version = $stmt->fetch();
    echo "<p class='info'>📊 Version MySQL : " . $version['version'] . "</p>";
    
} catch (PDOException $e) {
    echo "<div class='error-step'>";
    echo "<p class='error'>❌ Impossible de se connecter à MySQL XAMPP</p>";
    echo "<p><strong>Solutions :</strong></p>";
    echo "<ul>";
    echo "<li>Vérifiez que XAMPP est démarré</li>";
    echo "<li>Vérifiez que MySQL est en cours d'exécution (vert dans XAMPP Control Panel)</li>";
    echo "<li>Redémarrez XAMPP si nécessaire</li>";
    echo "</ul>";
    echo "<p class='error'>Erreur technique : " . $e->getMessage() . "</p>";
    echo "</div></div></body></html>";
    exit;
}
echo "</div>";

// Étape 2 : Vérification/Création de la base de données
echo "<div class='step'>";
echo "<h2>Étape 2 : Base de données</h2>";

try {
    // Vérifier si la base existe
    $stmt = $testConnection->query("SHOW DATABASES LIKE 'mupeci_db'");
    if ($stmt->rowCount() == 0) {
        echo "<p class='warning'>⚠️ Base de données 'mupeci_db' n'existe pas</p>";
        echo "<p>Création de la base de données...</p>";
        
        $testConnection->exec("CREATE DATABASE mupeci_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "<p class='success'>✅ Base de données 'mupeci_db' créée avec succès</p>";
    } else {
        echo "<p class='success'>✅ Base de données 'mupeci_db' existe déjà</p>";
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ Erreur lors de la création de la base : " . $e->getMessage() . "</p>";
    echo "</div></body></html>";
    exit;
}
echo "</div>";

// Étape 3 : Connexion à l'application
echo "<div class='step'>";
echo "<h2>Étape 3 : Test de connexion de l'application</h2>";

try {
    $db = DatabaseConfig::getInstance();
    
    if ($db->testConnection()) {
        echo "<p class='success'>✅ Connexion de l'application réussie</p>";
        
        // Afficher les informations XAMPP
        $info = $db->getXAMPPInfo();
        echo "<p class='info'>📊 Version MySQL : " . $info['mysql_version'] . "</p>";
        echo "<p class='info'>📁 Répertoire de données : " . $info['data_directory'] . "</p>";
    } else {
        throw new Exception("Test de connexion échoué");
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur de connexion de l'application : " . $e->getMessage() . "</p>";
    echo "</div></body></html>";
    exit;
}
echo "</div>";

// Étape 4 : Installation des tables
echo "<div class='step'>";
echo "<h2>Étape 4 : Installation des tables et données</h2>";

$sqlFiles = [
    'create_database.sql' => 'Création des tables principales',
    'views.sql' => 'Création des vues SQL',
    'stored_procedures.sql' => 'Création des procédures stockées',
    'triggers.sql' => 'Création des triggers',
    'indexes.sql' => 'Création des index de performance',
    'seed_data.sql' => 'Insertion des données de test'
];

$totalSuccess = 0;
$totalErrors = 0;

foreach ($sqlFiles as $file => $description) {
    echo "<h3>$description</h3>";
    
    if (!file_exists(__DIR__ . '/' . $file)) {
        echo "<p class='warning'>⚠️ Fichier $file non trouvé, ignoré</p>";
        continue;
    }
    
    try {
        $result = $db->executeSQLFile(__DIR__ . '/' . $file);
        
        if (count($result['errors']) === 0) {
            echo "<p class='success'>✅ $description - " . $result['executed'] . " requêtes exécutées</p>";
        } else {
            echo "<p class='warning'>⚠️ $description - " . $result['executed'] . " réussies, " . count($result['errors']) . " erreurs</p>";
            if (!empty($result['errors'])) {
                echo "<details><summary>Voir les erreurs</summary><pre>";
                foreach ($result['errors'] as $error) {
                    if (is_array($error)) {
                        echo 'Erreur à la requête #' . $error['index'] . ': ' . $error['error'] . "\n";
                        echo "Requête : " . $error['statement'] . "\n\n";
                    } else {
                        echo htmlspecialchars($error) . "\n";
                    }
                }
                echo "</pre></details>";
            }
        }
        
        $totalSuccess += $result['executed'];
        $totalErrors += count($result['errors']);
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Erreur lors de l'exécution de $file : " . $e->getMessage() . "</p>";
        $totalErrors++;
    }
}

echo "<hr>";
echo "<p><strong>Résumé :</strong> $totalSuccess requêtes réussies, $totalErrors erreurs</p>";
echo "</div>";

// Étape 5 : Vérification finale
echo "<div class='step'>";
echo "<h2>Étape 5 : Vérification finale</h2>";

try {
    // Vérifier les tables principales
    $tables = ['departments', 'users', 'visitors'];
    foreach ($tables as $table) {
        $stmt = $db->getConnection()->query("SELECT COUNT(*) as count FROM $table");
        $count = $stmt->fetch();
        echo "<p class='success'>✅ Table '$table' : " . $count['count'] . " enregistrements</p>";
    }
    
    // Tester un compte utilisateur
    $stmt = $db->getConnection()->query("SELECT name, email FROM users WHERE email = 'marie@mupeci.com'");
    $user = $stmt->fetch();
    if ($user) {
        echo "<p class='success'>✅ Compte de test trouvé : " . $user['name'] . " (" . $user['email'] . ")</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur lors de la vérification : " . $e->getMessage() . "</p>";
}

echo "</div>";

// Résultat final
if ($totalErrors == 0) {
    echo "<div class='step'>";
    echo "<h2 class='success'>🎉 Installation terminée avec succès !</h2>";
    echo "<h3>Comptes de test disponibles :</h3>";
    echo "<ul>";
    echo "<li><strong>Réceptionnistes :</strong></li>";
    echo "<ul><li>marie@mupeci.com / password123</li><li>jean@mupeci.com / password123</li></ul>";
    echo "<li><strong>Administrateurs :</strong></li>";
    echo "<ul><li>admin@mupeci.com / admin123 (Code secret: MUPECI2024)</li></ul>";
    echo "</ul>";
    
    echo "<p><a href='../index.php' class='btn'>🚀 Accéder à l'application MUPECI</a></p>";
    echo "<p><a href='http://localhost/phpmyadmin/' class='btn' target='_blank'>📊 Voir dans phpMyAdmin</a></p>";
    echo "</div>";
} else {
    echo "<div class='error-step'>";
    echo "<h2 class='error'>⚠️ Installation terminée avec des erreurs</h2>";
    echo "<p>L'application peut fonctionner partiellement. Vérifiez les erreurs ci-dessus.</p>";
    echo "<p><a href='../index.php' class='btn'>Tester l'application</a></p>";
    echo "</div>";
}

echo "</body></html>";
?>