<?php
require_once '../includes/functions.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}
$id = intval($_POST['dept_id'] ?? 0);
$name = trim($_POST['dept_name'] ?? '');
if (!$id || !$name) {
    echo json_encode(['success' => false, 'message' => 'ID et nom requis']);
    exit;
}
$pdo = getDB();
$stmt = $pdo->prepare('UPDATE departments SET name = ? WHERE id = ?');
if ($stmt->execute([$name, $id])) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la modification']);
} 