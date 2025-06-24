<?php
// Installation ultra-rapide en une seule page
header('Content-Type: text/html; charset=utf-8');

// Configuration
$config = [
    'host' => 'localhost',
    'port' => '3306', 
    'username' => 'root',
    'password' => '',
    'database' => 'mupeci_db'
];

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Setup MUPECI</title></head><body>";
echo "<h1>🚀 Installation Express MUPECI</h1>";

try {
    // 1. Connexion MySQL
    $pdo = new PDO("mysql:host={$config['host']};port={$config['port']}", $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p>✅ Connexion MySQL OK</p>";
    
    // 2. Créer la base
    $pdo->exec("DROP DATABASE IF EXISTS {$config['database']}");
    $pdo->exec("CREATE DATABASE {$config['database']} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE {$config['database']}");
    echo "<p>✅ Base de données créée</p>";
    
    // 3. Créer les tables essentielles
    $sql = "
    CREATE TABLE departments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        active BOOLEAN DEFAULT TRUE
    );
    
    CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        user_type ENUM('receptionist', 'admin') NOT NULL,
        secret_code VARCHAR(50) NULL,
        active BOOLEAN DEFAULT TRUE
    );
    
    CREATE TABLE visitors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        email VARCHAR(100) NULL,
        id_number VARCHAR(50) NOT NULL,
        department_id INT NOT NULL,
        purpose ENUM('visite', 'rendez-vous', 'autres') DEFAULT 'visite',
        status ENUM('waiting', 'in-progress', 'completed') DEFAULT 'waiting',
        checked_in_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        started_at TIMESTAMP NULL,
        completed_at TIMESTAMP NULL,
        created_by INT NOT NULL,
        FOREIGN KEY (department_id) REFERENCES departments(id),
        FOREIGN KEY (created_by) REFERENCES users(id)
    );
    ";
    
    $pdo->exec($sql);
    echo "<p>✅ Tables créées</p>";
    
    // 4. Données de base
    $pdo->exec("INSERT INTO departments (name, active) VALUES 
        ('Direction Générale', 1),
        ('Ressources Humaines', 1),
        ('Comptabilité', 1),
        ('Service Client', 1)");
    
    $pdo->exec("INSERT INTO users (name, email, password, user_type, secret_code, active) VALUES 
        ('Marie Dupont', 'marie@mupeci.com', '" . password_hash('password123', PASSWORD_DEFAULT) . "', 'receptionist', NULL, 1),
        ('Admin Principal', 'admin@mupeci.com', '" . password_hash('admin123', PASSWORD_DEFAULT) . "', 'admin', 'MUPECI2024', 1)");
    
    echo "<p>✅ Données de test ajoutées</p>";
    
    // 5. Créer le fichier config
    $configCode = "<?php
class DatabaseConfig {
    private const DB_HOST = '{$config['host']}';
    private const DB_NAME = '{$config['database']}';
    private const DB_USER = '{$config['username']}';
    private const DB_PASS = '{$config['password']}';
    private static \$instance = null;
    private \$pdo;
    
    private function __construct() {
        \$dsn = \"mysql:host=\" . self::DB_HOST . \";dbname=\" . self::DB_NAME . \";charset=utf8mb4\";
        \$this->pdo = new PDO(\$dsn, self::DB_USER, self::DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
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
}

function getDB() { return DatabaseConfig::getInstance()->getConnection(); }
function executeQuery(\$sql, \$params = []) { \$db = getDB(); \$stmt = \$db->prepare(\$sql); \$stmt->execute(\$params); return \$stmt; }
function fetchOne(\$sql, \$params = []) { return executeQuery(\$sql, \$params)->fetch(); }
function fetchAll(\$sql, \$params = []) { return executeQuery(\$sql, \$params)->fetchAll(); }
?>";
    
    file_put_contents(__DIR__ . '/config.php', $configCode);
    echo "<p>✅ Configuration sauvegardée</p>";
    
    echo "<h2 style='color: green;'>🎉 Installation terminée !</h2>";
    echo "<p><strong>Comptes de test :</strong></p>";
    echo "<ul>";
    echo "<li>Réceptionniste : marie@mupeci.com / password123</li>";
    echo "<li>Admin : admin@mupeci.com / admin123 (Code: MUPECI2024)</li>";
    echo "</ul>";
    echo "<p><a href='../index.php' style='display: inline-block; padding: 10px 20px; background: #22c55e; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;'>🚀 Accéder à MUPECI</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur : " . $e->getMessage() . "</p>";
    echo "<p><strong>Vérifiez que XAMPP MySQL est démarré !</strong></p>";
}

echo "</body></html>";
?>
