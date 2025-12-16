<?php
require_once __DIR__ . '/connect.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/helpers.php';

// Always return a 200 unless overridden later to avoid webhook retries timing out
http_response_code(200);

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

function persist_raw_body(string $requestId, string $rawBody): void {
    $file = __DIR__ . "/webhook_raw.log";
    $ts = date("Y-m-d\TH:i:sP");
    $entry = "-----\n[$ts][$requestId] length=" . strlen($rawBody) . "\n" . $rawBody . "\n";
    @file_put_contents($file, $entry, FILE_APPEND);
}

function normalize_email_value(?string $email): ?string {
    $normalized = strtolower(trim((string) ($email ?? '')));
    return $normalized !== '' ? $normalized : null;
}

function normalize_webhook_payload(array $data): array {
    // Special SamCart Order format
    if (isset($data['type']) && $data['type'] === 'Order') {
        $cust = $data['customer'] ?? [];
        $ord  = $data['order'] ?? [];
        $prod = $data['products'][0] ?? [];

        return [
            'event' => 'order.completed',
            'data'  => [
                'order' => [
                    'id'            => $ord['id'] ?? null,
                    'total'         => isset($ord['total']) ? (float)$ord['total'] : 0,
                    'product_id'    => $prod['id'] ?? null,
                    'referral_code' => $ord['analytics']['campaign'] ?? ($ord['referral_code'] ?? null),
                    'tracking_id'   => $ord['analytics']['content'] ?? ($ord['tracking_id'] ?? null),
                    'external_customer_id' => $ord['customer_id'] ?? null,
                ],
                'customer' => [
                    'email'      => $cust['email'] ?? null,
                    'first_name' => $cust['first_name'] ?? '',
                    'last_name'  => $cust['last_name'] ?? '',
                ],
                'subscription' => [
                    'id' => $data['subscription']['id'] ?? null,
                ],
            ],
            '_raw_event' => $data['event'] ?? $data['type'] ?? null,
        ];
    }

    $order = $data['data']['order'] ?? ($data['order'] ?? []);
    $customer = $data['data']['customer'] ?? ($data['customer'] ?? []);
    $subscription = $data['data']['subscription'] ?? ($data['subscription'] ?? []);

    // Fallbacks for form posts
    $order = array_merge([
        'id' => $data['order_id'] ?? null,
        'product_id' => $data['product_id'] ?? null,
        'total' => $data['total'] ?? null,
        'referral_code' => $data['referral_code'] ?? null,
        'tracking_id' => $data['tracking_id'] ?? null,
        'external_customer_id' => $data['customer_id'] ?? null,
    ], is_array($order) ? $order : []);

    $customer = array_merge([
        'email' => $data['customer_email'] ?? ($data['email'] ?? null),
        'first_name' => $data['first_name'] ?? '',
        'last_name' => $data['last_name'] ?? '',
    ], is_array($customer) ? $customer : []);

    $subscription = array_merge([
        'id' => $data['subscription_id'] ?? null,
    ], is_array($subscription) ? $subscription : []);

    $event = $data['event'] ?? ($data['type'] ?? null);
    if (!$event && (!empty($order['id']) || !empty($customer['email']))) {
        $event = 'order.completed';
    }

    return [
        'event' => $event,
        'data' => [
            'order' => [
                'id' => $order['id'] ?? null,
                'total' => isset($order['total']) ? (float)$order['total'] : 0,
                'product_id' => $order['product_id'] ?? null,
                'referral_code' => $order['referral_code'] ?? null,
                'tracking_id' => $order['tracking_id'] ?? null,
                'external_customer_id' => $order['external_customer_id'] ?? null,
            ],
            'customer' => [
                'email' => $customer['email'] ?? null,
                'first_name' => $customer['first_name'] ?? '',
                'last_name' => $customer['last_name'] ?? '',
            ],
            'subscription' => [
                'id' => $subscription['id'] ?? null,
            ],
        ],
        '_raw_event' => $data['event'] ?? $data['type'] ?? null,
    ];
}

