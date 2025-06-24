<?php
require_once '../includes/functions.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}
$id = intval($_POST['admin_id'] ?? 0);
$name = trim($_POST['admin_name'] ?? '');
$email = trim($_POST['admin_email'] ?? '');
$password = $_POST['admin_password'] ?? '';
$secret = trim($_POST['admin_secret'] ?? '');
if (!$id || !$name || !$email || !$secret) {
    echo json_encode(['success' => false, 'message' => 'Tous les champs sont requis']);
    exit;
}
if (!validateEmail($email)) {
    echo json_encode(['success' => false, 'message' => 'Email invalide']);
    exit;
}
$pdo = getDB();
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
$stmt->execute([$email, $id]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Cet email existe déjà']);
    exit;
}
if ($password) {
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, password = ?, secret_code = ? WHERE id = ? AND user_type = "admin"');
    $ok = $stmt->execute([$name, $email, $hash, $secret, $id]);
} else {
    $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, secret_code = ? WHERE id = ? AND user_type = "admin"');
    $ok = $stmt->execute([$name, $email, $secret, $id]);
}
if ($ok) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la modification']);
} 