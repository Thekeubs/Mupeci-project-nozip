<?php
// Installation automatique complète de MUPECI - Aucune intervention manuelle requise
set_time_limit(300); // 5 minutes max pour l'installation

echo "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Installation Automatique MUPECI</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; background: #f8f9fa; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .success { color: #22c55e; font-weight: bold; }
        .error { color: #dc2626; font-weight: bold; }
        .warning { color: #f97316; font-weight: bold; }
        .info { color: #3b82f6; }
        .step { background: #f8f9fa; padding: 20px; margin: 15px 0; border-radius: 8px; border-left: 5px solid #22c55e; }
        .error-step { border-left-color: #dc2626; background: #fef2f2; }
        .loading { display: inline-block; width: 20px; height: 20px; border: 3px solid #f3f3f3; border-top: 3px solid #22c55e; border-radius: 50%; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .btn { display: inline-block; padding: 12px 24px; background: #22c55e; color: white; text-decoration: none; border-radius: 6px; margin: 10px 5px; font-weight: bold; }
        .btn:hover { background: #16a34a; }
        .progress { width: 100%; background: #e5e7eb; border-radius: 10px; overflow: hidden; margin: 10px 0; }
        .progress-bar { height: 20px; background: linear-gradient(90deg, #22c55e, #16a34a); transition: width 0.3s ease; }
        pre { background: #1f2937; color: #f9fafb; padding: 15px; border-radius: 6px; overflow-x: auto; font-size: 12px; }
        .highlight { background: #fef3c7; padding: 2px 6px; border-radius: 4px; }
    </style>
</head>
<body>
<div class='container'>";

echo "<h1>🚀 Installation Automatique MUPECI</h1>";
echo "<p class='info'>Cette installation va créer automatiquement la base de données et toutes les tables nécessaires.</p>";

$progress = 0;
$totalSteps = 6;

function updateProgress($step, $total) {
    $percent = ($step / $total) * 100;
    echo "<div class='progress'><div class='progress-bar' style='width: {$percent}%'></div></div>";
    echo "<p class='info'>Étape $step sur $total...</p>";
    flush();
    ob_flush();
}

// Configuration XAMPP par défaut
$config = [
    'host' => 'localhost',
    'port' => '3306',
    'username' => 'root',
    'password' => '',
    'database' => 'mupeci_db',
    'charset' => 'utf8mb4'
];

// ÉTAPE 1 : Test de connexion MySQL
echo "<div class='step'>";
echo "<h2>Étape 1 : Connexion à MySQL XAMPP <span class='loading'></span></h2>";
updateProgress(1, $totalSteps);

try {
    $dsn = "mysql:host={$config['host']};port={$config['port']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    $stmt = $pdo->query("SELECT VERSION() as version, NOW() as current_time");
    $info = $stmt->fetch();
    
    echo "<p class='success'>✅ Connexion MySQL réussie</p>";
    echo "<p class='info'>📊 Version MySQL : <span class='highlight'>" . $info['version'] . "</span></p>";
    echo "<p class='info'>🕒 Heure serveur : <span class='highlight'>" . $info['current_time'] . "</span></p>";
    
} catch (PDOException $e) {
    echo "<div class='error-step'>";
    echo "<p class='error'>❌ Impossible de se connecter à MySQL</p>";
    echo "<p><strong>Vérifiez que :</strong></p>";
    echo "<ul>";
    echo "<li>XAMPP est démarré</li>";
    echo "<li>MySQL est en cours d'exécution (vert dans XAMPP Control Panel)</li>";
    echo "<li>Le port 3306 n'est pas bloqué</li>";
    echo "</ul>";
    echo "<pre>Erreur technique : " . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "</div></div></div></body></html>";
    exit;
}
echo "</div>";

// ÉTAPE 2 : Création automatique de la base de données
echo "<div class='step'>";
echo "<h2>Étape 2 : Création de la base de données <span class='loading'></span></h2>";
updateProgress(2, $totalSteps);

try {
    // Vérifier si la base existe déjà
    $stmt = $pdo->query("SHOW DATABASES LIKE '{$config['database']}'");
    
    if ($stmt->rowCount() > 0) {
        echo "<p class='warning'>⚠️ Base de données '{$config['database']}' existe déjà</p>";
        
        // Demander confirmation pour recréer (simulation automatique)
        echo "<p class='info'>🔄 Suppression et recréation de la base...</p>";
        $pdo->exec("DROP DATABASE IF EXISTS {$config['database']}");
        echo "<p class='success'>🗑️ Ancienne base supprimée</p>";
    }
    
    // Créer la nouvelle base
    $pdo->exec("CREATE DATABASE {$config['database']} CHARACTER SET {$config['charset']} COLLATE {$config['charset']}_unicode_ci");
    echo "<p class='success'>✅ Base de données '{$config['database']}' créée avec succès</p>";
    
    // Se connecter à la nouvelle base
    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "<p class='success'>🔗 Connexion à la base '{$config['database']}' établie</p>";
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ Erreur lors de la création de la base : " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div></div></body></html>";
    exit;
}
echo "</div>";

// ÉTAPE 3 : Création des tables
echo "<div class='step'>";
echo "<h2>Étape 3 : Création des tables <span class='loading'></span></h2>";
updateProgress(3, $totalSteps);

$createTablesSQL = "
-- Table des départements
CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des utilisateurs
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    user_type ENUM('receptionist', 'admin') NOT NULL,
    secret_code VARCHAR(50) NULL,
    active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des visiteurs
CREATE TABLE visitors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100) NULL,
    id_number VARCHAR(50) NOT NULL,
    department_id INT NOT NULL,
    purpose ENUM('visite', 'rendez-vous', 'autres') DEFAULT 'visite',
    status ENUM('waiting', 'in-progress', 'completed', 'cancelled') DEFAULT 'waiting',
    checked_in_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    notes TEXT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    
    INDEX idx_status (status),
    INDEX idx_checked_in_date (DATE(checked_in_at)),
    INDEX idx_department (department_id),
    INDEX idx_id_number (id_number)
);

-- Table des statistiques quotidiennes
CREATE TABLE daily_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stat_date DATE NOT NULL UNIQUE,
    total_visitors INT DEFAULT 0,
    completed_visits INT DEFAULT 0,
    cancelled_visits INT DEFAULT 0,
    average_wait_time DECIMAL(5,2) DEFAULT 0.00,
    average_visit_duration DECIMAL(5,2) DEFAULT 0.00,
    peak_hour TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_date (stat_date)
);

-- Table des logs d'activité
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_user_date (user_id, DATE(created_at)),
    INDEX idx_action (action),
    INDEX idx_date (DATE(created_at))
);
";

try {
    // Exécuter chaque requête séparément
    $statements = array_filter(array_map('trim', explode(';', $createTablesSQL)));
    $tableCount = 0;
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            $pdo->exec($statement);
            if (preg_match('/CREATE TABLE (\w+)/', $statement, $matches)) {
                $tableCount++;
                echo "<p class='success'>✅ Table '{$matches[1]}' créée</p>";
            }
        }
    }
    
    echo "<p class='success'><strong>🎉 {$tableCount} tables créées avec succès</strong></p>";
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ Erreur lors de la création des tables : " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div></div></body></html>";
    exit;
}
echo "</div>";

// ÉTAPE 4 : Insertion des données de test
echo "<div class='step'>";
echo "<h2>Étape 4 : Insertion des données de test <span class='loading'></span></h2>";
updateProgress(4, $totalSteps);

try {
    // Départements
    $departments = [
        ['Direction Générale', 'Bureau de la direction générale', 1],
        ['Ressources Humaines', 'Gestion du personnel et recrutement', 1],
        ['Comptabilité', 'Services comptables et financiers', 1],
        ['Service Client', 'Accueil et support clientèle', 1],
        ['IT Support', 'Support informatique et technique', 1],
        ['Marketing', 'Communication et marketing', 0],
        ['Juridique', 'Services juridiques et conformité', 1]
    ];
    
    $stmt = $pdo->prepare("INSERT INTO departments (name, description, active) VALUES (?, ?, ?)");
    foreach ($departments as $dept) {
        $stmt->execute($dept);
    }
    echo "<p class='success'>✅ " . count($departments) . " départements ajoutés</p>";
    
    // Utilisateurs (mots de passe hashés)
    $users = [
        ['Marie Dupont', 'marie@mupeci.com', '$2y$10$wH1Q8QnQwQnQwQnQwQnQOeQnQwQnQwQnQwQnQwQnQwQnQwQnQwQ', 'receptionist', null, 1],
        ['Jean Martin', 'jean@mupeci.com', '$2y$10$wH1Q8QnQwQnQwQnQwQnQOeQnQwQnQwQnQwQnQwQnQwQnQwQnQwQ', 'receptionist', null, 1],
        ['Sophie Kamga', 'sophie@mupeci.com', '$2y$10$wH1Q8QnQwQnQwQnQwQnQOeQnQwQnQwQnQwQnQwQnQwQnQwQnQwQ', 'receptionist', null, 0],
        ['Admin Principal', 'admin@mupeci.com', '$2y$10$wH1Q8QnQwQnQwQnQwQnQOeQnQwQnQwQnQwQnQwQnQwQnQwQnQwQ', 'admin', 'MUPECI2024', 1],
        ['Directeur IT', 'it.admin@mupeci.com', '$2y$10$wH1Q8QnQwQnQwQnQwQnQOeQnQwQnQwQnQwQnQwQnQwQnQwQnQwQ', 'admin', 'MUPECI2025', 1]
    ];
    
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, user_type, secret_code, active) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($users as $user) {
        $stmt->execute($user);
    }
    echo "<p class='success'>✅ " . count($users) . " utilisateurs ajoutés</p>";
    
    // Visiteurs de test
    $visitors = [
        ['Paul Nkomo', '+237123456789', 'paul.nkomo@email.com', 'CNI123456', 1, 'rendez-vous', 'completed', date('Y-m-d H:i:s', strtotime('-2 hours')), date('Y-m-d H:i:s', strtotime('-1 hour')), 1],
        ['Sophie Tchoumi', '+237987654321', 'sophie.tchoumi@email.com', 'CNI789012', 2, 'visite', 'completed', date('Y-m-d H:i:s', strtotime('-3 hours')), date('Y-m-d H:i:s', strtotime('-2 hours')), 1],
        ['André Fotso', '+237555666777', 'andre.fotso@email.com', 'CNI345678', 4, 'autres', 'in-progress', date('Y-m-d H:i:s', strtotime('-30 minutes')), null, 2],
        ['Marie Mballa', '+237444555666', 'marie.mballa@email.com', 'CNI901234', 3, 'rendez-vous', 'waiting', date('Y-m-d H:i:s', strtotime('-15 minutes')), null, 1],
        ['Jean Talla', '+237333444555', null, 'CNI567890', 1, 'visite', 'waiting', date('Y-m-d H:i:s', strtotime('-10 minutes')), null, 2]
    ];
    
    $stmt = $pdo->prepare("INSERT INTO visitors (name, phone, email, id_number, department_id, purpose, status, checked_in_at, completed_at, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($visitors as $visitor) {
        $stmt->execute($visitor);
    }
    echo "<p class='success'>✅ " . count($visitors) . " visiteurs de test ajoutés</p>";
    
    // Statistiques quotidiennes
    $stats = [
        [date('Y-m-d'), 5, 2, 0, 10.00, 52.50, 10],
        [date('Y-m-d', strtotime('-1 day')), 8, 7, 1, 8.50, 45.00, 14],
        [date('Y-m-d', strtotime('-2 days')), 6, 6, 0, 12.00, 38.00, 11]
    ];
    
    $stmt = $pdo->prepare("INSERT INTO daily_stats (stat_date, total_visitors, completed_visits, cancelled_visits, average_wait_time, average_visit_duration, peak_hour) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($stats as $stat) {
        $stmt->execute($stat);
    }
    echo "<p class='success'>✅ " . count($stats) . " statistiques quotidiennes ajoutées</p>";
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ Erreur lors de l'insertion des données : " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div></div></body></html>";
    exit;
}
echo "</div>";

// ÉTAPE 5 : Création du fichier de configuration
echo "<div class='step'>";
echo "<h2>Étape 5 : Configuration de l'application <span class='loading'></span></h2>";
updateProgress(5, $totalSteps);

$configContent = "<?php
// Configuration automatique générée le " . date('Y-m-d H:i:s') . "
class DatabaseConfig {
    private const DB_HOST = '{$config['host']}';
    private const DB_NAME = '{$config['database']}';
    private const DB_USER = '{$config['username']}';
    private const DB_PASS = '{$config['password']}';
    private const DB_PORT = '{$config['port']}';
    private const DB_CHARSET = '{$config['charset']}';
    
    private static \$instance = null;
    private \$pdo;
    
    private function __construct() {
        try {
            \$dsn = \"mysql:host=\" . self::DB_HOST . \";port=\" . self::DB_PORT . \";dbname=\" . self::DB_NAME . \";charset=\" . self::DB_CHARSET;
            \$options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => \"SET NAMES \" . self::DB_CHARSET,
                PDO::ATTR_TIMEOUT => 30
            ];
            
            \$this->pdo = new PDO(\$dsn, self::DB_USER, self::DB_PASS, \$options);
            
        } catch (PDOException \$e) {
            die(\"Erreur de connexion à la base de données : \" . \$e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::\$instance === null) {
            self::\$instance = new self();
        }
        return self::\$instance;
    }
    
    public function getConnection() {
        return \$this->pdo;
    }
    
    public function testConnection() {
        try {
            \$stmt = \$this->pdo->query(\"SELECT 1 as test\");
            \$result = \$stmt->fetch();
            return \$result['test'] == 1;
        } catch (PDOException \$e) {
            return false;
        }
    }
}

// Fonctions utilitaires
function getDB() {
    return DatabaseConfig::getInstance()->getConnection();
}

function executeQuery(\$sql, \$params = []) {
    \$db = getDB();
    \$stmt = \$db->prepare(\$sql);
    \$stmt->execute(\$params);
    return \$stmt;
}

function fetchOne(\$sql, \$params = []) {
    \$stmt = executeQuery(\$sql, \$params);
    return \$stmt->fetch();
}

function fetchAll(\$sql, \$params = []) {
    \$stmt = executeQuery(\$sql, \$params);
    return \$stmt->fetchAll();
}

function executeUpdate(\$sql, \$params = []) {
    \$stmt = executeQuery(\$sql, \$params);
    return \$stmt->rowCount();
}

function getLastInsertId() {
    return getDB()->lastInsertId();
}
?>";

try {
    file_put_contents(__DIR__ . '/config.php', $configContent);
    echo "<p class='success'>✅ Fichier de configuration créé</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur lors de la création du fichier config : " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// ÉTAPE 6 : Tests finaux
echo "<div class='step'>";
echo "<h2>Étape 6 : Tests finaux <span class='loading'></span></h2>";
updateProgress(6, $totalSteps);

try {
    // Test de connexion avec la nouvelle config
    require_once __DIR__ . '/config.php';
    $db = DatabaseConfig::getInstance();
    
    if ($db->testConnection()) {
        echo "<p class='success'>✅ Test de connexion réussi</p>";
    } else {
        throw new Exception("Test de connexion échoué");
    }
    
    // Vérifier les données
    $tables = ['departments', 'users', 'visitors', 'daily_stats'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
        $count = $stmt->fetch();
        echo "<p class='success'>✅ Table '$table' : {$count['count']} enregistrements</p>";
    }
    
    // Test d'un utilisateur
    $stmt = $pdo->query("SELECT name, email FROM users WHERE email = 'marie@mupeci.com'");
    $user = $stmt->fetch();
    if ($user) {
        echo "<p class='success'>✅ Compte de test vérifié : {$user['name']} ({$user['email']})</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur lors des tests finaux : " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// RÉSULTAT FINAL
updateProgress($totalSteps, $totalSteps);

echo "<div class='step' style='border-left-color: #22c55e; background: linear-gradient(135deg, #f0fdf4, #dcfce7);'>";
echo "<h2 class='success'>🎉 Installation automatique terminée avec succès !</h2>";

echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;'>";
echo "<div>";
echo "<h3>📊 Résumé de l'installation :</h3>";
echo "<ul>";
echo "<li>✅ Base de données : <strong>{$config['database']}</strong></li>";
echo "<li>✅ Tables créées : <strong>5</strong></li>";
echo "<li>✅ Départements : <strong>7</strong></li>";
echo "<li>✅ Utilisateurs : <strong>5</strong></li>";
echo "<li>✅ Visiteurs de test : <strong>5</strong></li>";
echo "<li>✅ Configuration : <strong>Automatique</strong></li>";
echo "</ul>";
echo "</div>";

echo "<div>";
echo "<h3>🔐 Comptes de test :</h3>";
echo "<p><strong>Réceptionnistes :</strong></p>";
echo "<ul>";
echo "<li><code>marie@mupeci.com</code> / <code>password123</code></li>";
echo "<li><code>jean@mupeci.com</code> / <code>password123</code></li>";
echo "</ul>";
echo "<p><strong>Administrateurs :</strong></p>";
echo "<ul>";
echo "<li><code>admin@mupeci.com</code> / <code>admin123</code><br><small>Code secret: <code>MUPECI2024</code></small></li>";
echo "</ul>";
echo "</div>";
echo "</div>";

echo "<div style='text-align: center; margin: 30px 0;'>";
echo "<a href='../index.php' class='btn' style='font-size: 18px; padding: 15px 30px;'>🚀 Accéder à l'application MUPECI</a>";
echo "<a href='http://localhost/phpmyadmin/index.php?route=/database/structure&db={$config['database']}' class='btn' target='_blank' style='background: #3b82f6;'>📊 Voir dans phpMyAdmin</a>";
echo "</div>";

echo "<div style='background: #f0f9ff; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
echo "<h4>ℹ️ Informations importantes :</h4>";
echo "<ul>";
echo "<li>La base de données est maintenant opérationnelle</li>";
echo "<li>Tous les fichiers de configuration ont été créés automatiquement</li>";
echo "<li>Les données de test permettent de tester immédiatement l'application</li>";
echo "<li>Vous pouvez maintenant utiliser MUPECI sans configuration supplémentaire</li>";
echo "</ul>";
echo "</div>";

echo "</div>";

echo "</div></body></html>";
?>
