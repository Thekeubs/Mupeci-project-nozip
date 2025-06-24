<?php
require_once '../includes/functions.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}
$id = intval($_POST['rec_id'] ?? 0);
$name = trim($_POST['rec_name'] ?? '');
$email = trim($_POST['rec_email'] ?? '');
$password = $_POST['rec_password'] ?? '';
if (!$id || !$name || !$email) {
    echo json_encode(['success' => false, 'message' => 'ID, nom et email requis']);
    exit;
}
if (!validateEmail($email)) {
    echo json_encode(['success' => false, 'message' => 'Email invalide']);
    exit;
}
$pdo = getDB();
// Vérifier unicité email (hors ce user)
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
$stmt->execute([$email, $id]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Cet email existe déjà']);
    exit;
}
if ($password) {
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, password = ? WHERE id = ? AND user_type = "receptionist"');
    $ok = $stmt->execute([$name, $email, $hash, $id]);
} else {
    $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ? WHERE id = ? AND user_type = "receptionist"');
    $ok = $stmt->execute([$name, $email, $id]);
}
if ($ok) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la modification']);
} 