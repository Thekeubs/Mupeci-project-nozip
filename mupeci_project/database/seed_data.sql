-- Insertion des données de test pour MUPECI
USE mupeci_db;

-- Vider les tables avant insertion
SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM activity_logs;
DELETE FROM daily_stats;
DELETE FROM visit_sessions;
DELETE FROM visitors;
DELETE FROM users;
DELETE FROM departments;;

SET FOREIGN_KEY_CHECKS = 1;

-- Insertion des départements
INSERT INTO departments (name, description, active) VALUES
('Direction Générale', 'Bureau de la direction générale', TRUE),
('Ressources Humaines', 'Gestion du personnel et recrutement', TRUE),
('Comptabilité', 'Services comptables et financiers', TRUE),
('Service Client', 'Accueil et support clientèle', TRUE),
('IT Support', 'Support informatique et technique', TRUE),
('Marketing', 'Communication et marketing', FALSE),
('Juridique', 'Services juridiques et conformité', TRUE);

-- Insertion des utilisateurs
-- Mot de passe hashé pour 'password123' : $2y$10$wH1Q8QnQwQnQwQnQwQnQOeQnQwQnQwQnQwQnQwQnQwQnQwQnQwQ
-- Mot de passe hashé pour 'admin123' : $2y$10$wH1Q8QnQwQnQwQnQwQnQOeQnQwQnQwQnQwQnQwQnQwQnQwQnQwQ

INSERT INTO users (name, email, password, user_type, secret_code, active) VALUES
-- Réceptionnistes
('Marie Dupont', 'marie@mupeci.com', '$2y$10$wH1Q8QnQwQnQwQnQwQnQOeQnQwQnQwQnQwQnQwQnQwQnQwQnQwQ', 'receptionist', NULL, TRUE),
('Jean Martin', 'jean@mupeci.com', '$2y$10$wH1Q8QnQwQnQwQnQwQnQOeQnQwQnQwQnQwQnQwQnQwQnQwQnQwQ', 'receptionist', NULL, TRUE),
('Sophie Kamga', 'sophie@mupeci.com', '$2y$10$wH1Q8QnQwQnQwQnQwQnQOeQnQwQnQwQnQwQnQwQnQwQnQwQnQwQ', 'receptionist', NULL, FALSE),

-- Administrateurs
('Admin Principal', 'admin@mupeci.com', '$2y$10$wH1Q8QnQwQnQwQnQwQnQOeQnQwQnQwQnQwQnQwQnQwQnQwQnQwQ', 'admin', 'MUPECI2024', TRUE),
('Directeur IT', 'it.admin@mupeci.com', '$2y$10$wH1Q8QnQwQnQwQnQwQnQOeQnQwQnQwQnQwQnQwQnQwQnQwQnQwQ', 'admin', 'MUPECI2025', TRUE);

-- Insertion de visiteurs de test (données des derniers jours)
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
 DATE_SUB(NOW(), INTERVAL 1 DAY) + INTERVAL 9 HOUR, DATE_SUB(NOW(), INTERVAL 1 DAY) + INTERVAL 9 HOUR, DATE_SUB(NOW(), INTERVAL 1 DAY) + INTERVAL 10 HOUR, 1),

('Robert Essomba', '+237111222333', 'robert.essomba@email.com', 'CNI333444', 2, 'visite', 'completed',
 DATE_SUB(NOW(), INTERVAL 1 DAY) + INTERVAL 10 HOUR, DATE_SUB(NOW(), INTERVAL 1 DAY) + INTERVAL 10 HOUR, DATE_SUB(NOW(), INTERVAL 1 DAY) + INTERVAL 10 HOUR + INTERVAL 30 MINUTE, 2),

('Françoise Ndongo', '+237666777888', 'francoise.ndongo@email.com', 'CNI555666', 4, 'rendez-vous', 'completed',
 DATE_SUB(NOW(), INTERVAL 1 DAY) + INTERVAL 14 HOUR, DATE_SUB(NOW(), INTERVAL 1 DAY) + INTERVAL 14 HOUR, DATE_SUB(NOW(), INTERVAL 1 DAY) + INTERVAL 15 HOUR, 1),

-- Visiteurs de la semaine dernière
('Michel Onana', '+237777888999', 'michel.onana@email.com', 'CNI777888', 3, 'visite', 'completed',
 DATE_SUB(NOW(), INTERVAL 3 DAY) + INTERVAL 11 HOUR, DATE_SUB(NOW(), INTERVAL 3 DAY) + INTERVAL 11 HOUR, DATE_SUB(NOW(), INTERVAL 3 DAY) + INTERVAL 11 HOUR + INTERVAL 45 MINUTE, 1),

('Élise Manga', '+237888999000', 'elise.manga@email.com', 'CNI999000', 1, 'autres', 'completed',
 DATE_SUB(NOW(), INTERVAL 5 DAY) + INTERVAL 15 HOUR, DATE_SUB(NOW(), INTERVAL 5 DAY) + INTERVAL 15 HOUR, DATE_SUB(NOW(), INTERVAL 5 DAY) + INTERVAL 16 HOUR, 2);

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
