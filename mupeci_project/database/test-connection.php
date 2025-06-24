<?php
// Script de test de connexion XAMPP
require_once 'config.php';

echo "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <title>Test de connexion XAMPP - MUPECI</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .success { color: #22c55e; }
        .error { color: #dc2626; }
        .info { background: #f0f9ff; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .test-item { padding: 10px; margin: 5px 0; border-left: 4px solid #e5e7eb; padding-left: 15px; }
        .test-success { border-left-color: #22c55e; background: #f0fdf4; }
        .test-error { border-left-color: #dc2626; background: #fef2f2; }
    </style>
</head>
<body>";

echo "<h1>🔧 Test de connexion XAMPP pour MUPECI</h1>";

// Test 1 : Connexion MySQL de base
echo "<div class='test-item'>";
echo "<h3>Test 1 : Connexion MySQL XAMPP</h3>";
try {
    $pdo = new PDO('mysql:host=localhost;port=3306', 'root', '');
    echo "<p class='success'>✅ Connexion MySQL réussie</p>";
    
    $stmt = $pdo->query("SELECT VERSION() as version");
    $version = $stmt->fetch();
    echo "<p>Version MySQL : " . $version['version'] . "</p>";
    echo "</div>";
} catch (PDOException $e) {
    echo "<p class='error'>❌ Échec de connexion MySQL</p>";
    echo "<p>Erreur : " . $e->getMessage() . "</p>";
    echo "<p><strong>Vérifiez que :</strong></p>";
    echo "<ul><li>XAMPP est démarré</li><li>MySQL est en cours d'exécution</li></ul>";
    echo "</div></body></html>";
    exit;
}

// Test 2 : Existence de la base de données
echo "<div class='test-item'>";
echo "<h3>Test 2 : Base de données mupeci_db</h3>";
try {
    $stmt = $pdo->query("SHOW DATABASES LIKE 'mupeci_db'");
    if ($stmt->rowCount() > 0) {
        echo "<p class='success'>✅ Base de données 'mupeci_db' trouvée</p>";
        echo "</div>";
    } else {
        echo "<p class='error'>❌ Base de données 'mupeci_db' non trouvée</p>";
        echo "<p>Créez la base avec : <code>CREATE DATABASE mupeci_db;</code></p>";
        echo "</div>";
    }
} catch (PDOException $e) {
    echo "<p class='error'>❌ Erreur lors de la vérification : " . $e->getMessage() . "</p>";
    echo "</div>";
}

// Test 3 : Connexion avec la classe DatabaseConfig
echo "<div class='test-item'>";
echo "<h3>Test 3 : Classe DatabaseConfig</h3>";
try {
    $db = DatabaseConfig::getInstance();
    if ($db->testConnection()) {
        echo "<p class='success'>✅ Connexion via DatabaseConfig réussie</p>";
        
        $info = $db->getXAMPPInfo();
        echo "<p>Statut : " . $info['connection_status'] . "</p>";
        echo "<p>Version : " . $info['mysql_version'] . "</p>";
        echo "<p>Répertoire de données : " . $info['data_directory'] . "</p>";
        if ($info['error']) {
            echo "<p class='error'>Erreur : " . $info['error'] . "</p>";
        }
        echo "</div>";
    } else {
        echo "<p class='error'>❌ Test de connexion échoué</p>";
        echo "</div>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur DatabaseConfig : " . $e->getMessage() . "</p>";
    echo "</div>";
}

// Test 4 : Vérification des tables
echo "<div class='test-item'>";
echo "<h3>Test 4 : Tables de l'application</h3>";
try {
    $db = DatabaseConfig::getInstance();
    $conn = $db->getConnection();
    
    $tables = ['departments', 'users', 'visitors'];
    $tablesFound = 0;
    
    foreach ($tables as $table) {
        try {
            $stmt = $conn->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch();
            echo "<p class='success'>✅ Table '$table' : " . $count['count'] . " enregistrements</p>";
            $tablesFound++;
        } catch (PDOException $e) {
            echo "<p class='error'>❌ Table '$table' non trouvée</p>";
        }
    }
    
    if ($tablesFound == count($tables)) {
        echo "<p class='success'><strong>Toutes les tables sont présentes !</strong></p>";
    } else {
        echo "<p class='error'><strong>Certaines tables manquent. Exécutez l'installation.</strong></p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur lors de la vérification des tables : " . $e->getMessage() . "</p>";
}
echo "</div>";

// Informations système
echo "<div class>";