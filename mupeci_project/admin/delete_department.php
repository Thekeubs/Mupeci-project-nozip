<?php
require_once '../includes/functions.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}
$id = intval($_POST['dept_id'] ?? 0);
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID requis']);
    exit;
}
$pdo = getDB();
// Vérifier s'il y a des visiteurs liés
$stmt = $pdo->prepare('SELECT COUNT(*) FROM visitors WHERE department_id = ?');
$stmt->execute([$id]);
if ($stmt->fetchColumn() > 0) {
    echo json_encode(['success' => false, 'message' => 'Impossible de supprimer : des visiteurs sont liés à ce département.']);
    exit;
}
$stmt = $pdo->prepare('DELETE FROM departments WHERE id = ?');
if ($stmt->execute([$id])) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression']);
} 