-- Index supplémentaires pour optimiser les performances de MUPECI
USE mupeci_db;

-- Index composites pour les requêtes fréquentes
CREATE INDEX idx_visitors_checked_in_at_status ON visitors (checked_in_at, status); -- FIXED
CREATE INDEX idx_visitors_department_checked_in_at ON visitors (department_id, checked_in_at); -- FIXED
CREATE INDEX idx_visitors_created_by_checked_in_at ON visitors (created_by, checked_in_at); -- FIXED

-- Index pour les recherches par numéro de téléphone et email
CREATE INDEX idx_visitors_phone ON visitors (phone);
CREATE INDEX idx_visitors_email ON visitors (email);

-- Index pour les statistiques par heure
CREATE INDEX idx_visitors_checked_in_at_hour ON visitors (checked_in_at); -- You cannot index HOUR(checked_in_at) directly

-- Index pour les sessions de visite
CREATE INDEX idx_visit_sessions_checked_in_at_dept ON visit_sessions (checked_in_at, department_id); -- FIXED
CREATE INDEX idx_visit_sessions_receptionist_checked_in_at ON visit_sessions (receptionist_id, checked_in_at); -- FIXED

-- Index pour les logs d'activité
CREATE INDEX idx_activity_logs_user_action ON activity_logs (user_id, action, created_at); -- FIXED

-- Index pour les recherches full-text (si supporté)
-- ALTER TABLE visitors ADD FULLTEXT(name, email);
-- ALTER TABLE departments ADD FULLTEXT(name, description);