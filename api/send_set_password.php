<?php
require_once __DIR__ . '/connect.php';

function send_password_setup_email(array $user)
{
    global $pdo;

    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour

    $stmt = $pdo->prepare('
        INSERT INTO password_reset_tokens (referral_user_id, token, expires_at)
        VALUES (?, ?, ?)
    ');
    $stmt->execute([$user['id'], $token, $expires]);

    $link = 'https://introduce.now/clinicsecret/set-password.php?token=' . urlencode($token);

    $subject = 'Set Your Clinic Secret Referral Portal Password';
    $message = "Hello {$user['first_name']},

Welcome to the Clinic Secret Referral Portal!

Please set your password using the secure link below:
$link

This link expires in 1 hour.

Thank you,
Clinic Secret Team
";

    $headers = "From: no-reply@introduce.now\r\n";
    mail($user['email'], $subject, $message, $headers);
}
