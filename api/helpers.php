<?php

// ======================================================================
//  INITIALS FROM FIRST + LAST NAME ONLY
// ======================================================================
function makeInitialsFromName(string $firstName, string $lastName): string
{
    $first = strtoupper(substr(trim($firstName), 0, 1));
    $last  = strtoupper(substr(trim($lastName), 0, 1));

    if ($first && $last) {
        return $first . $last;       // Priya Charmin -> PC
    }

    if ($first && !$last) {
        return $first . $first;      // Only "Priya" -> PP
    }

    if (!$first && $last) {
        return $last . $last;        // Only "Charmin" -> CC
    }

    return "XX";                     // Fallback
}



// ======================================================================
//  REFERRAL CODE GENERATOR (NO HYPHENS)
//  Format: CS{INITIALS}{HEX}
//  Example: CSPC9AF31
// ======================================================================
function generateReferralCode(PDO $pdo, int $clientId, string $initials): string
{
    $brand = 'CS'; // Clinic Secret

    do {
        $randomHex = strtoupper(substr(bin2hex(random_bytes(3)), 0, 5)); // 5-char hex
        $code      = $brand . $initials . $randomHex;                    // CSPC9AF31

        $stmt = $pdo->prepare("
            SELECT id 
            FROM referral_users 
            WHERE client_id = ? AND referral_code = ?
            LIMIT 1
        ");
        $stmt->execute([$clientId, $code]);
        $exists = $stmt->fetch(PDO::FETCH_ASSOC);

    } while ($exists);

    return $code;
}



// ======================================================================
//  PASSWORD RESET TOKEN (uses password_reset_tokens table)
//  Expected columns: referral_user_id, token, expires_at
// ======================================================================
function createPasswordResetToken(PDO $pdo, int $userId): string
{
    $token   = bin2hex(random_bytes(32));                     // 64 chars
    $expires = date('Y-m-d H:i:s', time() + 86400);           // +24h

    // Remove previous tokens for this user
    $pdo->prepare("
        DELETE FROM password_reset_tokens 
        WHERE referral_user_id = ?
    ")->execute([$userId]);

    // Insert fresh token
    $stmt = $pdo->prepare("
        INSERT INTO password_reset_tokens (referral_user_id, token, expires_at)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$userId, $token, $expires]);

    return $token;
}



// ======================================================================
//  SEND AMBASSADOR WELCOME EMAIL + PASSWORD SET LINK
// ======================================================================
function sendAmbassadorWelcomeEmail(array $user, string $resetLink): void
{
    $email     = $user['email'];
    $firstName = ucfirst($user['first_name'] ?? '');
    $refCode   = trim($user['referral_code'] ?? '');
    $refLink   = $refCode !== ''
        ? "https://introduce.now/clinicsecret/refer/{$refCode}"
        : ($user['referral_link'] ?? '');

    $subject = "Your ClinicSecret Ambassador Account";

    $message = "
Hi {$firstName},

Your ClinicSecret ambassador account has been created!

Your personal referral link:
{$refLink}

Before logging in, please create your password using this secure link:
{$resetLink}

After setting your password you can log in to:

• View your referral link  
• Track clicks and signups  
• See your earnings and payouts  

Welcome aboard!

ClinicSecret Team
";

    $headers = "From: ClinicSecret <noreply@introduce.now>\r\n";

    @mail($email, $subject, $message, $headers);
}



// ======================================================================
//  OPTIONAL LEGACY EMAIL (IF ANY OLD CODE STILL CALLS THIS)
// ======================================================================
function sendReferralWelcomeEmail(string $email, string $referralCode): void
{
    $refLink = "https://clinicsecret.com/?ref=" . $referralCode;

    $subject = "Your ClinicSecret Referral Link";
    $message = "
Hello,

Your ClinicSecret referral link is:

{$refLink}

Share this link with friends to earn rewards.

ClinicSecret Team
";

    $headers = "From: ClinicSecret <noreply@introduce.now>\r\n";

    @mail($email, $subject, $message, $headers);
}



// ======================================================================
//  CREATE REFERRAL ORDER + PAYOUT
// ======================================================================
function create_referral_order_and_payout(
    PDO $pdo,
    array $client,
    array $refUser,
    string $referredEmail,
    string $samcartOrderId,
    ?string $samcartSubscriptionId,
    string $productType,
    float $orderAmount,
    ?float $overridePayoutAmount,
    ?string $overridePayoutType
): void {
    // Insert order record
    $stmt = $pdo->prepare("
        INSERT INTO referral_orders (
            client_id,
            referral_user_id,
            referral_code,
            referred_email,
            samcart_order_id,
            samcart_subscription_id,
            product_type,
            order_amount
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $client['id'],
        $refUser['id'],
        $refUser['referral_code'],
        $referredEmail,
        $samcartOrderId,
        $samcartSubscriptionId,
        $productType,
        $orderAmount
    ]);

    // If no payout configured, stop here
    if ($overridePayoutAmount <= 0 || empty($overridePayoutType)) {
        return;
    }

    $periodMonth = date('Y-m-01');

    // Insert payout record
    $stmt = $pdo->prepare("
        INSERT INTO referral_payouts (
            client_id,
            referral_user_id,
            referred_email,
            product_type,
            payout_amount,
            payout_type,
            period_month,
            status
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
    ");
    $stmt->execute([
        $client['id'],
        $refUser['id'],
        $referredEmail,
        $productType,
        $overridePayoutAmount,
        $overridePayoutType,
        $periodMonth
    ]);
}

?>
