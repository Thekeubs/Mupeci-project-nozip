-- MUPECI - Script SQL complet pour import direct
-- Créé pour import via phpMyAdmin ou ligne de commande MySQL

-- Utiliser la base de données
USE mupeci_db;

-- Supprimer les tables existantes si elles existent (pour réinstallation)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS daily_stats;
DROP TABLE IF EXISTS visit_sessions;
DROP TABLE IF EXISTS visitors;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS departments;
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- CRÉATION DES TABLES
-- =====================================================

-- Table des départements
CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des utilisateurs (réceptionnistes et administrateurs)
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    INDEX idx_checked_in_at (checked_in_at),
    INDEX idx_department (department_id),
    INDEX idx_id_number (id_number),
    INDEX idx_phone (phone),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des sessions de visite (historique détaillé)
CREATE TABLE visit_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    visitor_id INT NOT NULL,
    department_id INT NOT NULL,
    receptionist_id INT NOT NULL,
    purpose VARCHAR(200),
    status ENUM('waiting', 'in-progress', 'completed', 'cancelled') DEFAULT 'waiting',
    wait_time_minutes INT DEFAULT 0,
    visit_duration_minutes INT DEFAULT 0,
    satisfaction_rating TINYINT NULL,
    feedback TEXT NULL,
    checked_in_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (visitor_id) REFERENCES visitors(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT,
    FOREIGN KEY (receptionist_id) REFERENCES users(id) ON DELETE RESTRICT,
    
    INDEX idx_checked_in_at (checked_in_at),
    INDEX idx_department_date (department_id, checked_in_at),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    
    INDEX idx_user_date (user_id, created_at),
    INDEX idx_action (action),
    INDEX idx_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INSERTION DES DONNÉES DE TEST
-- =====================================================

-- Insertion des départements
INSERT INTO departments (name, description, active) VALUES
('Direction Générale', 'Bureau de la direction générale', TRUE),
('Ressources Humaines', 'Gestion du personnel et recrutement', TRUE),
('Comptabilité', 'Services comptables et financiers', TRUE),
('Service Client', 'Accueil et support clientèle', TRUE),
('IT Support', 'Support informatique et technique', TRUE),
('Marketing', 'Communication et marketing', FALSE),
('Juridique', 'Services juridiques et conformité', TRUE);

-- Insertion des utilisateurs avec mots de passe hashés
-- password123 = $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
-- admin123 = $2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm

INSERT INTO users (name, email, password, user_type, secret_code, active) VALUES
-- Réceptionnistes
('Marie Dupont', 'marie@mupeci.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'receptionist', NULL, TRUE),
('Jean Martin', 'jean@mupeci.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'receptionist', NULL, TRUE),
('Sophie Kamga', 'sophie@mupeci.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'receptionist', NULL, FALSE),

-- Administrateurs
('Admin Principal', 'admin@mupeci.com', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'admin', 'MUPECI2024', TRUE),
('Directeur IT', 'it.admin@mupeci.com', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'admin', 'MUPECI2025', TRUE);

-- Insertion de visiteurs de test
INSERT INTO visitors (name, phone, email, id_number, department_id, purpose, status, checked_in_at, started_at, completed_at, created_by) VALUES
-- Visiteurs d'aujourd'hui
('Paul Nkomo', '+237123456789', 'paul.nkomo@email.com', 'CNI123456', 1, 'rendez-vous', 'completed', 
 DATE_SUB(NOW(), INTERVAL 2 HOUR), DATE_SUB(NOW(), INTERVAL 2 HOUR), DATE_SUB(NOW(), INTERVAL 1 HOUR), 1),

('Sophie Tchoumi', '+237987654321', 'sophie.tchoumi@email.com', 'CNI789012', 2, 'visite', 'completed',
 DATE_SUB(NOW(), INTERVAL 3 HOUR), DATE_SUB(NOW(), INTERVAL 3 HOUR), DATE_SUB(NOW(), INTERVAL 2 HOUR), 1),

('André Fotso', '+237555666777', 'andre.fotso@email.com', 'CNI345678', 4, 'autres', 'in-progress',
 DATE_SUB(NOW(), INTERVAL 30 MINUTE), DATE_SUB(NOW(), INTERVAL 15 MINUTE), NULL, 2),

('Marie Mballa', '+237444555666', 'marie.mballa@email.com', 'CNI901234', 3, 'rendez-vous', 'waiting',
 DATE_SUB(NOW(), INTERVAL 15 MINUTE), NULL, NULL, 1),

('Jean Talla', '+237333444555', NULL, 'CNI567890', 1, 'visite', 'waiting',
 DATE_SUB(NOW(), INTERVAL 10 MINUTE), NULL, NULL, 2),

-- Visiteurs d'hier
('Christelle Biya', '+237222333444', 'christelle.biya@email.com', 'CNI111222', 1, 'rendez-vous', 'completed',
 DATE_SUB(NOW(), INTERVAL 1 DAY) + INTERVAL 9 HOUR, 
 DATE_SUB(NOW(), INTERVAL 1 DAY) + INTERVAL 9 HOUR, 
 DATE_SUB(NOW(), INTERVAL 1 DAY) + INTERVAL 10 HOUR, 1),

('Robert Essomba', '+237111222333', 'robert.essomba@email.com', 'CNI333444', 2, 'visite', 'completed',
 DATE_SUB(NOW(), INTERVAL 1 DAY) + INTERVAL 10 HOUR, 
 DATE_SUB(NOW(), INTERVAL 1 DAY) + INTERVAL 10 HOUR, 
 DATE_SUB(NOW(), INTERVAL 1 DAY) + INTERVAL 10 HOUR + INTERVAL 30 MINUTE, 2),

('Françoise Ndongo', '+237666777888', 'francoise.ndongo@email.com', 'CNI555666', 4, 'rendez-vous', 'completed',
 DATE_SUB(NOW(), INTERVAL 1 DAY) + INTERVAL 14 HOUR, 
 DATE_SUB(NOW(), INTERVAL 1 DAY) + INTERVAL 14 HOUR, 
 DATE_SUB(NOW(), INTERVAL 1 DAY) + INTERVAL 15 HOUR, 1),

-- Visiteurs de la semaine dernière
('Michel Onana', '+237777888999', 'michel.onana@email.com', 'CNI777888', 3, 'visite', 'completed',
 DATE_SUB(NOW(), INTERVAL 3 DAY) + INTERVAL 11 HOUR, 
 DATE_SUB(NOW(), INTERVAL 3 DAY) + INTERVAL 11 HOUR, 
 DATE_SUB(NOW(), INTERVAL 3 DAY) + INTERVAL 12 HOUR, 2);

-- Insertion des sessions de visite détaillées
INSERT INTO visit_sessions (visitor_id, department_id, receptionist_id, purpose, status, wait_time_minutes, visit_duration_minutes, checked_in_at, started_at, completed_at) VALUES
(1, 1, 1, 'Réunion avec le directeur', 'completed', 5, 60, DATE_SUB(NOW(), INTERVAL 2 HOUR), DATE_SUB(NOW(), INTERVAL 2 HOUR), DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(2, 2, 1, 'Entretien d\'embauche', 'completed', 10, 45, DATE_SUB(NOW(), INTERVAL 3 HOUR), DATE_SUB(NOW(), INTERVAL 3 HOUR), DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(3, 4, 2, 'Réclamation client', 'in-progress', 15, 0, DATE_SUB(NOW(), INTERVAL 30 MINUTE), DATE_SUB(NOW(), INTERVAL 15 MINUTE), NULL),
(4, 3, 1, 'Consultation comptable', 'waiting', 0, 0, DATE_SUB(NOW(), INTERVAL 15 MINUTE), NULL, NULL),
(5, 1, 2, 'Présentation projet', 'waiting', 0, 0, DATE_SUB(NOW(), INTERVAL 10 MINUTE), NULL, NULL);

-- Insertion des statistiques quotidiennes
INSERT INTO daily_stats (stat_date, total_visitors, completed_visits, cancelled_visits, average_wait_time, average_visit_duration, peak_hour) VALUES
(CURDATE(), 5, 2, 0, 10.00, 52.50, 10),
(DATE_SUB(CURDATE(), INTERVAL 1 DAY), 8, 7, 1, 8.50, 45.00, 14),
(DATE_SUB(CURDATE(), INTERVAL 2 DAY), 6, 6, 0, 12.00, 38.00, 11),
(DATE_SUB(CURDATE(), INTERVAL 3 DAY), 4, 4, 0, 15.00, 42.00, 15),
(DATE_SUB(CURDATE(), INTERVAL 4 DAY), 7, 6, 1, 9.00, 35.00, 9),
(DATE_SUB(CURDATE(), INTERVAL 5 DAY), 5, 5, 0, 11.00, 48.00, 16),
(DATE_SUB(CURDATE(), INTERVAL 6 DAY), 9, 8, 1, 7.50, 40.00, 10);

-- Insertion de quelques logs d'activité
INSERT INTO activity_logs (user_id, action, table_name, record_id, ip_address, user_agent) VALUES
(1, 'LOGIN', 'users', 1, '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'),
(1, 'CREATE_VISITOR', 'visitors', 1, '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'),
(2, 'LOGIN', 'users', 2, '192.168.1.101', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36'),
(4, 'LOGIN', 'users', 4, '192.168.1.102', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'),
(4, 'CREATE_DEPARTMENT', 'departments', 7, '192.168.1.102', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

-- =====================================================
-- CRÉATION DES VUES
-- =====================================================

-- Vue pour les visiteurs actifs
CREATE VIEW active_visitors AS
SELECT 
    v.id,
    v.name,
    v.phone,
    v.email,
    v.id_number,
    d.name as department_name,
    v.purpose,
    v.status,
    v.checked_in_at,
    v.started_at,
    TIMESTAMPDIFF(MINUTE, v.checked_in_at, NOW()) as waiting_minutes,
    u.name as receptionist_name
FROM visitors v
JOIN departments d ON v.department_id = d.id
JOIN users u ON v.created_by = u.id
WHERE v.status IN ('waiting', 'in-progress')
ORDER BY v.checked_in_at ASC;

-- Vue pour les visiteurs du jour
CREATE VIEW today_visitors AS
SELECT 
    v.id,
    v.name,
    v.phone,
    v.email,
    v.id_number,
    d.name as department_name,
    v.purpose,
    v.status,
    v.checked_in_at,
    v.started_at,
    v.completed_at,
    CASE 
        WHEN v.started_at IS NOT NULL 
        THEN TIMESTAMPDIFF(MINUTE, v.checked_in_at, v.started_at)
        ELSE NULL 
    END as wait_time_minutes,
    CASE 
        WHEN v.completed_at IS NOT NULL AND v.started_at IS NOT NULL
        THEN TIMESTAMPDIFF(MINUTE, v.started_at, v.completed_at)
        ELSE NULL 
    END as visit_duration_minutes,
    u.name as receptionist_name
FROM visitors v
JOIN departments d ON v.department_id = d.id
JOIN users u ON v.created_by = u.id
WHERE DATE(v.checked_in_at) = CURDATE()
ORDER BY v.checked_in_at DESC;

-- Vue pour les statistiques des départements
CREATE VIEW department_stats AS
SELECT 
    d.id,
    d.name as department_name,
    d.active,
    COUNT(v.id) as total_visitors_today,
    SUM(CASE WHEN v.status = 'waiting' THEN 1 ELSE 0 END) as waiting_count,
    SUM(CASE WHEN v.status = 'in-progress' THEN 1 ELSE 0 END) as in_progress_count,
    SUM(CASE WHEN v.status = 'completed' THEN 1 ELSE 0 END) as completed_count,
    AVG(CASE 
        WHEN v.completed_at IS NOT NULL AND v.started_at IS NOT NULL 
        THEN TIMESTAMPDIFF(MINUTE, v.started_at, v.completed_at) 
        ELSE NULL 
    END) as avg_visit_duration
FROM departments d
LEFT JOIN visitors v ON d.id = v.department_id AND DATE(v.checked_in_at) = CURDATE()
GROUP BY d.id, d.name, d.active
ORDER BY total_visitors_today DESC;

-- =====================================================
-- MESSAGE DE CONFIRMATION
-- =====================================================

-- Afficher un résumé de l'installation
SELECT 
    'INSTALLATION TERMINÉE' as status,
    (SELECT COUNT(*) FROM departments) as departments_created,
    (SELECT COUNT(*) FROM users) as users_created,
    (SELECT COUNT(*) FROM visitors) as visitors_created,
    'Base de données MUPECI prête à l\'utilisation' as message;

-- Afficher les comptes de test
SELECT 
    'COMPTES DE TEST' as info,
    'marie@mupeci.com / password123' as receptionist_1,
    'jean@mupeci.com / password123' as receptionist_2,
    'admin@mupeci.com / admin123 (Code: MUPECI2024)' as administrator;
