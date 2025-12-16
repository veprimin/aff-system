<?php
require_once __DIR__ . '/connect.php';
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT COUNT(*) FROM referral_clicks WHERE referral_user_id = ?');
$stmt->execute([$userId]);
$totalClicks = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM referral_orders WHERE referral_user_id = ?');
$stmt->execute([$userId]);
$totalReferrals = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM referral_orders WHERE referral_user_id = ? AND subscription_active = 1');
$stmt->execute([$userId]);
$activeSubs = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COALESCE(SUM(payout_amount),0) FROM referral_payouts WHERE referral_user_id = ?');
$stmt->execute([$userId]);
$lifetimePayout = (float)$stmt->fetchColumn();

$currentMonth = date('Y-m-01');
$stmt = $pdo->prepare('SELECT COALESCE(SUM(payout_amount),0) FROM referral_payouts WHERE referral_user_id = ? AND period_month = ? AND status = "pending"');
$stmt->execute([$userId, $currentMonth]);
$currentMonthPayout = (float)$stmt->fetchColumn();

echo json_encode([
    'total_clicks' => $totalClicks,
    'total_referrals' => $totalReferrals,
    'active_subscriptions' => $activeSubs,
    'lifetime_payout' => $lifetimePayout,
    'current_month_payout' => $currentMonthPayout,
]);
