<?php
require_once '../includes/functions.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}
$name = trim($_POST['admin_name'] ?? '');
$email = trim($_POST['admin_email'] ?? '');
$password = $_POST['admin_password'] ?? '';
$secret = trim($_POST['admin_secret'] ?? '');
if (!$name || !$email || !$password || !$secret) {
    echo json_encode(['success' => false, 'message' => 'Tous les champs sont requis']);
    exit;
}
if (!validateEmail($email)) {
    echo json_encode(['success' => false, 'message' => 'Email invalide']);
    exit;
}
$pdo = getDB();
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Cet email existe déjà']);
    exit;
}
$hash = password_hash($password, PASSWORD_BCRYPT);
$stmt = $pdo->prepare('INSERT INTO users (name, email, password, user_type, secret_code, active) VALUES (?, ?, ?, "admin", ?, 1)');
if ($stmt->execute([$name, $email, $hash, $secret])) {
    $id = $pdo->lastInsertId();
    echo json_encode([
        'success' => true,
        'admin' => [
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'active' => 1
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout']);
} 