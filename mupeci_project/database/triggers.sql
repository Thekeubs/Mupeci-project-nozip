-- Triggers pour automatiser certaines tâches dans MUPECI
USE mupeci_db;

-- Drop all triggers if they exist (to ensure no old triggers remain)
DROP TRIGGER IF EXISTS update_last_login;
DROP TRIGGER IF EXISTS log_visitor_changes;
DROP TRIGGER IF EXISTS calculate_visit_duration;
DROP TRIGGER IF EXISTS generate_daily_stats;
DROP TRIGGER IF EXISTS validate_visitor_data;

DELIMITER //

DROP TRIGGER IF EXISTS update_last_login;//
-- Trigger pour mettre à jour last_login lors de la connexion
CREATE TRIGGER update_last_login
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    -- Ce trigger sera déclenché par l'application PHP lors de la connexion
    IF NEW.last_login != OLD.last_login THEN
        INSERT INTO activity_logs (user_id, action, table_name, record_id, ip_address)
        VALUES (NEW.id, 'LOGIN', 'users', NEW.id, 'system');
    END IF;
END //

DROP TRIGGER IF EXISTS log_visitor_changes;//
-- Trigger pour logger les modifications de visiteurs
CREATE TRIGGER log_visitor_changes
AFTER UPDATE ON visitors
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO activity_logs (user_id, action, table_name, record_id, old_values, new_values)
        VALUES (
            NEW.created_by, 
            CONCAT('STATUS_CHANGE_', OLD.status, '_TO_', NEW.status),
            'visitors',
            NEW.id,
            JSON_OBJECT('status', OLD.status, 'updated_at', OLD.updated_at),
            JSON_OBJECT('status', NEW.status, 'updated_at', NEW.updated_at)
        );
    END IF;
END //

DROP TRIGGER IF EXISTS calculate_visit_duration;//
-- Trigger pour calculer automatiquement les durées dans visit_sessions
CREATE TRIGGER calculate_visit_duration
BEFORE UPDATE ON visit_sessions
FOR EACH ROW
BEGIN
    -- Calculer le temps d'attente
    IF NEW.started_at IS NOT NULL AND OLD.started_at IS NULL THEN
        SET NEW.wait_time_minutes = TIMESTAMPDIFF(MINUTE, NEW.checked_in_at, NEW.started_at);
    END IF;
    
    -- Calculer la durée de la visite
    IF NEW.completed_at IS NOT NULL AND OLD.completed_at IS NULL AND NEW.started_at IS NOT NULL THEN
        SET NEW.visit_duration_minutes = TIMESTAMPDIFF(MINUTE, NEW.started_at, NEW.completed_at);
    END IF;
END //

DROP TRIGGER IF EXISTS generate_daily_stats;//
-- Trigger pour générer automatiquement les statistiques quotidiennes
CREATE TRIGGER generate_daily_stats
AFTER UPDATE ON visitors
FOR EACH ROW
BEGIN
    -- Générer les stats quand une visite est terminée
    IF OLD.status != 'completed' AND NEW.status = 'completed' THEN
        CALL GenerateDailyReport(DATE(NEW.checked_in_at));
    END IF;
END //

DROP TRIGGER IF EXISTS validate_visitor_data;//
-- Trigger pour valider les données avant insertion
CREATE TRIGGER validate_visitor_data
BEFORE INSERT ON visitors
FOR EACH ROW
BEGIN
    -- Valider le format du téléphone camerounais
    IF NEW.phone NOT REGEXP '^[+]237[0-9]{9}$' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Format de téléphone invalide. Utilisez +237XXXXXXXXX';
    END IF;
    
    -- Valider l'email si fourni
    IF NEW.email IS NOT NULL AND NEW.email != '' AND NEW.email NOT REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+[.][A-Za-z]{2,}$' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Format d''email invalide';
    END IF;
    
    -- Vérifier que le département est actif
    IF NOT EXISTS (SELECT 1 FROM departments WHERE id = NEW.department_id AND active = TRUE) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Le département sélectionné n''est pas actif';
    END IF;
END //

DELIMITER ;
