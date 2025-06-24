-- Vues pour simplifier les requêtes courantes dans MUPECI
USE mupeci_db;

-- Vue pour les visiteurs actifs (en attente ou en cours)
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

-- Vue pour l'historique des visites terminées
CREATE VIEW completed_visits AS
SELECT 
    v.id,
    v.name,
    v.phone,
    v.email,
    v.id_number,
    d.name as department_name,
    v.purpose,
    v.checked_in_at,
    v.started_at,
    v.completed_at,
    TIMESTAMPDIFF(MINUTE, v.checked_in_at, v.started_at) as wait_time_minutes,
    TIMESTAMPDIFF(MINUTE, v.started_at, v.completed_at) as visit_duration_minutes,
    u.name as receptionist_name,
    DATE(v.checked_in_at) as visit_date
FROM visitors v
JOIN departments d ON v.department_id = d.id
JOIN users u ON v.created_by = u.id
WHERE v.status = 'completed'
ORDER BY v.completed_at DESC;

-- Vue pour les statistiques par heure
CREATE VIEW hourly_stats AS
SELECT 
    DATE(v.checked_in_at) as visit_date,
    HOUR(v.checked_in_at) as hour_of_day,
    COUNT(*) as visitor_count,
    COUNT(CASE WHEN v.status = 'completed' THEN 1 END) as completed_count,
    AVG(CASE 
        WHEN v.completed_at IS NOT NULL AND v.started_at IS NOT NULL 
        THEN TIMESTAMPDIFF(MINUTE, v.started_at, v.completed_at) 
        ELSE NULL 
    END) as avg_visit_duration
FROM visitors v
WHERE v.checked_in_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
GROUP BY DATE(v.checked_in_at), HOUR(v.checked_in_at)
ORDER BY visit_date DESC, hour_of_day ASC;

-- Vue pour le tableau de bord administrateur
CREATE VIEW admin_dashboard AS
SELECT 
    (SELECT COUNT(*) FROM users WHERE user_type = 'receptionist' AND active = TRUE) as active_receptionists,
    (SELECT COUNT(*) FROM departments WHERE active = TRUE) as active_departments,
    (SELECT COUNT(*) FROM visitors WHERE DATE(checked_in_at) = CURDATE()) as today_visitors,
    (SELECT COUNT(*) FROM visitors WHERE status = 'waiting') as waiting_visitors,
    (SELECT COUNT(*) FROM visitors WHERE status = 'in-progress') as in_progress_visitors,
    (SELECT COUNT(*) FROM visitors WHERE DATE(checked_in_at) = CURDATE() AND status = 'completed') as completed_today;

-- Vue pour les utilisateurs actifs (sans mot de passe)
CREATE VIEW active_users AS
SELECT 
    u.id,
    u.name,
    u.email,
    u.user_type,
    u.active,
    u.last_login,
    u.created_at,
    COUNT(v.id) as visitors_created_today
FROM users u
LEFT JOIN visitors v ON u.id = v.created_by AND DATE(v.created_at) = CURDATE()
WHERE u.active = TRUE
GROUP BY u.id, u.name, u.email, u.user_type, u.active, u.last_login, u.created_at
ORDER BY u.user_type, u.name;