/**
 * --------------------------------------------------------------------
 * REQUEST METADATA
 * --------------------------------------------------------------------
 */
$requestId = bin2hex(random_bytes(8));
$receivedAt = date('c');
$requestHeaders = function_exists('getallheaders') ? getallheaders() : $_SERVER;

debug_log('WEBHOOK_REQUEST_META', [
    'request_id' => $requestId,
    'received_at' => $receivedAt,
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
    'uri' => $_SERVER['REQUEST_URI'] ?? 'UNKNOWN',
    'headers' => $requestHeaders,
]);

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

function logWebhook(PDO $pdo, $eventType, array $payload, string $requestId) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO webhook_logs (event_type, request_id, payload, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([
            $eventType,
            $requestId,
            json_encode($payload, JSON_UNESCAPED_SLASHES),
        ]);
    } catch (Exception $e) {
        debug_log("ERROR_INSERTING_WEBHOOK_LOG", [
            'message' => $e->getMessage(),
            'request_id' => $requestId,
        ]);
    }
}

debug_log("=== WEBHOOK STARTED ===");

/**
 * --------------------------------------------------------------------
 * RAW INPUT + CONTENT TYPE HANDLING
 * --------------------------------------------------------------------
 */
$raw = file_get_contents("php://input");
persist_raw_body($requestId, $raw);
debug_log("RAW INPUT", ['request_id' => $requestId, 'length' => strlen($raw)]);

$contentTypeHeader = $_SERVER['CONTENT_TYPE'] ?? '';
$normalizedContentType = strtolower(trim(explode(';', $contentTypeHeader)[0]));
$data = [];
$jsonError = null;

if ($normalizedContentType === 'application/json') {
    $data = json_decode($raw, true);
    $jsonError = json_last_error();
} elseif ($normalizedContentType === 'application/x-www-form-urlencoded') {
    parse_str($raw, $data);
} else {
    // Attempt JSON as a best-effort fallback
    $data = json_decode($raw, true);
    $jsonError = json_last_error();
}

if (!is_array($data)) {
    $data = [];
}

debug_log("CONTENT_TYPE_DETECTED", [
    'request_id' => $requestId,
    'content_type' => $contentTypeHeader,
    'normalized' => $normalizedContentType,
    'json_error' => $jsonError,
]);

if ($jsonError && $jsonError !== JSON_ERROR_NONE) {
    debug_log('JSON_DECODE_ERROR', [
        'request_id' => $requestId,
        'error' => $jsonError,
    ]);
}

debug_log("PAYLOAD_TOP_LEVEL_KEYS", array_keys($data));

$eventFromPayload = $data['event'] ?? ($data['type'] ?? null);
debug_log("EVENT_TYPE_RAW", ['request_id' => $requestId, 'event' => $eventFromPayload]);

/**
 * --------------------------------------------------------------------
 * DB LOG RAW PAYLOAD
 * --------------------------------------------------------------------
 */
$initialEventType = $eventFromPayload ?? "unknown_format";
saveRawWebhook($pdo, $initialEventType, $raw);

/**
 * --------------------------------------------------------------------
 * NORMALIZATION
 * --------------------------------------------------------------------
 */
debug_log("STEP: NORMALIZATION START", ['request_id' => $requestId]);

$data = normalize_webhook_payload($data);

if (!$data || !isset($data['event'])) {
    debug_log("Payload not recognized or missing event", ['request_id' => $requestId]);
    echo "Webhook received but not recognized";
    exit;
}

$event = $data['event'];
debug_log("EVENT DETECTED", ['request_id' => $requestId, 'event' => $event]);

debug_log("NORMALIZED_PAYLOAD_KEYS", [
    'request_id' => $requestId,
    'order_keys' => array_keys($data['data']['order'] ?? []),
    'customer_keys' => array_keys($data['data']['customer'] ?? []),
    'subscription_keys' => array_keys($data['data']['subscription'] ?? []),
]);

