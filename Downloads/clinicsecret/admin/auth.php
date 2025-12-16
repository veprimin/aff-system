<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../api/connect.php';

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (!$email || !$password) {
    $_SESSION['admin_error'] = 'Email and password required.';
    header('Location: /clinicsecret/admin/index.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM admin_users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin || !password_verify($password, $admin['password_hash'])) {
    $_SESSION['admin_error'] = 'Invalid credentials.';
    header('Location: /clinicsecret/admin/index.php');
    exit;
}

$_SESSION['admin_id'] = $admin['id'];
header('Location: /clinicsecret/admin/dashboard.php');
