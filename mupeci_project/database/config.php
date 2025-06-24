<?php
// Configuration de base de données pour MUPECI
// Ce fichier gère la connexion PDO et fournit des utilitaires robustes pour l'installation et la gestion de la base de données.

class DatabaseConfig {
    // Paramètres XAMPP par défaut pour la connexion MySQL
    private const DB_HOST = 'localhost';
    private const DB_NAME = 'mupeci_db';
    private const DB_USER = 'root';
    private const DB_PASS = '';  // Pas de mot de passe par défaut sur XAMPP
    private const DB_PORT = '3306';
    private const DB_CHARSET = 'utf8mb4';
    
    // Singleton pour garantir une seule instance PDO
    private static $instance = null;
    private $pdo;
    
    // Constructeur privé : initialise la connexion PDO
    private function __construct() {
        try {
            $dsn = "mysql:host=" . self::DB_HOST . ";port=" . self::DB_PORT . ";dbname=" . self::DB_NAME . ";charset=" . self::DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . self::DB_CHARSET,
                PDO::ATTR_TIMEOUT => 30
            ];
            
            $this->pdo = new PDO($dsn, self::DB_USER, self::DB_PASS, $options);
            
        } catch (PDOException $e) {
            // Affiche une erreur détaillée si la connexion échoue
            $error = "<h3>Erreur de connexion à la base de données MUPECI</h3>";
            $error .= "<p><strong>Vérifiez que :</strong></p>";
            $error .= "<ul>";
            $error .= "<li>XAMPP est démarré</li>";
            $error .= "<li>MySQL est en cours d'exécution</li>";
            $error .= "<li>La base de données 'mupeci_db' existe</li>";
            $error .= "<li>Les paramètres de connexion sont corrects</li>";
            $error .= "</ul>";
            $error .= "<p><strong>Erreur technique :</strong> " . $e->getMessage() . "</p>";
            $error .= "<p><a href='database/quick-setup.php'>🚀 Installer la base de données</a></p>";
            die($error);
        }
    }
    
    // Retourne l'instance unique de DatabaseConfig
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // Retourne l'objet PDO pour les requêtes SQL
    public function getConnection() {
        return $this->pdo;
    }
    
    // Teste la connexion à la base de données
    public function testConnection() {
        try {
            $stmt = $this->pdo->query("SELECT 1 as test");
            $result = $stmt->fetch();
            return $result['test'] == 1;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Récupère des informations sur le serveur MySQL/XAMPP.
     * Retourne la version MySQL, le répertoire de données, le statut de connexion et les erreurs éventuelles.
     *
     * @return array{connection_status: string, mysql_version: mixed, data_directory: mixed, error: null|string}
     */
    public function getXAMPPInfo(): array {
        $info = [
            'connection_status' => 'unknown',
            'mysql_version' => null,
            'data_directory' => null,
            'error' => null
        ];
        try {
            // Vérifie la connexion
            $status = $this->pdo->query("SELECT 1");
            $info['connection_status'] = $status ? 'connected' : 'not connected';

            // Récupère la version MySQL
            $stmt = $this->pdo->query("SELECT VERSION() AS version");
            $row = $stmt->fetch();
            $info['mysql_version'] = $row ? $row['version'] : null;

            // Récupère le répertoire de données MySQL
            $stmt = $this->pdo->query("SHOW VARIABLES LIKE 'datadir'");
            $row = $stmt->fetch();
            $info['data_directory'] = $row ? $row['Value'] : null;
        } catch (PDOException $e) {
            $info['connection_status'] = 'error';
            $info['error'] = $e->getMessage();
        }
        return $info;
    }

    /**
     * Exécute un fichier SQL complet (multi-requêtes) avec gestion des erreurs et transaction.
     * Retourne un tableau détaillant le succès, le nombre de requêtes exécutées et les erreurs éventuelles.
     * Les commentaires et lignes vides sont ignorés.
     */
    public function executeSQLFile($filePath) {
        $result = [
            'success' => false,
            'executed' => 0,
            'errors' => [],
            'file' => $filePath
        ];
        // Vérifie l'existence et la lisibilité du fichier
        if (!file_exists($filePath) || !is_readable($filePath)) {
            $result['errors'][] = "File not found or not readable: $filePath";
            return $result;
        }
        $sql = file_get_contents($filePath);
        if ($sql === false) {
            $result['errors'][] = "Failed to read file: $filePath";
            return $result;
        }
        // Découpe le fichier en requêtes SQL, ignore les commentaires/lignes vides
        $statements = [];
        $delimiter = ';';
        $buffer = '';
        $lines = preg_split('/\r\n|\r|\n/', $sql);
        foreach ($lines as $line) {
            $trimmed = trim($line);
            // Ignore les commentaires SQL et lignes vides
            if ($trimmed === '' || strpos($trimmed, '--') === 0 || strpos($trimmed, '#') === 0) {
                continue;
            }
            $buffer .= $line . "\n";
            if (substr(rtrim($line), -1) === $delimiter) {
                $statements[] = $buffer;
                $buffer = '';
            }
        }
        if (trim($buffer) !== '') {
            $statements[] = $buffer;
        }
        // Exécute toutes les requêtes dans une transaction
        $this->pdo->beginTransaction();
        try {
            foreach ($statements as $i => $statement) {
                $trimmed = trim($statement);
                if ($trimmed === '') continue;
                try {
                    $this->pdo->exec($trimmed);
                    $result['executed']++;
                } catch (PDOException $e) {
                    // Enregistre l'erreur mais continue l'exécution
                    $result['errors'][] = [
                        'statement' => $trimmed,
                        'error' => $e->getMessage(),
                        'index' => $i + 1
                    ];
                    // Pour rollback immédiat sur la première erreur, décommentez la ligne suivante :
                    // throw $e;
                }
            }
            // Si aucune erreur, commit la transaction
            if (count($result['errors']) === 0) {
                $this->pdo->commit();
                $result['success'] = true;
            } else {
                // Sinon, rollback
                $this->pdo->rollBack();
                $result['success'] = false;
            }
        } catch (Exception $e) {
            $this->pdo->rollBack();
            $result['errors'][] = $e->getMessage();
            $result['success'] = false;
        }
        return $result;
    }
}

// Fonctions utilitaires pour la base de données (requêtes simples)
function getDB(): PDO {
    $host = 'localhost';
    $dbname = 'mupeci_db';
    $user = 'root'; // Mets ici ton utilisateur MySQL
    $pass = '';     // Mets ici ton mot de passe MySQL

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("Erreur de connexion à la base de données: " . $e->getMessage());
    }
}

function executeQuery($sql, $params = []) {
    $db = getDB();
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function fetchOne($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetch();
}

function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetchAll();
}

function executeUpdate($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->rowCount();
}

function getLastInsertId() {
    return getDB()->lastInsertId();
}

// Teste la connexion à l'initialisation du fichier
try {
    DatabaseConfig::getInstance();
} catch (Exception $e) {
    // L'erreur sera affichée par le constructeur
}
?>
// ... existing code ...