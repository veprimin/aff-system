<?php
require_once __DIR__ . '/connect.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/helpers.php';

/**
 * --------------------------------------------------------------------
 * DEBUG LOGGER
 * --------------------------------------------------------------------
 */
function debug_log($label, $data = null) {
    $file = __DIR__ . "/error_debug.log";
    $ts = date("Y-m-d H:i:s");
    $msg = "[$ts] $label";
    if ($data !== null) {
        $msg .= " => " . print_r($data, true);
    }
    $msg .= "\n";
    @file_put_contents($file, $msg, FILE_APPEND);
}

/**
 * --------------------------------------------------------------------
 * SAVE RAW PAYLOAD
 * --------------------------------------------------------------------
 */
function saveRawWebhook(PDO $pdo, $eventType, $payload) {
    try {
        $payloadToStore = is_string($payload)
            ? $payload
            : json_encode($payload, JSON_UNESCAPED_SLASHES);

        $stmt = $pdo->prepare("
            INSERT INTO webhook_raw_logs (event_type, payload, received_at)
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$eventType, $payloadToStore]);
    } catch (Exception $e) {
        debug_log("ERROR_INSERTING_RAW_WEBHOOK", $e->getMessage());
    }
}

debug_log("=== WEBHOOK STARTED ===");

/**
 * --------------------------------------------------------------------
 * RAW INPUT
 * --------------------------------------------------------------------
 */
$raw = file_get_contents("php://input");
debug_log("RAW INPUT", $raw);

$data = json_decode($raw, true);
debug_log("JSON-DECODED RAW", $data);

/**
 * --------------------------------------------------------------------
 * DB LOG RAW PAYLOAD
 * --------------------------------------------------------------------
 */
$initialEventType = $data['event'] ?? "unknown_format";
saveRawWebhook($pdo, $initialEventType, $raw);

/**
 * --------------------------------------------------------------------
 * NORMALIZATION (NEW SAMCART FORMAT)
 * --------------------------------------------------------------------
 */
debug_log("STEP: NORMALIZATION START", $data);

// NEW SAMCART FORMAT
if (is_array($data) && isset($data['type']) && $data['type'] === "Order") {
    debug_log("Detected NEW SamCart Payload");

    $cust = $data["customer"] ?? [];
    $ord  = $data["order"] ?? [];
    $prod = $data["products"][0] ?? [];

    $data = [
        "event" => "order.completed",
        "data"  => [
            "order" => [
                "id"            => $ord['id'] ?? null,
                "total"         => isset($ord['total']) ? (float)$ord['total'] : 0,
                "product_id"    => $prod['id'] ?? null,
                "referral_code" => $ord['analytics']['campaign'] ?? null,
                "tracking_id"   => $ord['analytics']['content'] ?? null
            ],
            "customer" => [
                "email"      => $cust['email'] ?? null,
                "first_name" => $cust['first_name'] ?? "",
                "last_name"  => $cust['last_name'] ?? ""
            ],
            "subscription" => []
        ]
    ];

    debug_log("Normalized NEW SamCart Format", $data);
}

if (!$data || !isset($data['event'])) {
    debug_log("Payload not recognized or missing event");
    http_response_code(200);
    exit("Webhook received but not recognized");
}

$event = $data['event'];
debug_log("EVENT DETECTED", $event);

/**
 * --------------------------------------------------------------------
 * LOG NORMALIZED
 * --------------------------------------------------------------------
 */
logWebhook($pdo, $event, $data);

/**
 * --------------------------------------------------------------------
 * EXTRACT ORDER & CUSTOMER
 * --------------------------------------------------------------------
 */
$order        = $data['data']['order'] ?? [];
$customer     = $data['data']['customer'] ?? [];
$subscription = $data['data']['subscription'] ?? [];

debug_log("ORDER_BLOCK", $order);
debug_log("CUSTOMER_BLOCK", $customer);

$email      = $customer['email'] ?? null;
$firstName  = $customer['first_name'] ?? "";
$lastName   = $customer['last_name'] ?? "";
$orderId    = $order['id'] ?? null;
$productId  = $order['product_id'] ?? null;
$orderTotal = isset($order['total']) ? (float)$order['total'] : 0;
$subId      = $subscription['id'] ?? null;

$referralCode = $order['referral_code'] ?? null;
$trackingId   = $order['tracking_id'] ?? null;

debug_log("REFERRAL_CODE", $referralCode);
debug_log("TRACKING_ID", $trackingId);

if (!$email) {
    debug_log("ERROR: Missing Email");
    exit("Missing customer email");
}

/**
 * --------------------------------------------------------------------
 * LOAD CLIENT
 * --------------------------------------------------------------------
 */