/**
 * --------------------------------------------------------------------
 * LOG NORMALIZED
 * --------------------------------------------------------------------
 */
logWebhook($pdo, $event, $data, $requestId);

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

$email      = normalize_email_value($customer['email'] ?? null);
$firstName  = trim((string)($customer['first_name'] ?? ""));
$lastName   = trim((string)($customer['last_name'] ?? ""));
$orderId    = $order['id'] ?? null;
$productId  = $order['product_id'] ?? null;
$orderTotal = isset($order['total']) ? (float)$order['total'] : 0;
$subId      = $subscription['id'] ?? null;
$externalCustomerId = $order['external_customer_id'] ?? null;

$referralCode = $order['referral_code'] ?? null;
$trackingId   = $order['tracking_id'] ?? null;

debug_log("REFERRAL_CODE", $referralCode);
debug_log("TRACKING_ID", $trackingId);
$context = [
    'request_id' => $requestId,
    'event' => $event,
    'order_id' => $orderId,
    'subscription_id' => $subId,
    'email' => $email,
    'external_customer_id' => $externalCustomerId,
];

if (!$email) {
    debug_log("ERROR: Missing Email", $context);
    echo "Missing customer email";
    exit;
}

$idempotencyKey = $orderId ?? $externalCustomerId ?? $email;
debug_log('IDEMPOTENCY_KEY', array_merge($context, ['key' => $idempotencyKey]));

$responseMessage = 'Event ignored';
$userCreated = false;

