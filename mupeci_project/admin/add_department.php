<?php
require_once '../includes/functions.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$name = trim($_POST['dept_name'] ?? '');
if (!$name) {
    echo json_encode(['success' => false, 'message' => 'Le nom est requis']);
    exit;
}

$pdo = getDB();
$stmt = $pdo->prepare('INSERT INTO departments (name, active) VALUES (?, 1)');
if ($stmt->execute([$name])) {
    $id = $pdo->lastInsertId();
    echo json_encode([
        'success' => true,
        'department' => [
            'id' => $id,
            'name' => $name,
            'active' => 1
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout']);
} 