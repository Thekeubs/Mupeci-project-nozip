<?php
require_once '../database/config.php';
header('Content-Type: application/json');

$idNumber = $_GET['id_number'] ?? '';
if (!$idNumber) {
    echo json_encode(['success' => false, 'message' => 'Numéro manquant']);
    exit;
}

$pdo = getDB();
$stmt = $pdo->prepare('SELECT v.*, d.name as department_name FROM visitors v JOIN departments d ON v.department_id = d.id WHERE v.id_number = ? ORDER BY v.id DESC LIMIT 1');
$stmt->execute([$idNumber]);
$visitor = $stmt->fetch(PDO::FETCH_ASSOC);

if ($visitor) {
    echo json_encode([
        'success' => true,
        'visitor' => [
            'name' => $visitor['name'],
            'phone' => $visitor['phone'],
            'email' => $visitor['email'],
            'id_number' => $visitor['id_number'],
            'department_name' => $visitor['department_name'],
            'purpose' => $visitor['purpose']
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Aucun visiteur trouvé']);
} 