<?php
require_once __DIR__ . '/../api/connect.php';
require_once __DIR__ . '/../config.php';

$code = $_GET['code'] ?? null;

if (!$code) {
    header("Location: https://clinicsecret.com");
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM clients WHERE slug = ? LIMIT 1');
$stmt->execute([CLIENT_SLUG]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare('SELECT * FROM referral_users WHERE client_id = ? AND referral_code = ? LIMIT 1');
$stmt->execute([$client['id'], $code]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: https://clinicsecret.com");
    exit;
}

// Track click
$trackingId = bin2hex(random_bytes(16));
$ip = $_SERVER['REMOTE_ADDR'] ?? null;
$ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

$stmt = $pdo->prepare('
    INSERT INTO referral_clicks (client_id, referral_user_id, referral_code, tracking_id, ip, user_agent)
    VALUES (?, ?, ?, ?, ?, ?)
');
$stmt->execute([$client['id'], $user['id'], $user['referral_code'], $trackingId, $ip, $ua]);

// Store cookies
setcookie("referral_code", $user['referral_code'], time() + 30 * 86400, "/");
setcookie("tracking_id", $trackingId, time() + 30 * 86400, "/");

// Build URL
$ref = urlencode($user['referral_code']);
$affid = urlencode($user['referral_code']);
$subid = urlencode($trackingId);

$redirectUrl = "https://clinicsecret.com/?ref={$ref}&affid={$affid}&subid={$subid}&utm_source=referral&utm_medium=ambassador&utm_campaign=clinicsecret";

// Redirect
header("Location: $redirectUrl");
exit;
