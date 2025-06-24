-- Création de la base de données MUPECI
CREATE DATABASE IF NOT EXISTS mupeci_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE mupeci_db;

-- Table des départements
CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des utilisateurs (réceptionnistes et administrateurs)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    user_type ENUM('receptionist', 'admin') NOT NULL,
    secret_code VARCHAR(50) NULL, -- Pour les administrateurs uniquement
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
    created_by INT NOT NULL, -- ID du réceptionniste qui a enregistré
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    
    INDEX idx_status (status),
    INDEX idx_checked_in_at (checked_in_at), -- FIXED
    INDEX idx_department (department_id),
    INDEX idx_id_number (id_number)
);

-- Table des sessions de visite (historique détaillé)
CREATE TABLE visit_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    visitor_id INT NOT NULL,
    department_id INT NOT NULL,
    receptionist_id INT NOT NULL,
    purpose VARCHAR(200),
    status ENUM('waiting', 'in-progress', 'completed', 'cancelled') DEFAULT 'waiting',
    wait_time_minutes INT DEFAULT 0, -- Temps d'attente en minutes
    visit_duration_minutes INT DEFAULT 0, -- Durée de la visite en minutes
    satisfaction_rating TINYINT NULL, -- Note de satisfaction (1-5)
    feedback TEXT NULL,
    checked_in_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (visitor_id) REFERENCES visitors(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT,
    FOREIGN KEY (receptionist_id) REFERENCES users(id) ON DELETE RESTRICT,
    
    INDEX idx_checked_in_at (checked_in_at), -- FIXED
    INDEX idx_department_date (department_id, checked_in_at), -- FIXED
    INDEX idx_status (status)
);

-- Table des statistiques quotidiennes (pour optimiser les requêtes)
CREATE TABLE daily_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stat_date DATE NOT NULL UNIQUE,
    total_visitors INT DEFAULT 0,
    completed_visits INT DEFAULT 0,
    cancelled_visits INT DEFAULT 0,
    average_wait_time DECIMAL(5,2) DEFAULT 0.00,
    average_visit_duration DECIMAL(5,2) DEFAULT 0.00,
    peak_hour TINYINT DEFAULT 0, -- Heure de pointe (0-23)
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
    
    INDEX idx_user_date (user_id, created_at), -- FIXED
    INDEX idx_action (action),
    INDEX idx_date (created_at) -- FIXED
);