$stmt = $pdo->prepare("SELECT * FROM clients WHERE slug = ? LIMIT 1");
$stmt->execute([CLIENT_SLUG]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

debug_log("CLIENT_RESULT", $client);

if (!$client) {
    debug_log("ERROR: CLIENT NOT FOUND");
    exit("Client not found");
}

/**
 * --------------------------------------------------------------------
 * PRODUCT MAPPING
 * --------------------------------------------------------------------
 */
$stmt = $pdo->prepare("
    SELECT * FROM product_map
    WHERE samcart_product_id = ? AND active = 1
    LIMIT 1
");
$stmt->execute([$productId]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

debug_log("PRODUCT_LOOKUP_RESULT", $product);

if (!$product) {
    $pdo->prepare("
        INSERT INTO product_map (samcart_product_id, product_code, payout_amount, payout_type, active)
        VALUES (?, 'auto_unknown', 0, 'onetime', 1)
    ")->execute([$productId]);

    $product = [
        "product_code"  => "auto_unknown",
        "payout_amount" => 0,
        "payout_type"   => "onetime"
    ];
}

$productType  = $product['product_code'];
$payoutAmount = (float)$product['payout_amount'];
$payoutType   = $product['payout_type'];

/**
 * --------------------------------------------------------------------
 * USER AUTO-CREATION
 * --------------------------------------------------------------------
 */
debug_log("STEP: AUTO-USER CHECK START", $email);

$stmt = $pdo->prepare("
    SELECT * FROM referral_users
    WHERE client_id = ? AND email = ?
    LIMIT 1
");
$stmt->execute([$client['id'], $email]);
$buyer = $stmt->fetch(PDO::FETCH_ASSOC);

debug_log("USER_LOOKUP_RESULT", $buyer);

if (!$buyer) {
    debug_log("USER NOT FOUND — creating new user");

    $firstName = trim($firstName) ?: "Unknown";
    $lastName  = trim($lastName) ?: "User";

    // FIX 1: Use helpers.php version
    $initials = makeInitialsFromName($firstName, $lastName);

    // FIX 2: Use helpers.php generateReferralCode
    $refCode = generateReferralCode($pdo, $client['id'], $initials);

    $refLink = "https://clinicsecret.com/?ref={$refCode}";

    $insert = $pdo->prepare("
        INSERT INTO referral_users
        (client_id, email, first_name, last_name, initials, referral_code, referral_link)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $insert->execute([
        $client['id'], $email, $firstName, $lastName, $initials, $refCode, $refLink
    ]);

    debug_log("USER_INSERT_DONE");

    // Reload
    $stmt = $pdo->prepare("
        SELECT * FROM referral_users
        WHERE client_id = ? AND email = ?
        LIMIT 1
    ");
    $stmt->execute([$client['id'], $email]);
    $buyer = $stmt->fetch(PDO::FETCH_ASSOC);

    debug_log("RELOADED_USER", $buyer);

    // FIX 3: correct password set link path
    $token = createPasswordResetToken($pdo, $buyer['id']);
    $resetLink = "https://clinicsecret.com/referral/set-password.php?token={$token}";

    sendAmbassadorWelcomeEmail($buyer, $resetLink);
}

/**
 * --------------------------------------------------------------------
 * REFERRER DETECTION (unchanged)
 * --------------------------------------------------------------------
 */
debug_log("STEP: REFERRER CHECK START");

$refUser = null;

// referral_code
if ($referralCode) {
    $stmt = $pdo->prepare("SELECT * FROM referral_users WHERE referral_code = ? LIMIT 1");
    $stmt->execute([$referralCode]);
    $refUser = $stmt->fetch(PDO::FETCH_ASSOC);
}

// tracking click
if (!$refUser && $trackingId) {
    $stmt = $pdo->prepare("
        SELECT referral_user_id 
        FROM referral_clicks 
        WHERE tracking_id = ?
        ORDER BY clicked_at DESC
        LIMIT 1
    ");
    $stmt->execute([$trackingId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $stmt2 = $pdo->prepare("SELECT * FROM referral_users WHERE id = ?");
        $stmt2->execute([$row['referral_user_id']]);
        $refUser = $stmt2->fetch(PDO::FETCH_ASSOC);
    }
}

// last click fallback
if (!$refUser) {
    $stmt = $pdo->prepare("
        SELECT referral_user_id 
        FROM referral_clicks
        ORDER BY clicked_at DESC
        LIMIT 1
    ");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $stmt2 = $pdo->prepare("SELECT * FROM referral_users WHERE id = ?");
        $stmt2->execute([$row['referral_user_id']]);
        $refUser = $stmt2->fetch(PDO::FETCH_ASSOC);
    }
}

debug_log("FINAL_REFERRER", $refUser);

/**
 * --------------------------------------------------------------------
 * HANDLE EVENTS (unchanged)
 * --------------------------------------------------------------------
 */
debug_log("STEP: HANDLE EVENT", $event);

// order.completed
if ($event === "order.completed") {

    debug_log("ORDER_COMPLETED START", [
        "refUser" => $refUser,
        "buyer"   => $buyer
    ]);

    if ($refUser) {
        create_referral_order_and_payout(
            $pdo, $client, $refUser, $email,
            $orderId, $subId, $productType, $orderTotal,
            $payoutAmount, $payoutType
        );
        debug_log("ORDER_PAYOUT_CREATED");
    }

    exit("Order processed");
}

// subscription.renewed
if ($event === "subscription.renewed") {

    if (!$subId) exit("Missing subscription ID");

    $stmt = $pdo->prepare("
        SELECT * FROM referral_orders
        WHERE samcart_subscription_id = ?
        LIMIT 1
    ");
    $stmt->execute([$subId]);
    $orig = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($orig) {
        $stmt2 = $pdo->prepare("SELECT * FROM referral_users WHERE id = ?");
        $stmt2->execute([$orig['referral_user_id']]);
        $refUser = $stmt2->fetch(PDO::FETCH_ASSOC);

        if ($refUser) {
            create_referral_order_and_payout(
                $pdo, $client, $refUser,
                $orig['referred_email'],
                $orderId ?: "renew_" . time(),
                $subId,
                $orig['product_type'],
                $orderTotal,
                $payoutAmount,
                $payoutType
            );
        }
    }

    exit("Renewal processed");
}

exit("Event ignored");
?>