try {
    $pdo->beginTransaction();

    /**
     * --------------------------------------------------------------------
     * LOAD CLIENT
     * --------------------------------------------------------------------
     */
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE slug = ? LIMIT 1");
    $stmt->execute([CLIENT_SLUG]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    debug_log("CLIENT_RESULT", array_merge($context, ['client' => $client]));

    if (!$client) {
        throw new RuntimeException('Client not found');
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

    debug_log("PRODUCT_LOOKUP_RESULT", array_merge($context, ['product' => $product]));

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
     * USER AUTO-CREATION (IDEMPOTENT)
     * --------------------------------------------------------------------
     */
    debug_log("STEP: AUTO-USER CHECK START", array_merge($context, ['email' => $email]));

    $stmt = $pdo->prepare("
        SELECT * FROM referral_users
        WHERE client_id = ? AND (email = ? OR external_customer_id = ?)
        LIMIT 1
    ");
    $stmt->execute([$client['id'], $email, $externalCustomerId]);
    $buyer = $stmt->fetch(PDO::FETCH_ASSOC);

    debug_log("USER_LOOKUP_RESULT", array_merge($context, ['buyer' => $buyer]));

    if (!$buyer) {
        debug_log("USER NOT FOUND — creating new user", $context);

        $firstName = $firstName ?: "Unknown";
        $lastName  = $lastName ?: "User";

        $initials = makeInitialsFromName($firstName, $lastName);
        $refCode = generateReferralCode($pdo, $client['id'], $initials);
        $refLink = "https://clinicsecret.com/?ref={$refCode}";

        $insert = $pdo->prepare("
            INSERT INTO referral_users
            (client_id, email, external_customer_id, first_name, last_name, initials, referral_code, referral_link)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $insert->execute([
            $client['id'], $email, $externalCustomerId, $firstName, $lastName, $initials, $refCode, $refLink
        ]);

        $userCreated = true;
        debug_log("USER_INSERT_DONE", array_merge($context, ['email' => $email]));

        // Reload
        $stmt = $pdo->prepare("
            SELECT * FROM referral_users
            WHERE client_id = ? AND (email = ? OR external_customer_id = ?)
            LIMIT 1
        ");
        $stmt->execute([$client['id'], $email, $externalCustomerId]);
        $buyer = $stmt->fetch(PDO::FETCH_ASSOC);

        debug_log("RELOADED_USER", array_merge($context, ['buyer' => $buyer]));

        $token = createPasswordResetToken($pdo, $buyer['id']);
        $resetLink = "https://clinicsecret.com/referral/set-password.php?token={$token}";

        sendAmbassadorWelcomeEmail($buyer, $resetLink);
        debug_log('HEALTH_USER_CREATED', [
            'request_id' => $requestId,
            'user_id' => $buyer['id'] ?? null,
            'email' => $email,
        ]);
    } else {
        debug_log('USER_ALREADY_EXISTS', array_merge($context, ['buyer_id' => $buyer['id'] ?? null]));
        debug_log('HEALTH_USER_EXISTS', [
            'request_id' => $requestId,
            'user_id' => $buyer['id'] ?? null,
            'email' => $email,
        ]);
    }

    /**
     * --------------------------------------------------------------------
     * REFERRER DETECTION
     * --------------------------------------------------------------------
     */
    debug_log("STEP: REFERRER CHECK START", $context);

    $refUser = null;

    if ($referralCode) {
        $stmt = $pdo->prepare("SELECT * FROM referral_users WHERE referral_code = ? LIMIT 1");
        $stmt->execute([$referralCode]);
        $refUser = $stmt->fetch(PDO::FETCH_ASSOC);
    }

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

    debug_log("FINAL_REFERRER", array_merge($context, ['refUser' => $refUser]));

    /**
     * --------------------------------------------------------------------
     * HANDLE EVENTS
     * --------------------------------------------------------------------
     */
    debug_log("STEP: HANDLE EVENT", array_merge($context, ['event' => $event]));

    switch ($event) {
        case "order.completed":
            debug_log("ORDER_COMPLETED START", [
                "refUser" => $refUser,
                "buyer"   => $buyer,
                'request_id' => $requestId,
            ]);

            $orderExists = false;
            if ($orderId) {
                $existingOrderStmt = $pdo->prepare("SELECT id FROM referral_orders WHERE samcart_order_id = ? LIMIT 1");
                $existingOrderStmt->execute([$orderId]);
                $orderExists = (bool) $existingOrderStmt->fetch(PDO::FETCH_ASSOC);
            }

            if ($orderExists) {
                debug_log('ORDER_ALREADY_EXISTS', array_merge($context, ['order_id' => $orderId]));
                $responseMessage = 'Order already processed';
                break;
            }

            if ($refUser) {
                create_referral_order_and_payout(
                    $pdo, $client, $refUser, $email,
                    $orderId ?: ($idempotencyKey . '_order'), $subId, $productType, $orderTotal,
                    $payoutAmount, $payoutType
                );
                debug_log("ORDER_PAYOUT_CREATED", array_merge($context, ['refUser' => $refUser]));
                $responseMessage = 'Order processed';
            } else {
                debug_log('NO_REFERRER_FOUND', $context);
                $responseMessage = 'Order processed without referrer';
            }
            break;

        case "subscription.renewed":
            if (!$subId) {
                debug_log('MISSING_SUBSCRIPTION_ID', $context);
                $responseMessage = 'Missing subscription ID';
                break;
            }

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
                    $responseMessage = 'Renewal processed';
                } else {
                    debug_log('RENEWAL_REFUSER_NOT_FOUND', $context);
                    $responseMessage = 'Renewal logged without referrer';
                }
            } else {
                debug_log('ORIGINAL_SUBSCRIPTION_NOT_FOUND', $context);
                $responseMessage = 'Renewal missing original order';
            }
            break;

        default:
            debug_log('EVENT_IGNORED', $context);
            $responseMessage = 'Event ignored';
            break;
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    debug_log('WEBHOOK_EXCEPTION', [
        'request_id' => $requestId,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
        'context' => $context,
    ]);

    $responseMessage = 'Webhook recorded with errors';
}

echo $responseMessage;
exit;
?>
