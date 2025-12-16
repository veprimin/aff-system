<?php
require_once __DIR__ . '/connect.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/helpers.php';

$action = $_GET['action'] ?? '';

if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $_SESSION['error'] = 'Email and password are required.';
        header('Location: /clinicsecret/login.php');
        exit;
    }

    $clientStmt = $pdo->prepare('SELECT * FROM clients WHERE slug = ? LIMIT 1');
    $clientStmt->execute([CLIENT_SLUG]);
    $client = $clientStmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare('SELECT * FROM referral_users WHERE client_id = ? AND email = ? LIMIT 1');
    $stmt->execute([$client['id'], $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || empty($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
        $_SESSION['error'] = 'Invalid login.';
        header('Location: /clinicsecret/login.php');
        exit;
    }

    $_SESSION['user_id'] = $user['id'];
    header('Location: /clinicsecret/dashboard.php');
    exit;
}

if ($action === 'forgot' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (!$email) {
        $_SESSION['error'] = 'Email required.';
        header('Location: /clinicsecret/forgot-password.php');
        exit;
    }

    $client = get_client($pdo);
    $stmt = $pdo->prepare('SELECT * FROM referral_users WHERE client_id = ? AND email = ? LIMIT 1');
    $stmt->execute([$client['id'], $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        require_once __DIR__ . '/send_set_password.php';
        send_password_setup_email($user);
    }

    $_SESSION['success'] = 'If this email exists, a reset link was sent.';
    header('Location: /clinicsecret/forgot-password.php');
    exit;
}

http_response_code(400);
echo 'Invalid auth action';
