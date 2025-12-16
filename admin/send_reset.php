<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../api/connect.php';
require_once __DIR__ . '/../api/send_set_password.php';

if (empty($_SESSION['admin_id'])) {
    header('Location: /clinicsecret/admin/index.php');
    exit;
}

$userId = (int)($_POST['user_id'] ?? 0);
if ($userId) {
    $stmt = $pdo->prepare('SELECT * FROM referral_users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        send_password_setup_email($user);
    }
}

header('Location: /clinicsecret/admin/users.php');
exit;
