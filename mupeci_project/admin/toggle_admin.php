<?php
require_once '../includes/functions.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}
$id = intval($_POST['admin_id'] ?? 0);
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID requis']);
    exit;
}
$pdo = getDB();
$stmt = $pdo->prepare('UPDATE users SET active = NOT active WHERE id = ? AND user_type = "admin"');
if ($stmt->execute([$id])) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Erreur lors du changement de statut']);
} 