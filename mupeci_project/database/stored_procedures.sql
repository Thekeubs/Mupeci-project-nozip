-- Procédures stockées pour MUPECI
USE mupeci_db;

DELIMITER //

-- Procédure pour obtenir les statistiques du jour
CREATE PROCEDURE GetTodayStats()
BEGIN
    SELECT 
        COUNT(*) as total_visitors,
        SUM(CASE WHEN status = 'waiting' THEN 1 ELSE 0 END) as waiting_count,
        SUM(CASE WHEN status = 'in-progress' THEN 1 ELSE 0 END) as in_progress_count,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
        SUM(CASE WHEN purpose = 'rendez-vous' THEN 1 ELSE 0 END) as appointments_count
    FROM visitors 
    WHERE DATE(checked_in_at) = CURDATE();
END //

-- Procédure pour obtenir les statistiques par département
CREATE PROCEDURE GetDepartmentStats(IN days_back INT)
BEGIN
    SELECT 
        d.name as department_name,
        COUNT(v.id) as visitor_count,
        AVG(CASE 
            WHEN v.completed_at IS NOT NULL AND v.started_at IS NOT NULL 
            THEN TIMESTAMPDIFF(MINUTE, v.started_at, v.completed_at) 
            ELSE NULL 
        END) as avg_visit_duration
    FROM departments d
    LEFT JOIN visitors v ON d.id = v.department_id 
        AND v.checked_in_at >= DATE_SUB(CURDATE(), INTERVAL days_back DAY)
    WHERE d.active = TRUE
    GROUP BY d.id, d.name
    ORDER BY visitor_count DESC;
END //

-- Procédure pour obtenir les statistiques par heure
CREATE PROCEDURE GetHourlyStats(IN target_date DATE)
BEGIN
    SELECT 
        HOUR(checked_in_at) as hour_of_day,
        COUNT(*) as visitor_count
    FROM visitors 
    WHERE DATE(checked_in_at) = target_date
    GROUP BY HOUR(checked_in_at)
    ORDER BY hour_of_day;
END //

-- Procédure pour démarrer une visite
CREATE PROCEDURE StartVisit(IN visitor_id INT, IN user_id INT)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    UPDATE visitors 
    SET status = 'in-progress', 
        started_at = NOW(),
        updated_at = NOW()
    WHERE id = visitor_id AND status = 'waiting';
    
    INSERT INTO activity_logs (user_id, action, table_name, record_id)
    VALUES (user_id, 'START_VISIT', 'visitors', visitor_id);
    
    COMMIT;
END //

-- Procédure pour terminer une visite
CREATE PROCEDURE CompleteVisit(IN visitor_id INT, IN user_id INT)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    UPDATE visitors 
    SET status = 'completed', 
        completed_at = NOW(),
        updated_at = NOW()
    WHERE id = visitor_id AND status = 'in-progress';
    
    INSERT INTO activity_logs (user_id, action, table_name, record_id)
    VALUES (user_id, 'COMPLETE_VISIT', 'visitors', visitor_id);
    
    COMMIT;
END //

-- Procédure pour nettoyer les anciennes données
CREATE PROCEDURE CleanOldData(IN days_to_keep INT)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Supprimer les anciens logs d'activité
    DELETE FROM activity_logs 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL days_to_keep DAY);
    
    -- Supprimer les anciennes sessions de visite
    DELETE FROM visit_sessions 
    WHERE checked_in_at < DATE_SUB(NOW(), INTERVAL days_to_keep DAY);
    
    -- Supprimer les anciens visiteurs (garder seulement les données récentes)
    DELETE FROM visitors 
    WHERE checked_in_at < DATE_SUB(NOW(), INTERVAL days_to_keep DAY)
    AND status = 'completed';
    
    COMMIT;
END //

-- Procédure pour générer le rapport quotidien
CREATE PROCEDURE GenerateDailyReport(IN report_date DATE)
BEGIN
    DECLARE total_visitors INT DEFAULT 0;
    DECLARE completed_visits INT DEFAULT 0;
    DECLARE cancelled_visits INT DEFAULT 0;
    DECLARE avg_wait_time DECIMAL(5,2) DEFAULT 0.00;
    DECLARE avg_visit_duration DECIMAL(5,2) DEFAULT 0.00;
    DECLARE peak_hour INT DEFAULT 0;
    
    -- Calculer les statistiques
    SELECT 
        COUNT(*),
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END),
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END)
    INTO total_visitors, completed_visits, cancelled_visits
    FROM visitors 
    WHERE DATE(checked_in_at) = report_date;
    
    -- Calculer le temps d'attente moyen
    SELECT AVG(TIMESTAMPDIFF(MINUTE, checked_in_at, started_at))
    INTO avg_wait_time
    FROM visitors 
    WHERE DATE(checked_in_at) = report_date 
    AND started_at IS NOT NULL;
    
    -- Calculer la durée moyenne des visites
    SELECT AVG(TIMESTAMPDIFF(MINUTE, started_at, completed_at))
    INTO avg_visit_duration
    FROM visitors 
    WHERE DATE(checked_in_at) = report_date 
    AND completed_at IS NOT NULL;
    
    -- Trouver l'heure de pointe
    SELECT HOUR(checked_in_at)
    INTO peak_hour
    FROM visitors 
    WHERE DATE(checked_in_at) = report_date
    GROUP BY HOUR(checked_in_at)
    ORDER BY COUNT(*) DESC
    LIMIT 1;
    
    -- Insérer ou mettre à jour les statistiques quotidiennes
    INSERT INTO daily_stats (stat_date, total_visitors, completed_visits, cancelled_visits, average_wait_time, average_visit_duration, peak_hour)
    VALUES (report_date, total_visitors, completed_visits, cancelled_visits, IFNULL(avg_wait_time, 0), IFNULL(avg_visit_duration, 0), IFNULL(peak_hour, 0))
    ON DUPLICATE KEY UPDATE
        total_visitors = VALUES(total_visitors),
        completed_visits = VALUES(completed_visits),
        cancelled_visits = VALUES(cancelled_visits),
        average_wait_time = VALUES(average_wait_time),
        average_visit_duration = VALUES(average_visit_duration),
        peak_hour = VALUES(peak_hour),
        updated_at = NOW();
END //

DELIMITER ;
