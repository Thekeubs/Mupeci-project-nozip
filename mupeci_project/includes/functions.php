<?php
require_once __DIR__ . '/../database/config.php';

function initializeData() {
    if (!isset($_SESSION['departments'])) {
        $_SESSION['departments'] = [
            ['id' => 1, 'name' => 'Direction Générale', 'active' => true],
            ['id' => 2, 'name' => 'Ressources Humaines', 'active' => true],
            ['id' => 3, 'name' => 'Comptabilité', 'active' => true],
            ['id' => 4, 'name' => 'Service Client', 'active' => true],
            ['id' => 5, 'name' => 'IT Support', 'active' => false]
        ];
    }
    
    if (!isset($_SESSION['visitors'])) {
        $_SESSION['visitors'] = [];
    }
    
    if (!isset($_SESSION['users'])) {
        $_SESSION['users'] = [
            'receptionists' => [
                ['id' => 1, 'name' => 'Marie Dupont', 'email' => 'marie@mupeci.com', 'password' => 'password123', 'active' => true],
                ['id' => 2, 'name' => 'Jean Martin', 'email' => 'jean@mupeci.com', 'password' => 'password123', 'active' => true]
            ],
            'admins' => [
                ['id' => 1, 'name' => 'Admin Principal', 'email' => 'admin@mupeci.com', 'password' => 'admin123', 'secret_code' => 'MUPECI2024']
            ]
        ];
    }
}

function validateCameroonPhone($phone) {
    return preg_match('/^\+237[0-9]{9}$/', $phone);
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function getActiveDepartments() {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT * FROM departments WHERE active = 1");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getTodayVisitors() {
    $pdo = getDB();
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT * FROM visitors WHERE DATE(checked_in_at) = ?");
    $stmt->execute([$today]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getVisitorsByStatus($status) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM visitors WHERE status = ?");
    $stmt->execute([$status]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getDepartmentStats() {
    $pdo = getDB();
    $today = date('Y-m-d');
    $stmt = $pdo->query("SELECT d.name, COUNT(v.id) as count FROM departments d LEFT JOIN visitors v ON v.department_id = d.id AND DATE(v.checked_in_at) = '$today' GROUP BY d.id");
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    usort($stats, function($a, $b) { return $b['count'] - $a['count']; });
    return $stats;
}

function getHourlyStats() {
    $pdo = getDB();
    $today = date('Y-m-d');
    $hourly = array_fill(8, 10, 0); // 8h à 17h
    $stmt = $pdo->prepare("SELECT checked_in_at FROM visitors WHERE DATE(checked_in_at) = ?");
    $stmt->execute([$today]);
    $visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($visitors as $visitor) {
        $hour = (int)date('H', strtotime($visitor['checked_in_at']));
        if ($hour >= 8 && $hour <= 17) {
            $hourly[$hour]++;
        }
    }
    return $hourly;
}
?>
