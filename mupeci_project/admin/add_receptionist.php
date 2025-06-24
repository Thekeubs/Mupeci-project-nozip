<?php
require_once '../includes/functions.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$name = trim($_POST['rec_name'] ?? '');
$email = trim($_POST['rec_email'] ?? '');
$password = $_POST['rec_password'] ?? '';

if (!$name || !$email || !$password) {
    echo json_encode(['success' => false, 'message' => 'Tous les champs sont requis']);
    exit;
}
if (!validateEmail($email)) {
    echo json_encode(['success' => false, 'message' => 'Email invalide']);
    exit;
}

$pdo = getDB();
// Vérifier si l'email existe déjà
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Cet email existe déjà']);
    exit;
}

$hash = password_hash($password, PASSWORD_BCRYPT);
$stmt = $pdo->prepare('INSERT INTO users (name, email, password, user_type, active) VALUES (?, ?, ?, "receptionist", 1)');
if ($stmt->execute([$name, $email, $hash])) {
    $id = $pdo->lastInsertId();
    echo json_encode([
        'success' => true,
        'receptionist' => [
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'active' => 1
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout']);
} 