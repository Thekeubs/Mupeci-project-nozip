<?php
function checkAuth($userType) {
    if (!isset($_SESSION['user']) || $_SESSION['user']['type'] !== $userType) {
        header('Location: ../login/' . $userType . '.php');
        exit();
    }
}

function login($email, $password, $userType) {
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE (email = ? OR name = ?) AND user_type = ? AND active = 1 LIMIT 1');
    $stmt->execute([$email, $email, $userType]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'type' => $userType
        ];
        return true;
    }
    return false;
}

function verifySecretCode($code) {
    if (!isset($_SESSION['user']) || $_SESSION['user']['type'] !== 'admin') {
        return false;
    }
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT secret_code FROM users WHERE id = ? AND user_type = "admin" AND active = 1 LIMIT 1');
    $stmt->execute([$_SESSION['user']['id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && $user['secret_code'] === $code) {
        return true;
    }
    return false;
}
?>
